<?php

/**
 * This file is part of MySoftware.
 *
 * Copyright (C) 2025 exc-D inc.
 * https://exc-d.com
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

use App\Models\MemberSetting;
use App\Services\MailServerValidatorService;

trait PasswordResetTrait
{
    /**
     * Check if password reset is enabled and mail server is configured
     *
     * @param string $settingKey The setting key for password reset enabled (default: 'password_reset_enabled')
     * @return bool
     */
    protected function isPasswordResetAvailable(string $settingKey = 'password_reset_enabled'): bool
    {
        $passwordResetEnabled = (bool) MemberSetting::getValue($settingKey, true);
        return $passwordResetEnabled && MailServerValidatorService::canSendMail();
    }

    /**
     * Get password requirements from settings
     *
     * @return array
     */
    protected function getPasswordRequirements(): array
    {
        return [
            'passwordMinLength' => (int) MemberSetting::getValue('password_min_length', 8),
            'passwordRequireUppercase' => (bool) MemberSetting::getValue('password_require_uppercase', true),
            'passwordRequireSymbol' => (bool) MemberSetting::getValue('password_require_symbol', false),
        ];
    }

    /**
     * Abort with 404 if password reset is not available
     *
     * @param string $settingKey The setting key for password reset enabled
     * @return void
     */
    protected function abortIfPasswordResetUnavailable(string $settingKey = 'password_reset_enabled'): void
    {
        if (!$this->isPasswordResetAvailable($settingKey)) {
            abort(404);
        }
    }

    /**
     * Get common validation rules for password reset
     *
     * @return array
     */
    protected function getPasswordResetValidationRules(): array
    {
        return [
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
        ];
    }

    /**
     * Get validation rules for password reset link request
     *
     * @return array
     */
    protected function getPasswordResetLinkValidationRules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }
}
