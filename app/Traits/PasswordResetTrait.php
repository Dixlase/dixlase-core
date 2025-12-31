<?php

/**
 * This file is part of Dixlase.
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
use App\Services\PasswordValidationService;

trait PasswordResetTrait
{
    /**
     * Check if password reset is enabled and mail server is configured
     * 
     * @param callable $settingGetter 設定取得用のコールバック関数
     * @return bool
     */
    protected function isPasswordResetAvailable(callable $settingGetter): bool
    {
        $passwordResetEnabled = (bool) $settingGetter('password_reset_enabled', true);
        return $passwordResetEnabled && MailServerValidatorService::canSendMail();
    }

    /**
     * Abort with 404 if password reset is not available
     * 
     * @param callable $settingGetter 設定取得用のコールバック関数
     * @return void
     */
    protected function abortIfPasswordResetUnavailable(callable $settingGetter): void
    {
        if (!$this->isPasswordResetAvailable($settingGetter)) {
            abort(404);
        }
    }

    /**
     * Get common validation rules for password reset
     * 
     * @param int $minLength 最小文字数
     * @param bool $requireUppercase 大文字・小文字の混在を必須にするか
     * @param bool $requireNumber 数字を必須にするか
     * @param bool $requireSymbol 記号を必須にするか
     * @param bool $checkPwned 漏洩パスワードチェックを行うか
     * @return array
     */
    protected function getPasswordResetValidationRules(
        int $minLength,
        bool $requireUppercase,
        bool $requireNumber,
        bool $requireSymbol,
        bool $checkPwned = false
    ): array {
        return [
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => PasswordValidationService::buildPasswordRules(
                $minLength,
                $requireUppercase,
                $requireNumber,
                $requireSymbol,
                true,
                $checkPwned
            ),
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
