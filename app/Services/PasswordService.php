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

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Rules\NotPwnedPassword;
use App\Services\MailServerValidatorService;

/**
 * パスワードサービス
 * 
 * パスワードに関する包括的な機能を提供：
 * - ハッシュ化・検証
 * - バリデーションルール構築
 * - パスワードリセット関連機能
 * 
 * メンバー管理とユーザー管理の両方で使用可能
 */
class PasswordService
{
    // =================================================================
    // ハッシュ化・検証関連
    // =================================================================

    /**
     * パスワードをハッシュ化（文字列を直接ハッシュ化）
     *
     * @param string $password 平文パスワード
     * @return string ハッシュ化されたパスワード
     */
    public static function hash(string $password): string
    {
        return Hash::make($password);
    }

    /**
     * パスワードをハッシュ化（配列内のパスワードフィールドを処理）
     * 
     * パスワードが空の場合は配列から削除します。
     * パスワードが存在する場合はハッシュ化します。
     *
     * @param array &$data パスワードフィールドを含む配列（参照渡し）
     * @param string $field パスワードフィールド名（デフォルト: 'password'）
     * @return void
     */
    public static function hashPasswordIfPresent(array &$data, string $field = 'password'): void
    {
        if (!empty($data[$field])) {
            $data[$field] = Hash::make($data[$field]);
        } else {
            unset($data[$field]);
        }
    }

    /**
     * パスワードを検証
     *
     * @param string $password 平文パスワード
     * @param string $hashedPassword ハッシュ化されたパスワード
     * @return bool
     */
    public static function verify(string $password, string $hashedPassword): bool
    {
        return Hash::check($password, $hashedPassword);
    }

    /**
     * パスワードの再ハッシュ化が必要かチェック
     *
     * @param string $hashedPassword ハッシュ化されたパスワード
     * @return bool
     */
    public static function needsRehash(string $hashedPassword): bool
    {
        return Hash::needsRehash($hashedPassword);
    }

    // =================================================================
    // バリデーション関連
    // =================================================================

    /**
     * パスワードバリデーションルールを構築
     * 
     * 漏洩パスワードチェックはセキュリティ設定から自動的に取得されます
     * 
     * @param int $minLength 最小文字数
     * @param bool $requireUppercase 大文字を必須にするか
     * @param bool $requireLowercase 小文字を必須にするか
     * @param bool $requireNumber 数字を必須にするか
     * @param bool $requireSymbol 記号を必須にするか
     * @param bool $isRequired パスワード入力を必須にするか
     * @return array バリデーションルール配列
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

        // 漏洩パスワードチェック（セキュリティ設定から自動取得）
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
     * 漏洩パスワードチェックはセキュリティ設定から自動的に取得されます
     * 
     * @param int $minLength 最小文字数
     * @param bool $requireUppercase 大文字・小文字の混在を必須にするか
     * @param bool $requireNumber 数字を必須にするか
     * @param bool $requireSymbol 記号を必須にするか
     * @return array
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
