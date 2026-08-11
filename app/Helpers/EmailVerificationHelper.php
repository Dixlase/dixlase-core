<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Helpers;

use App\Services\MailServerValidatorService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * @internal For Core use only. Do not reference from plugins/themes
 */
class EmailVerificationHelper
{
    /**
     * Generate email verification hash
     *
     * @param  mixed  $user  User model
     * @return string Hash value
     */
    public function generateVerificationHash($user): string
    {
        return sha1($user->getEmailForVerification());
    }

    /**
     * Verify email verification hash
     *
     * @param  mixed  $user  User model
     * @param  string  $hash  Hash to verify
     * @return bool Verification result
     */
    public function verifyHash($user, string $hash): bool
    {
        return hash_equals((string) $hash, $this->generateVerificationHash($user));
    }

    /**
     * Check if email verification is required
     *
     * @param  mixed  $user  User model
     * @return array ['needs_verification' => bool, 'is_email_change' => bool]
     */
    public function needsVerification($user): array
    {
        $isEmailChange = ! empty($user->pending_email);
        $needsVerification = ! $user->hasVerifiedEmail() || $isEmailChange;

        return [
            'needs_verification' => $needsVerification,
            'is_email_change' => $isEmailChange,
        ];
    }

    /**
     * Process email verification immediately (if logged in)
     *
     * @param  mixed  $user  User model
     * @param  string  $context  Context (admin, user, etc.)
     * @return array ['success' => bool, 'message' => string, 'redirect' => string]
     */
    public function processVerificationImmediately($user, string $context = 'admin'): array
    {
        try {
            if ($user->pending_email) {
                // Email address change verification
                $oldEmail = $user->email;
                $user->email = $user->pending_email;
                $user->pending_email = null;
                $user->email_verified_at = now();
                $user->save();

                Log::info('[Email Verification] Email change verified immediately', [
                    'user_id' => $user->id,
                    'old_email' => $oldEmail,
                    'new_email' => $user->email,
                    'context' => $context,
                ]);

                return [
                    'success' => true,
                    'message' => __('account.email_verification_success'),
                    'redirect' => route("{$context}.profile"),
                ];
            } else {
                // New account verification
                $user->markEmailAsVerified();

                Log::info('[Email Verification] Account verified immediately', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'context' => $context,
                ]);

                // Send verification completion notification
                $this->sendVerificationNotifications($user, $context);

                return [
                    'success' => true,
                    'message' => __('account.account_verification_success'),
                    'redirect' => route("{$context}.dashboard"),
                ];
            }
        } catch (\Exception $e) {
            Log::error('[Email Verification] Verification failed (immediate)', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'context' => $context,
            ]);

            return [
                'success' => false,
                'message' => __('auth.verification_failed'),
                'redirect' => route("{$context}.profile"),
            ];
        }
    }

    /**
     * Save email verification info to session (when not logged in)
     *
     * @param  mixed  $user  User model
     * @param  string  $hash  Verification hash
     * @param  int  $expiresMinutes  Expiration time (minutes)
     * @return array Data saved in session
     */
    public function storeVerificationInSession($user, string $hash, int $expiresMinutes = 30): array
    {
        $data = [
            'member_id' => $user->id,
            'hash' => $hash,
            'email' => $user->pending_email ?? $user->email,
            'is_email_change' => (bool) $user->pending_email,
            'expires_at' => now()->addMinutes($expiresMinutes)->timestamp,
        ];

        session(['email_verification_pending' => $data]);
        session()->save();

        Log::info('[Email Verification] Verification info stored in session', [
            'user_id' => $user->id,
            'is_email_change' => $data['is_email_change'],
            'expires_at' => date('Y-m-d H:i:s', $data['expires_at']),
        ]);

        return $data;
    }

    /**
     * Retrieve email verification info from session
     *
     * @return array|null Verification info or null
     */
    public function getVerificationFromSession(): ?array
    {
        $data = session('email_verification_pending');

        if (! $data) {
            return null;
        }

        // Expiration check
        if (isset($data['expires_at']) && now()->timestamp > $data['expires_at']) {
            session()->forget('email_verification_pending');
            Log::warning('[Email Verification] Session data expired', [
                'expired_at' => date('Y-m-d H:i:s', $data['expires_at']),
            ]);

            return null;
        }

        return $data;
    }

    /**
     * Remove email verification information from session
     */
    public function clearVerificationFromSession(): void
    {
        session()->forget('email_verification_pending');
    }

    /**
     * Send verification completion notification
     *
     * @param  mixed  $user  User model
     * @param  string  $context  Context (admin, user, etc.)
     */
    protected function sendVerificationNotifications($user, string $context = 'admin'): void
    {
        // Send notification only if email server is configured
        if (! MailServerValidatorService::isMailServerTested()) {
            Log::info('[Email Verification] Mail server not configured, skipping notifications');

            return;
        }

        // Send verification completion email to the user
        $this->sendUserNotification($user, $context);

        // Notify administrator
        $this->sendAdminNotification($user, $context);
    }

    /**
     * Send verification completion notification to the user
     *
     * @param  mixed  $user  User model
     * @param  string  $context  Context
     */
    protected function sendUserNotification($user, string $context): void
    {
        try {
            $notificationClass = $this->getVerificationCompletedNotificationClass($context);

            if (class_exists($notificationClass)) {
                $user->notify(new $notificationClass());

                Log::info('[Email Verification] Verification completed notification sent to user', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'context' => $context,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('[Email Verification] Failed to send verification completed notification to user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'context' => $context,
            ]);
        }
    }

    /**
     * Send verification completion notification to administrator
     *
     * @param  mixed  $user  User model
     * @param  string  $context  Context
     */
    protected function sendAdminNotification($user, string $context): void
    {
        try {
            $adminEmail = \App\Models\SiteSetting::getValue('system_admin_email')
                ?? \App\Models\SiteSetting::getValue('notification_email');

            if (! $adminEmail) {
                Log::info('[Email Verification] No admin email configured, skipping admin notification');

                return;
            }

            $notificationClass = $this->getAdminVerifiedNotificationClass($context);

            if (class_exists($notificationClass)) {
                Notification::route('mail', $adminEmail)
                    ->notify(new $notificationClass($user, now()->format('Y-m-d H:i:s')));

                Log::info('[Email Verification] Verification notification sent to admin', [
                    'user_id' => $user->id,
                    'admin_email' => $adminEmail,
                    'context' => $context,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('[Email Verification] Failed to send verification notification to admin', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'context' => $context,
            ]);
        }
    }

    /**
     * Get verification completion notification class based on context
     *
     * @param  string  $context  Context
     * @return string Notification class name
     */
    protected function getVerificationCompletedNotificationClass(string $context): string
    {
        return match ($context) {
            // Kept in step with AccountVerificationTrait, which carries the same
            // mapping. MemberVerifiedNotification is the renamed class; the old
            // name never existed as a file, so this branch resolved to nothing
            // and the class_exists() gate at the call site skipped the mail
            // silently instead of failing.
            'admin' => \App\Notifications\MemberVerifiedNotification::class,
            // A 'user' arm naming App\Notifications\UserVerificationCompletedNotification
            // used to sit here. That class belongs to a front-end account plugin
            // and has never existed in Core, so the arm only ever resolved to a
            // name the class_exists() gate at the call site rejected.
            default => \App\Notifications\MemberVerifiedNotification::class,
        };
    }

    /**
     * Get administrator notification class based on context
     *
     * @param  string  $context  Context
     * @return string Notification class name
     */
    protected function getAdminVerifiedNotificationClass(string $context): string
    {
        return match ($context) {
            'admin' => \App\Notifications\AdminMemberVerifiedNotification::class,
            default => \App\Notifications\AdminMemberVerifiedNotification::class,
        };
    }

    /**
     * Get message key for email verification link
     *
     * @param  bool  $isEmailChange  Whether this is an email address change
     * @return string Message key
     */
    public function getLoginRequiredMessageKey(bool $isEmailChange): string
    {
        return $isEmailChange
            ? 'account.verify_email_change_login_required'
            : 'account.verify_email_login_required';
    }
}
