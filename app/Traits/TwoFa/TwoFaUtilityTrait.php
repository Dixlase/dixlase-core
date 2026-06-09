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

namespace App\Traits\TwoFa;

use App\Enums\AuthenticationMode;
use App\Enums\TwoFaMethod;
use App\Services\TwoFa\TwoFaPasskeyService;
use App\Traits\DeviceDetectionTrait;

/**
 * @internal For Core use only. Do not reference from plugins/themes
 *
 * Trait that provides low-level utility functions for two-factor authentication
 *
 * Provides basic two-factor authentication functionality such as
 * code generation/verification, settings retrieval, and validation logic
 */
trait TwoFaUtilityTrait
{
    use DeviceDetectionTrait;

    /**
     * Generate two-factor authentication code and save to database
     *
     * @param  mixed  $user  User model
     * @param  int  $expireMinutes  Expiration time (minutes)
     * @return string Generated code
     */
    public function generateTwoFaCode($user, ?int $expireMinutes = null): string
    {
        $codeService = app(\App\Services\TwoFa\TwoFaCodeService::class);

        return $codeService->generate($user, $expireMinutes);
    }

    /**
     * Verify two-factor authentication code
     *
     * @param  mixed  $user  User model
     * @param  string  $inputCode  Entered code
     * @return bool Verification result
     */
    public function validateTwoFaCode($user, string $inputCode): bool
    {
        $codeService = app(\App\Services\TwoFa\TwoFaCodeService::class);

        return $codeService->validate($user, $inputCode);
    }

    /**
     * Get valid authentication methods
     *
     * @param  string  $settingsKey  Settings key
     * @param  array  $defaultMethods  Default authentication method
     * @return array Array of valid authentication methods
     */
    public function getEnabledTwoFaMethods(string $settingsKey = 'enabled_two_fa_methods', ?array $defaultMethods = null): array
    {
        if ($defaultMethods === null) {
            $defaultMethods = [TwoFaMethod::EMAIL->value];
        }

        $settingsValue = $this->getSettingValue($settingsKey, json_encode($defaultMethods));

        // Handle cases where settings value is not a string (e.g., integer)
        if (! is_string($settingsValue)) {
            return $defaultMethods;
        }

        $decoded = json_decode($settingsValue, true);

        // Return default if JSON decode fails or result is not an array
        if (! is_array($decoded)) {
            return $defaultMethods;
        }

        return $decoded;
    }

    /**
     * Determine whether two-factor authentication is required
     *
     * @param  mixed  $user  User model
     * @param  int  $forceSetting  System settings for enforced 2FA
     * @param  array  $enabledMethods  Valid authentication methods
     * @return bool Whether 2FA is required
     */
    public function requiresTwoFa($user, int $forceSetting, array $enabledMethods): bool
    {
        // Disable 2FA if there are no valid authentication methods
        if (empty($enabledMethods)) {
            return false;
        }

        // Check if 2FA is required based on current settings
        $modeValue = $this->getEffectiveTwoFaMode($user, $forceSetting);
        $mode = AuthenticationMode::tryFrom($modeValue);

        // Always require 2FA when Passkey is enabled
        if (in_array(TwoFaMethod::PASSKEY->value, $enabledMethods)) {
            return true;
        }

        return match ($mode) {
            AuthenticationMode::Always => true,
            default => false,
        };
    }

    /**
     * Retrieve the active two-factor authentication mode
     *
     * @param  mixed  $user  User model
     * @param  int  $forceSetting  System settings for enforced 2FA
     * @return int Active 2FA mode
     */
    protected function getEffectiveTwoFaMode($user, int $forceSetting): int
    {
        // When 2FA is disabled in system settings
        if ($forceSetting === AuthenticationMode::Disabled->value) {
            return AuthenticationMode::Disabled->value;
        }

        // When forced by system settings
        if ($forceSetting === AuthenticationMode::Always->value) {
            return AuthenticationMode::Always->value;
        }

        // When using profile settings
        if ($forceSetting === AuthenticationMode::UseProfileSetting->value) {
            return $this->checkUserTwoFaSetting($user);
        }

        return AuthenticationMode::Disabled->value;
    }

    /**
     * Check user's personal 2FA settings
     *
     * @param  mixed  $user  User model
     * @return int 2FA mode
     */
    protected function checkUserTwoFaSetting($user): int
    {
        $mode = $user->two_fa_mode;

        // Disabled by default if null
        if ($mode === null) {
            return AuthenticationMode::Disabled->value;
        }

        return match ($mode) {
            AuthenticationMode::Always => AuthenticationMode::Always->value,
            default => AuthenticationMode::Disabled->value,
        };
    }

    /**
     * Check if accessed from a trusted device
     *
     * @param  mixed  $user  User model
     * @return bool True if trusted device
     */
    protected function isFromTrustedDevice($user): bool
    {
        $passkeyService = new TwoFaPasskeyService();

        return $passkeyService->isTrustedDevice($user);
    }

    /**
     * Retrieve settings value (implemented in child class)
     *
     * @param  string  $key  Settings key
     * @param  mixed  $default  Default value
     * @return mixed Settings value
     */
    abstract protected function getSettingValue(string $key, $default = null);
}
