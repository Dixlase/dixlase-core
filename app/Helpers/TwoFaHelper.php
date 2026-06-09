<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

use App\Enums\AuthenticationMode;
use App\Enums\TwoFaMethod;
use App\Models\SecuritySetting;
use App\Services\TwoFa\TwoFaPasskeyService;
use App\Traits\TwoFa\TwoFaUtilityTrait;
use Illuminate\Support\Facades\Log;

class TwoFaHelper
{
    use TwoFaUtilityTrait;

    /**
     * Retrieve settings value (from SecuritySetting)
     *
     * @param  string  $key  Settings key
     * @param  mixed  $default  Default value
     * @return mixed Settings value
     */
    protected function getSettingValue(string $key, $default = null)
    {
        return SecuritySetting::getValue($key, $default);
    }

    /**
     * Check if email settings are configured
     * Check DB (admin panel) settings with priority
     *
     * @return bool Whether email settings are configured
     */
    public function isMailConfigured(): bool
    {
        $mailer = ConfigHelper::getMailMailer();

        // If email settings do not exist
        if (! $mailer) {
            return false;
        }

        // For SMTP, check required settings
        if ($mailer === 'smtp') {
            $host = ConfigHelper::getMailHost();
            $port = ConfigHelper::getMailPort();

            if (empty($host) || $port <= 0) {
                return false;
            }
        }

        // Check if sender address is configured
        $fromAddress = ConfigHelper::getMailFromAddress();
        if (empty($fromAddress) || $fromAddress === 'hello@example.com') {
            return false;
        }

        return true;
    }

    /**
     * Generate two-factor authentication code and send email
     *
     * @param  mixed  $user  User model
     * @param  string  $mailClass  Mail class name
     * @param  int  $expireMinutes  Expiration time (minutes)
     * @param  string  $context  Context (admin, user, etc.)
     * @return string Generated code
     *
     * @throws \Exception If email settings are not configured
     */
    public function generateAndSendCode($user, string $mailClass, ?int $expireMinutes = null, string $context = 'admin'): string
    {
        $codeService = app(\App\Services\TwoFa\TwoFaCodeService::class);

        return $codeService->generateAndSend($user, $mailClass, $expireMinutes, $context);
    }

    /**
     * Retrieve two-factor authentication settings (member or user)
     *
     * @param  string|null  $settingModelClass  Settings model class name (null=SecuritySetting)
     */
    public function getTwoFaSettings(?string $settingModelClass = null): array
    {
        $settingModelClass = $settingModelClass ?? \App\Models\SecuritySetting::class;

        return [
            'two_fa_mode' => (int) $settingModelClass::getValue('two_fa_mode', '0'),
            'enabled_methods' => $this->getEnabledTwoFaMethods($settingModelClass),
            'default_method' => (int) $settingModelClass::getValue('default_two_fa_method', (string) TwoFaMethod::EMAIL->value),
        ];
    }

    /**
     * Retrieve valid two-factor authentication methods (based on global settings)
     *
     * @param  string|null  $settingModelClass  Settings model class name (null=SecuritySetting)
     */
    public function getEnabledTwoFaMethods(?string $settingModelClass = null): array
    {
        $settingModelClass = $settingModelClass ?? \App\Models\SecuritySetting::class;
        $methods = [];

        // Email authentication (always enabled)
        $methods[] = TwoFaMethod::EMAIL->value;

        // Passkey authentication (enabled if two_fa_passkey_mode is not 0)
        $passkeyMode = (int) $settingModelClass::getValue('two_fa_passkey_mode', '2');
        if ($passkeyMode > 0) {
            $methods[] = TwoFaMethod::PASSKEY->value;
        }

        return $methods;
    }

    /**
     * Get available two-factor authentication methods for a specific member
     * Exclude Passkey if no Passkey device is registered
     *
     * @param  mixed  $member  Member model
     * @param  string|null  $settingModelClass  Settings model class name (null=SecuritySetting)
     */
    public function getAvailableTwoFaMethodsForMember($member, ?string $settingModelClass = null): array
    {
        $methods = $this->getEnabledTwoFaMethods($settingModelClass);

        // If Passkey is enabled, check if a device is registered
        if (in_array(TwoFaMethod::PASSKEY->value, $methods)) {
            $passkeyService = app(TwoFaPasskeyService::class);
            $devices = $passkeyService->getDevices($member);

            // Exclude Passkey if no device is registered
            if ($devices->isEmpty()) {
                $methods = array_values(array_filter($methods, function ($method) {
                    return $method !== TwoFaMethod::PASSKEY->value;
                }));
            }
        }

        return $methods;
    }

    /**
     * Determine if two-factor authentication is enabled
     *
     * @param  mixed  $user  User model
     * @param  string|null  $settingModelClass  Settings model class name (null=SecuritySetting)
     */
    public function isTwoFaEnabled($user, ?string $settingModelClass = null): bool
    {
        // Disable two-factor authentication if email settings are incomplete
        if (! $this->isMailConfigured()) {
            Log::warning('[2FA] Email settings are incomplete; disabling two-factor authentication');

            return false;
        }

        $systemSettings = $this->getTwoFaSettings($settingModelClass);
        $twoFaMode = $systemSettings['two_fa_mode'] ?? 0;

        Log::info('[2FA] isTwoFaEnabled check', [
            'user_id' => $user->id,
            'setting_class' => $settingModelClass,
            'two_fa_mode' => $twoFaMode,
            'user_two_fa_mode' => $user->two_fa_mode ?? null,
        ]);

        // Global settings: 0=Disabled, 1=DifferentDevice, 2=Always, 3=UseProfileSetting

        // If disabled
        if ($twoFaMode === AuthenticationMode::Disabled->value) {
            Log::info('[2FA] Disabled by global setting');

            return false;
        }

        // If always enabled in global settings
        if ($twoFaMode === AuthenticationMode::Always->value) {
            Log::info('[2FA] Always enabled by global setting');

            return true;
        }

        // If global settings is "DifferentDevice"
        if ($twoFaMode === AuthenticationMode::DifferentDevice->value) {
            $isDifferent = $this->isDifferentEnvironment($user);
            Log::info('[2FA] DifferentDevice mode', ['is_different' => $isDifferent]);

            return $isDifferent;
        }

        // If using profile settings (two_fa_mode = UseProfileSetting)
        $userMode = $user->two_fa_mode;

        // If AuthenticationMode Enum
        if ($userMode instanceof \App\Enums\AuthenticationMode) {
            $userModeValue = $userMode->value;
        } else {
            // If integer value
            $userModeValue = (int) $userMode;
        }

        Log::info('[2FA] Using user profile setting', ['user_mode_value' => $userModeValue]);

        // If user settings is disabled
        if ($userModeValue === AuthenticationMode::Disabled->value) {
            Log::info('[2FA] Disabled by user setting');

            return false;
        }

        // If user settings is always enabled
        if ($userModeValue === AuthenticationMode::Always->value) {
            Log::info('[2FA] Always enabled by user setting');

            return true;
        }

        // If user settings is "DifferentDevice"
        if ($userModeValue === AuthenticationMode::DifferentDevice->value) {
            $isDifferent = $this->isDifferentEnvironment($user);
            Log::info('[2FA] DifferentDevice mode by user setting', ['is_different' => $isDifferent]);

            return $isDifferent;
        }

        Log::info('[2FA] No condition matched, returning false');

        return false;
    }

    /**
     * Determine authentication method to use
     *
     * @param  mixed  $user  User model
     * @param  string|null  $settingModelClass  Settings model class name (null=SecuritySetting)
     * @return int Authentication method
     */
    public function getEffectiveAuthMethod($user, ?string $settingModelClass = null): int
    {
        $settingModelClass = $settingModelClass ?? \App\Models\SecuritySetting::class;
        $userMethod = $user->two_fa_default_method ?? null;
        $defaultMethod = (int) $settingModelClass::getValue('default_two_fa_method', (string) TwoFaMethod::EMAIL->value);
        $enabledMethods = $this->getEnabledTwoFaMethods($settingModelClass);
        $globalTwoFaMode = (int) $settingModelClass::getValue('force_two_fa', (string) AuthenticationMode::Disabled->value);

        // Exclude passkey from valid methods if user has disabled passkey
        $userPasskeyEnabled = $user->two_fa_passkey_enabled ?? true;
        $userEnabledMethods = $enabledMethods;
        if (! $userPasskeyEnabled) {
            $userEnabledMethods = array_values(array_filter($enabledMethods, function ($method) {
                return $method !== TwoFaMethod::PASSKEY->value;
            }));
        }

        // Exclude passkey from valid methods if no passkey device is registered
        if (in_array(TwoFaMethod::PASSKEY->value, $userEnabledMethods)) {
            $passkeyService = app(TwoFaPasskeyService::class);
            $devices = $passkeyService->getDevices($user);

            if ($devices->isEmpty()) {
                $userEnabledMethods = array_values(array_filter($userEnabledMethods, function ($method) {
                    return $method !== TwoFaMethod::PASSKEY->value;
                }));
            }
        }

        Log::info('[2FA] getEffectiveAuthMethod', [
            'user_id' => $user->id,
            'user_method' => $userMethod,
            'default_method' => $defaultMethod,
            'global_mode' => $globalTwoFaMode,
            'enabled_methods' => $enabledMethods,
            'user_passkey_enabled' => $userPasskeyEnabled,
            'user_enabled_methods' => $userEnabledMethods,
        ]);

        // Prioritize explicitly set authentication method if user has configured one
        if ($userMethod !== null && in_array((int) $userMethod, $userEnabledMethods, true)) {
            Log::info('[2FA] Using user method', ['method' => (int) $userMethod]);

            return (int) $userMethod;
        }

        // Use global default authentication method if user settings are not configured
        if (in_array($defaultMethod, $userEnabledMethods, true)) {
            Log::info('[2FA] Using global default method', ['method' => $defaultMethod]);

            return $defaultMethod;
        }

        // Use the first valid method if the default method is not valid
        $fallbackMethod = ! empty($userEnabledMethods) ? $userEnabledMethods[0] : TwoFaMethod::EMAIL->value;
        Log::info('[2FA] Using fallback method', ['method' => $fallbackMethod]);

        return $fallbackMethod;
    }

    /**
     * Get mail class name based on authentication method
     *
     * @param  int  $method  Authentication method
     * @param  string  $context  Context (admin, user, etc.)
     * @return string Mail class name
     */
    public function getMailClassForMethod(int $method, string $context = 'admin'): string
    {
        $contextPrefix = ucfirst($context);

        return match ($method) {
            TwoFaMethod::EMAIL->value => "App\\Mail\\{$contextPrefix}TwoFactorLoginCodeMail",
            TwoFaMethod::DEVICE->value => "App\\Mail\\{$contextPrefix}TwoFactorDeviceVerificationMail",
            TwoFaMethod::BIOMETRIC->value => "App\\Mail\\{$contextPrefix}TwoFactorBiometricMail",
            default => "App\\Mail\\{$contextPrefix}TwoFactorLoginCodeMail",
        };
    }

    /**
     * Get two-factor authentication statistics
     *
     * @return array Statistics
     */
    public function getTwoFaStats(): array
    {
        // Example implementation: add actual statistics retrieval logic
        return [
            'total_users_with_two_fa' => 0,
            'active_tokens' => 0,
            'failed_attempts_today' => 0,
        ];
    }

    /**
     * Clean up expired tokens
     *
     * @return int Number of deleted tokens
     */
    public function cleanupExpiredTokens(): int
    {
        $codeService = app(\App\Services\TwoFa\TwoFaCodeService::class);

        return $codeService->cleanupExpired();
    }

    /**
     * Get route name based on authentication method
     *
     * @param  string  $prefix  Route prefix (e.g., 'admin', 'dixlase-users::mypage')
     * @param  int  $method  Authentication method (TwoFaMethod enum value)
     * @return string Route name (e.g., 'admin.two-fa.email.show')
     */
    public static function getTwoFaMethodRoute(string $prefix, int $method): string
    {
        return match ($method) {
            TwoFaMethod::EMAIL->value => "{$prefix}.two-fa.email.show",
            default => "{$prefix}.two-fa.email.show",
        };
    }
}
