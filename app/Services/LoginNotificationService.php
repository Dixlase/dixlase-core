<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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

namespace App\Services;

use App\Enums\AuthenticationMode;
use App\Traits\DeviceDetectionTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * @internal Core only. Do not reference from plugins/themes
 *
 * Login notification service
 *
 * Provides common login notification functionality that can be used for both member and user management
 * Settings retrieval is done by the caller, this service only provides notification logic
 */
class LoginNotificationService
{
    use DeviceDetectionTrait;

    /**
     * Process login notification
     *
     * @param  Model  $user  User model (Member or User)
     * @param  Request  $request  Request
     * @param  callable  $getGlobalSetting  Global settings retrieval function
     * @param  string  $notificationClass  Notification class name
     * @param  string  $context  Context name for logging
     */
    public function handle(
        Model $user,
        Request $request,
        callable $getGlobalSetting,
        string $notificationClass,
        string $context = 'Login notification'
    ): void {
        // Prepare login detail data
        $loginDetails = $this->prepareLoginDetails($request);

        // Check email sending availability
        if (! $this->canSendNotification($user, $context)) {
            Log::info($context.' skipped: Mail sending not available');
            // Record login information but do not send notification
            $this->recordLoginInfo($user, $request);

            return;
        }

        // Skip notification if IP/UA cannot be obtained
        $ip = $request->ip();
        $userAgent = $request->userAgent();

        if (! $ip || ! $userAgent) {
            Log::warning($context.' skipped: IP or User-Agent not available', [
                'ip' => $ip,
                'user_agent' => $userAgent,
                'user_id' => $user->id,
            ]);
            // Record login information but do not send notification
            $this->recordLoginInfo($user, $request);

            return;
        }

        // Determine notification sending
        $isDifferentDevice = $this->isNewDevice($user, $ip, $userAgent);
        $shouldSendUser = $this->shouldSendUserNotification(
            $user,
            $ip,
            $userAgent,
            $getGlobalSetting
        );

        Log::info($context.' decision', [
            'user_email' => $user->email ?? 'N/A',
            'user_id' => $user->id,
            'isDifferentDevice' => $isDifferentDevice,
            'shouldSendUser' => $shouldSendUser,
        ]);

        // Send user notification
        if ($shouldSendUser) {
            Log::info('Sending '.$context, [
                'user_email' => $user->email ?? 'N/A',
                'user_id' => $user->id,
            ]);
            $user->notify(new $notificationClass($loginDetails, false));
        } else {
            Log::info($context.' not sent', [
                'user_email' => $user->email ?? 'N/A',
                'user_id' => $user->id,
                'reason' => 'shouldSendUser is false',
            ]);
        }

        // Record login information
        $this->recordLoginInfo($user, $request);
    }

    /**
     * Record login information
     */
    protected function recordLoginInfo(Model $user, Request $request): void
    {
        $user->last_login_ip = $request->ip();
        $user->last_login_ua = $request->userAgent();
        $user->last_login_at = now();
        $user->save();
    }

    /**
     * Prepare login detail data
     */
    protected function prepareLoginDetails(Request $request): array
    {
        return [
            'datetime' => now()->format('Y-m-d H:i:s'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];
    }

    /**
     * Check if email sending is possible and output log if not
     */
    protected function canSendNotification(Model $user, string $context = 'Login notification'): bool
    {
        if (! MailServerValidatorService::canSendMail()) {
            Log::info($context.' skipped: '.MailServerValidatorService::getMailDisabledReason(), [
                'user_id' => $user->id,
                'user_type' => get_class($user),
                'ip' => request()->ip(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Determine whether to send user notification
     */
    protected function shouldSendUserNotification(
        Model $user,
        string $ip,
        string $ua,
        callable $getGlobalSetting
    ): bool {
        $globalSetting = $getGlobalSetting();
        $globalMode = AuthenticationMode::tryFrom((int) $globalSetting);

        return match ($globalMode) {
            AuthenticationMode::Disabled => false,
            AuthenticationMode::Always => true,
            AuthenticationMode::DifferentDevice => $this->isNewDevice($user, $ip, $ua),
            AuthenticationMode::UseProfileSetting => $this->shouldSendBasedOnProfile($user, $ip, $ua),
            default => false,
        };
    }

    /**
     * Determine whether to send notification based on profile settings
     */
    protected function shouldSendBasedOnProfile(Model $user, string $ip, string $ua): bool
    {
        $profileValue = $user->login_notification_mode ?? 0;
        // Get value if Enum object, use as-is if integer
        $profileValueInt = $profileValue instanceof AuthenticationMode ? $profileValue->value : $profileValue;
        $profileMode = $this->mapProfileValueToEnum($profileValueInt);

        Log::info('Profile-based notification decision', [
            'user_email' => $user->email,
            'profile_value' => $profileValue,
            'profile_mode' => $profileMode->name,
            'profile_mode_value' => $profileMode->value,
        ]);

        $result = match ($profileMode) {
            AuthenticationMode::Disabled => false,
            AuthenticationMode::Always => true,
            AuthenticationMode::DifferentDevice => $this->isNewDevice($user, $ip, $ua),
            default => false,
        };

        Log::info('Profile notification result', [
            'user_email' => $user->email,
            'should_send' => $result,
        ]);

        return $result;
    }

    /**
     * Map profile settings value to AuthenticationMode enum
     */
    protected function mapProfileValueToEnum(int $profileValue): AuthenticationMode
    {
        return match ($profileValue) {
            0 => AuthenticationMode::Disabled,
            1 => AuthenticationMode::DifferentDevice,
            2 => AuthenticationMode::Always,
            default => AuthenticationMode::Disabled,
        };
    }
}
