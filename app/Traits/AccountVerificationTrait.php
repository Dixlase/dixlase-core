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

namespace App\Traits;

use App\Services\MailServerValidatorService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * @internal Core only. Do not reference from plugins/themes
 *
 * Common trait for account verification
 *
 * Provides functionality commonly used in account verification processes for members and users.
 * Controllers using this trait must implement the following abstract methods:
 */
trait AccountVerificationTrait
{
    /**
     * Get context (implement in subclass)
     *
     * @return string Context name (e.g., 'admin', 'user')
     */
    abstract protected function getContext(): string;

    /**
     * Get administrator email address settings key (implement in subclass)
     *
     * @return string Settings key name
     */
    abstract protected function getAdminEmailSettingKey(): string;

    /**
     * Get notification email address settings key (implement in subclass)
     *
     * @return string Settings key name
     */
    abstract protected function getNotificationEmailSettingKey(): string;

    /**
     * Get settings model class (implement in subclass)
     *
     * @return string Settings model class name
     */
    abstract protected function getSettingModelClass(): string;

    /**
     * Get verification completion notification class according to context
     *
     * @return string Notification class name
     */
    protected function getVerificationCompletedNotificationClass(): string
    {
        return match ($this->getContext()) {
            'admin' => \App\Notifications\MemberVerificationCompletedNotification::class,
            'user' => \App\Notifications\UserVerificationCompletedNotification::class,
            default => \App\Notifications\MemberVerificationCompletedNotification::class,
        };
    }

    /**
     * Get administrator notification class according to context
     *
     * @return string Notification class name
     */
    protected function getAdminVerifiedNotificationClass(): string
    {
        return match ($this->getContext()) {
            'admin' => \App\Notifications\AdminMemberVerifiedNotification::class,
            'user' => \App\Notifications\AdminUserVerifiedNotification::class,
            default => \App\Notifications\AdminMemberVerifiedNotification::class,
        };
    }

    /**
     * Execute verification process if email verification is pending after login
     *
     * @param  mixed  $user  User model (Member or User)
     * @param  \Illuminate\Http\Request  $request  Request object
     */
    protected function processEmailVerificationIfPending($user, $request): void
    {
        $verificationData = session('email_verification_pending');

        if (! $verificationData) {
            return;
        }

        // Check token expiration
        if ($verificationData['expires_at'] < now()->timestamp) {
            session()->forget('email_verification_pending');
            session()->flash('error', __('auth.verification_token_expired'));

            return;
        }

        // Check if logged-in user matches user awaiting verification
        if ($user->id !== $verificationData['member_id']) {
            session()->forget('email_verification_pending');
            session()->flash('error', __('auth.verification_member_mismatch'));

            return;
        }

        // Re-verify hash
        $expectedHash = sha1($verificationData['email']);
        if (! hash_equals((string) $verificationData['hash'], $expectedHash)) {
            session()->forget('email_verification_pending');
            session()->flash('error', __('auth.verification_invalid'));

            return;
        }

        try {
            if ($verificationData['is_email_change']) {
                // Email address change verification
                $this->processEmailChange($user);
            } else {
                // New account verification
                $this->processAccountVerification($user);
            }

            // Remove from session after verification is complete
            session()->forget('email_verification_pending');
        } catch (\Exception $e) {
            Log::error('[Account Verification] Failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            session()->forget('email_verification_pending');
            session()->flash('error', __('auth.verification_failed'));
        }
    }

    /**
     * Process email address change verification
     *
     * @param  mixed  $user
     */
    protected function processEmailChange($user): void
    {
        $user->email = $user->pending_email;
        $user->pending_email = null;
        $user->email_verified_at = now();
        $user->save();

        Log::info('[Account Verification] Email change verified', [
            'user_id' => $user->id,
            'new_email' => $user->email,
        ]);

        session()->flash('success', __('account.email_verification_success'));
    }

    /**
     * Process new account verification
     *
     * @param  mixed  $user
     */
    protected function processAccountVerification($user): void
    {
        $user->markEmailAsVerified();

        Log::info('[Account Verification] Account verified', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        session()->flash('success', __('account.account_verification_success'));

        // Send notification only if mail server is configured
        if (MailServerValidatorService::isMailServerTested()) {
            $this->sendVerificationNotifications($user);
        }
    }

    /**
     * Send verification completion notification
     *
     * @param  mixed  $user
     */
    protected function sendVerificationNotifications($user): void
    {
        // Send verification completion email to the user
        $notificationClass = $this->getVerificationCompletedNotificationClass();
        if ($notificationClass && class_exists($notificationClass)) {
            try {
                $user->notify(new $notificationClass());

                Log::info('[Account Verification] Notification sent to user', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);
            } catch (\Exception $e) {
                Log::error('[Account Verification] Failed to send notification to user', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Notify administrator
        $adminNotificationClass = $this->getAdminVerifiedNotificationClass();
        if ($adminNotificationClass && class_exists($adminNotificationClass)) {
            try {
                $settingModel = $this->getSettingModelClass();
                $adminEmail = $settingModel::getValue($this->getAdminEmailSettingKey())
                    ?? $settingModel::getValue($this->getNotificationEmailSettingKey());

                if ($adminEmail) {
                    Notification::route('mail', $adminEmail)
                        ->notify(new $adminNotificationClass(
                            $user,
                            now()->format('Y-m-d H:i:s')
                        ));

                    Log::info('[Account Verification] Notification sent to admin', [
                        'user_id' => $user->id,
                        'admin_email' => $adminEmail,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('[Account Verification] Failed to send notification to admin', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
