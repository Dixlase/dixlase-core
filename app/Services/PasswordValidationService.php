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

namespace App\Services;

use Illuminate\Validation\Rules\Password;
use App\Rules\NotPwnedPassword;
use App\Services\MailServerValidatorService;

/**
 * パスワードバリデーションサービス
 * 
 * メンバー管理とユーザー管理の両方で使用できる共通のパスワードバリデーション機能を提供
 * 設定の取得は呼び出し側で行い、このサービスはバリデーションロジックのみを提供
 */
class PasswordValidationService
{
    /**
     * パスワードバリデーションルールを構築
     * 
     * @param int $minLength 最小文字数
     * @param bool $requireUppercase 大文字を必須にするか
     * @param bool $requireLowercase 小文字を必須にするか
     * @param bool $requireNumber 数字を必須にするか
     * @param bool $requireSymbol 記号を必須にするか
     * @param bool $isRequired パスワード入力を必須にするか
     * @param bool $checkPwned 漏洩パスワードチェックを行うか
     * @return array バリデーションルール配列
     */
    public static function buildPasswordRules(
        int $minLength,
        bool $requireUppercase,
        bool $requireLowercase,
        bool $requireNumber,
        bool $requireSymbol,
        bool $isRequired = true,
        bool $checkPwned = false
    ): array {
        $rules = $isRequired ? ['required'] : ['nullable'];
        
        // Laravelのパスワードルールビルダーを使用
        $passwordRule = Password::min($minLength);

        // 大文字と小文字の両方が必須の場合はmixedCaseを使用
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

        // 漏洩パスワードチェック
        if ($checkPwned) {
            $rules[] = new NotPwnedPassword();
        }

        return $rules;
    }

    /**
     * パスワード要件の説明文を生成
     * 
     * @param int $minLength 最小文字数
     * @param bool $requireUppercase 大文字・小文字の混在を必須にするか
     * @param bool $requireNumber 数字を必須にするか
     * @param bool $requireSymbol 記号を必須にするか
     * @param string $locale ロケール（'ja' または 'en'）
     * @return string パスワード要件の説明文
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
     * パスワードリセットが利用可能かチェック
     * 
     * @param callable $settingGetter 設定取得用のコールバック関数
     * @return bool
     */
    public static function isPasswordResetAvailable(callable $settingGetter): bool
    {
        $passwordResetEnabled = (bool) $settingGetter('password_reset_enabled', true);
        return $passwordResetEnabled && MailServerValidatorService::canSendMail();
    }

    /**
     * パスワードリセットが利用不可の場合404エラーを返す
     * 
     * @param callable $settingGetter 設定取得用のコールバック関数
     * @return void
     */
    public static function abortIfPasswordResetUnavailable(callable $settingGetter): void
    {
        if (!self::isPasswordResetAvailable($settingGetter)) {
            abort(404);
        }
    }

    /**
     * パスワードリセット用のバリデーションルールを取得
     * 
     * @param int $minLength 最小文字数
     * @param bool $requireUppercase 大文字・小文字の混在を必須にするか
     * @param bool $requireNumber 数字を必須にするか
     * @param bool $requireSymbol 記号を必須にするか
     * @param bool $checkPwned 漏洩パスワードチェックを行うか
     * @return array
     */
    public static function getPasswordResetValidationRules(
        int $minLength,
        bool $requireUppercase,
        bool $requireNumber,
        bool $requireSymbol,
        bool $checkPwned = false
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
                true, // isRequired
                $checkPwned
            ),
        ];
    }

    /**
     * パスワードリセットリンク送信用のバリデーションルールを取得
     *
     * @return array
     */
    public static function getPasswordResetLinkValidationRules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }
}
