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

use App\Rules\NotPwnedPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Password Service
 *
 * Provides comprehensive password functionality:
 * - Hashing and verification
 * - Validation rule construction
 * - Password reset related features
 *
 * Can be used for both member management and user management
 */
class PasswordService
{
    // =================================================================
    // Hashing and verification related
    // =================================================================

    /**
     * Hash password (directly hash string)
     *
     * @param  string  $password  Plain text password
     * @return string Hashed password
     */
    public static function hash(string $password): string
    {
        return Hash::make($password);
    }

    /**
     * Hash password (process password field in array)
     *
     * If password is empty, remove it from the array.
     * If password exists, hash it.
     *
     * @param  array  &$data  Array containing password fields (passed by reference)
     * @param  string  $field  Password field name (default: 'password')
     */
    public static function hashPasswordIfPresent(array &$data, string $field = 'password'): void
    {
        if (! empty($data[$field])) {
            $data[$field] = Hash::make($data[$field]);
        } else {
            unset($data[$field]);
        }
    }

    /**
     * Verify password
     *
     * @param  string  $password  Plain text password
     * @param  string  $hashedPassword  Hashed password
     */
    public static function verify(string $password, string $hashedPassword): bool
    {
        return Hash::check($password, $hashedPassword);
    }

    /**
     * Check if password needs rehashing
     *
     * @param  string  $hashedPassword  Hashed password
     */
    public static function needsRehash(string $hashedPassword): bool
    {
        return Hash::needsRehash($hashedPassword);
    }

    // =================================================================
    // Validation related
    // =================================================================

    /**
     * Build password validation rules
     *
     * Compromised password check is automatically retrieved from security settings
     *
     * @param  int  $minLength  Minimum character count
     * @param  bool  $requireUppercase  Whether to require uppercase letters
     * @param  bool  $requireLowercase  Whether to require lowercase letters
     * @param  bool  $requireNumber  Whether to require numbers
     * @param  bool  $requireSymbol  Whether to require symbols
     * @param  bool  $isRequired  Whether to require password input
     * @return array Validation rules array
     */
    public static function buildPasswordRules(
        int $minLength,
        bool $requireUppercase,
        bool $requireLowercase,
        bool $requireNumber,
        bool $requireSymbol,
        bool $isRequired = true
    ): array {
        $rules = $isRequired ? ['required'] : ['nullable'];

        // Use Laravel's password rule builder
        $passwordRule = Password::min($minLength);

        // Use mixedCase if both uppercase and lowercase are required
        if ($requireUppercase && $requireLowercase) {
            $passwordRule->mixedCase();
        } elseif ($requireUppercase) {
            $passwordRule->letters()->uncompromised();
        } elseif ($requireLowercase) {
            $passwordRule->letters();
        }

        if ($requireNumber) {
            $passwordRule->numbers();
        }

        if ($requireSymbol) {
            $passwordRule->symbols();
        }

        $rules[] = $passwordRule;
        $rules[] = 'confirmed';

        // Compromised password check (automatically retrieved from security settings)
        $checkPwned = filter_var(
            \App\Models\SecuritySetting::get('pwned_password_check_enabled', false),
            FILTER_VALIDATE_BOOLEAN
        );

        if ($checkPwned) {
            $rules[] = new NotPwnedPassword();
        }

        return $rules;
    }

    /**
     * Generate password requirements description
     *
     * @param  int  $minLength  Minimum character count
     * @param  bool  $requireUppercase  Whether to require mixed case
     * @param  bool  $requireNumber  Whether to require numbers
     * @param  bool  $requireSymbol  Whether to require symbols
     * @param  string  $locale  Locale ('ja' or 'en')
     * @return string Password requirements description
     */
    public static function getPasswordRequirementsDescription(
        int $minLength,
        bool $requireUppercase,
        bool $requireNumber,
        bool $requireSymbol,
        string $locale = 'ja'
    ): string {
        $descriptions = [];

        $descriptions[] = __('validation.password_requirements.min_length', ['length' => $minLength]);

        if ($requireUppercase) {
            $descriptions[] = __('validation.password_requirements.mixed_case');
        }

        if ($requireNumber) {
            $descriptions[] = __('validation.password_requirements.numbers');
        }

        if ($requireSymbol) {
            $descriptions[] = __('validation.password_requirements.symbols');
        }

        return implode($locale === 'ja' ? '、' : ', ', $descriptions);
    }

    /**
     * Check if password reset is available
     *
     * @param  callable  $settingGetter  Callback function for retrieving settings
     */
    public static function isPasswordResetAvailable(callable $settingGetter): bool
    {
        $passwordResetEnabled = (bool) $settingGetter('password_reset_enabled', true);

        return $passwordResetEnabled && MailServerValidatorService::canSendMail();
    }

    /**
     * Return 404 error if password reset is unavailable
     *
     * @param  callable  $settingGetter  Callback function for retrieving settings
     */
    public static function abortIfPasswordResetUnavailable(callable $settingGetter): void
    {
        if (! self::isPasswordResetAvailable($settingGetter)) {
            abort(404);
        }
    }

    /**
     * Get validation rules for password reset
     *
     * Compromised password check is automatically retrieved from security settings
     *
     * @param  int  $minLength  Minimum character count
     * @param  bool  $requireUppercase  Whether to require mixed case
     * @param  bool  $requireNumber  Whether to require numbers
     * @param  bool  $requireSymbol  Whether to require symbols
     */
    public static function getPasswordResetValidationRules(
        int $minLength,
        bool $requireUppercase,
        bool $requireNumber,
        bool $requireSymbol
    ): array {
        return [
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => self::buildPasswordRules(
                $minLength,
                $requireUppercase,
                true, // requireLowercase - always required
                $requireNumber,
                $requireSymbol,
                true // isRequired
            ),
        ];
    }

    /**
     * Get validation rules for sending password reset link
     */
    public static function getPasswordResetLinkValidationRules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }
}
