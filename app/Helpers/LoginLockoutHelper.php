<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

use App\Models\MemberLoginAttempt;
use App\Models\SecuritySetting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LoginLockoutHelper
{
    /**
     * Check if login attempt restriction is enabled
     *
     * @param  mixed  $settingSource  Settings source (e.g., SecuritySetting::class)
     */
    public static function isLockoutEnabled(string $settingKey = 'login_attempt_limit_enabled', $settingSource = null): bool
    {
        if ($settingSource) {
            return (bool) $settingSource::getValue($settingKey, false);
        }

        return (bool) SecuritySetting::getValue($settingKey, false);
    }

    /**
     * Check if lockout notification is enabled
     *
     * @param  mixed  $settingSource  Settings source (e.g., SecuritySetting::class)
     */
    public static function isNotificationEnabled(string $settingKey = 'lockout_notification_enabled', $settingSource = null): bool
    {
        if ($settingSource) {
            return (bool) $settingSource::getValue($settingKey, true);
        }

        return (bool) SecuritySetting::getValue($settingKey, true);
    }

    /**
     * Get login attempt restriction settings
     *
     * @param  array  $settingKeys  Array of settings keys
     * @param  mixed  $settingSource  Settings source (e.g., SecuritySetting::class)
     */
    public static function getLockoutSettings(array $settingKeys = [], $settingSource = null): array
    {
        $defaultKeys = [
            'enabled_key' => 'login_attempt_limit_enabled',
            'max_attempts_key' => 'login_attempt_max_attempts',
            'max_attempts_ip_key' => 'login_attempt_max_attempts_ip',
            'time_window_key' => 'login_attempt_time_window',
            'lockout_duration_key' => 'login_attempt_lockout_duration',
            'notification_enabled_key' => 'login_attempt_lockout_notification_enabled',
        ];

        $keys = array_merge($defaultKeys, $settingKeys);
        $source = $settingSource ?: SecuritySetting::class;

        return [
            'enabled' => (bool) $source::getValue($keys['enabled_key'], false),
            'max_attempts' => (int) $source::getValue($keys['max_attempts_key'], 5),
            'max_attempts_ip' => (int) $source::getValue($keys['max_attempts_ip_key'], null),
            'time_window' => (int) $source::getValue($keys['time_window_key'], 15),
            'lockout_duration' => (int) $source::getValue($keys['lockout_duration_key'], 30),
            'notification_enabled' => (bool) $source::getValue($keys['notification_enabled_key'], true),
        ];
    }

    /**
     * Record login attempt and check lockout status
     *
     * @param  array  $settings  Settings array
     * @param  mixed  $settingSource  Settings source
     */
    public static function recordAndCheckLockout(
        Request $request,
        string $identifier,
        bool $successful = false,
        array $settings = [],
        $settingSource = null,
        ?string $failureReason = null,
    ): array {
        // Record login attempt (with behavioral analysis data)
        $behaviorService = app(\App\Services\LoginBehaviorService::class);
        $additionalData = [];
        if (! $successful && $failureReason) {
            $additionalData['failure_reason'] = $failureReason;
        }
        $behaviorService->recordLoginAttempt($identifier, $request, $successful, $additionalData);

        if ($successful) {
            // Clear failure records on success
            MemberLoginAttempt::clearFailedAttempts($identifier);

            return ['success' => true];
        }

        // Check lockout status on failure
        return static::checkLockoutStatus($request, $identifier, $settings, $settingSource);
    }

    /**
     * Check lockout status
     *
     * @param  array  $settings  Settings array
     * @param  mixed  $settingSource  Settings source
     */
    public static function checkLockoutStatus(
        Request $request,
        string $identifier,
        array $settings = [],
        $settingSource = null
    ): array {
        $lockoutSettings = static::getLockoutSettings($settings, $settingSource);

        $lockoutInfo = [
            'is_locked_out' => false,
            'remaining_attempts' => null,
            'lockout_minutes' => null,
            'is_ip_locked_out' => false,
            'settings' => $lockoutSettings,
        ];

        if (! $lockoutSettings['enabled']) {
            return $lockoutInfo;
        }

        // Check failure count within time window
        $failedAttempts = MemberLoginAttempt::getFailedAttemptsCount(
            $identifier,
            $lockoutSettings['time_window']
        );

        if ($failedAttempts >= $lockoutSettings['max_attempts']) {
            // If failure count reaches limit, check lockout period
            $remainingLockoutMinutes = static::getLockoutRemainingMinutes(
                $identifier,
                $lockoutSettings['lockout_duration']
            );

            if ($remainingLockoutMinutes === null) {
                // If no failure records exist (normally should not reach here)
                $lockoutInfo['is_locked_out'] = false;
                $lockoutInfo['remaining_attempts'] = $lockoutSettings['max_attempts'];
                $lockoutInfo['lockout_minutes'] = 0;
            } elseif ($remainingLockoutMinutes === 0) {
                // Release lockout if lockout period has ended
                $lockoutInfo['is_locked_out'] = false;
                $lockoutInfo['remaining_attempts'] = $lockoutSettings['max_attempts'];
                $lockoutInfo['lockout_minutes'] = 0;
            } else {
                // During lockout period
                $lockoutInfo['is_locked_out'] = true;
                $lockoutInfo['lockout_minutes'] = $remainingLockoutMinutes;

                // Send lockout notification (prevent duplicate sending)
                if ($lockoutSettings['notification_enabled']) {
                    $notificationKey = 'lockout_notification_sent_'.md5($identifier);
                    $lastNotificationTime = session($notificationKey);

                    // Resend only if more than 30 minutes have passed since last notification
                    if (! $lastNotificationTime || Carbon::parse($lastNotificationTime)->addMinutes(30)->isPast()) {
                        // Attempt to send notification and record in session only on success
                        $sent = static::sendLockoutNotification($identifier, $request, $lockoutSettings);
                        if ($sent) {
                            session([$notificationKey => Carbon::now()->toDateTimeString()]);
                        } else {
                            Log::warning('LoginLockout: Notification failed, not recorded in session', [
                                'identifier' => $identifier,
                            ]);
                        }
                    } else {
                    }
                } else {
                }
            }
        } else {
            // Normal state if failure count is below limit
            $lockoutInfo['is_locked_out'] = false;
            $lockoutInfo['remaining_attempts'] = $lockoutSettings['max_attempts'] - $failedAttempts;
            $lockoutInfo['lockout_minutes'] = 0;
        }

        // Also check IP address-based lockout
        $ipFailedAttempts = MemberLoginAttempt::getFailedAttemptsCountByIp(
            $request->ip(),
            $lockoutSettings['time_window']
        );
        // Use login_attempt_max_attempts_ip settings preferentially, otherwise max_attempts * 2
        $maxAttemptsForIp = $lockoutSettings['max_attempts_ip'] ?? ($lockoutSettings['max_attempts'] * 2);
        $lockoutInfo['is_ip_locked_out'] = $ipFailedAttempts >= $maxAttemptsForIp;

        return $lockoutInfo;
    }

    /**
     * Get remaining time (in minutes) until lockout release
     */
    public static function getLockoutRemainingMinutes(string $identifier, int $lockoutDuration): ?int
    {
        $lastFailedAttempt = MemberLoginAttempt::getLastFailedAttempt($identifier);
        if (! $lastFailedAttempt) {
            return null;
        }

        $lockoutUntil = $lastFailedAttempt->copy()->addMinutes($lockoutDuration);
        $now = Carbon::now();

        if ($now->greaterThanOrEqualTo($lockoutUntil)) {
            return 0; // Lockout period has ended
        }

        // Calculate in seconds and round up to minutes
        $remainingSeconds = $now->diffInSeconds($lockoutUntil);

        return (int) ceil($remainingSeconds / 60);
    }

    /**
     * Send lockout notification
     *
     * @return bool Returns true on success, false on failure
     */
    public static function sendLockoutNotification(string $identifier, Request $request, array $settings): bool
    {
        try {
            // Get notification email address
            $notificationEmail = \App\Models\SiteSetting::getValue('notification_email');

            if (empty($notificationEmail)) {
                Log::warning('Lockout notification: administrator email address is not configured');

                return false;
            }

            // Check if mail server is configured
            if (! \App\Services\MailServerValidatorService::isMailServerTested()) {
                Log::warning('Lockout notification: mail server is not configured');

                return false;
            }

            $subject = __('mail.lockout.subject');
            $details = [
                'identifier' => $identifier,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => Carbon::now()->format('Y-m-d H:i:s'),
                'max_attempts' => $settings['max_attempts'],
                'time_window' => $settings['time_window'],
                'lockout_duration' => $settings['lockout_duration'],
            ];

            // Send email using Mailable class
            $lockoutMail = new \App\Mail\LockoutNotificationMail($details);

            \Mail::to($notificationEmail)->send($lockoutMail);

            Log::info('Lockout notification sent', [
                'identifier' => $identifier,
                'ip_address' => $request->ip(),
                'notification_email' => $notificationEmail,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send lockout notification', [
                'identifier' => $identifier,
                'ip_address' => $request->ip(),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Clean up expired login attempt records
     *
     * @param  int  $days  Retention days (default: 30 days)
     * @return int Number of deleted records
     */
    public static function cleanupExpiredAttempts(int $days = 30): int
    {
        $cutoffDate = Carbon::now()->subDays($days);

        return MemberLoginAttempt::where('created_at', '<', $cutoffDate)->delete();
    }

    /**
     * Clear failure records for specified identifier
     */
    public static function clearFailedAttempts(string $identifier): void
    {
        MemberLoginAttempt::clearFailedAttempts($identifier);
    }

    /**
     * Clear all failure records
     *
     * @return int Number of deleted records
     */
    public static function clearAllFailedAttempts(): int
    {
        return MemberLoginAttempt::where('successful', false)->delete();
    }

    /**
     * Get detailed information about lockout status
     *
     * @param  array  $settings  Settings array
     * @param  mixed  $settingSource  Settings source
     */
    public static function getLockoutStatusDetails(
        string $identifier,
        string $ipAddress,
        array $settings = [],
        $settingSource = null
    ): array {
        $lockoutSettings = static::getLockoutSettings($settings, $settingSource);

        $failedAttempts = MemberLoginAttempt::getFailedAttemptsCount(
            $identifier,
            $lockoutSettings['time_window']
        );

        $ipFailedAttempts = MemberLoginAttempt::getFailedAttemptsCountByIp(
            $ipAddress,
            $lockoutSettings['time_window']
        );

        return [
            'is_enabled' => $lockoutSettings['enabled'],
            'is_locked_out' => $failedAttempts >= $lockoutSettings['max_attempts'],
            'is_ip_locked_out' => $ipFailedAttempts >= ($lockoutSettings['max_attempts'] * 2),
            'failed_attempts' => $failedAttempts,
            'ip_failed_attempts' => $ipFailedAttempts,
            'remaining_attempts' => max(0, $lockoutSettings['max_attempts'] - $failedAttempts),
            'remaining_minutes' => static::getLockoutRemainingMinutes($identifier, $lockoutSettings['lockout_duration']),
            'settings' => $lockoutSettings,
        ];
    }

    /**
     * Generate lockout error message
     *
     * @param  string  $context  Context (admin, user, etc.)
     */
    public static function generateLockoutMessage(array $lockoutInfo, string $context = 'admin'): string
    {
        if ($lockoutInfo['is_ip_locked_out']) {
            return __("auth.lockout.ip_locked_out.{$context}");
        }

        if ($lockoutInfo['is_locked_out']) {
            $minutes = $lockoutInfo['lockout_minutes'] ?? 0;

            return __("auth.lockout.account_locked_out.{$context}", ['minutes' => $minutes]);
        }

        if (isset($lockoutInfo['remaining_attempts']) && $lockoutInfo['remaining_attempts'] > 0) {
            return __("auth.lockout.remaining_attempts.{$context}", [
                'attempts' => $lockoutInfo['remaining_attempts'],
            ]);
        }

        return __("auth.failed.{$context}");
    }
}
