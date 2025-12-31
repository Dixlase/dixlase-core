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
     * @param bool $requireUppercase 大文字・小文字の混在を必須にするか
     * @param bool $requireNumber 数字を必須にするか
     * @param bool $requireSymbol 記号を必須にするか
     * @param bool $isRequired パスワード入力を必須にするか
     * @param bool $checkPwned 漏洩パスワードチェックを行うか
     * @return array バリデーションルール配列
     */
    public static function buildPasswordRules(
        int $minLength,
        bool $requireUppercase,
        bool $requireNumber,
        bool $requireSymbol,
        bool $isRequired = true,
        bool $checkPwned = false
    ): array {
        $rules = $isRequired ? ['required'] : ['nullable'];
        
        // Laravelのパスワードルールビルダーを使用
        $passwordRule = Password::min($minLength);

        if ($requireUppercase) {
            $passwordRule->mixedCase();
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

        if ($locale === 'ja') {
            $descriptions[] = "最小{$minLength}文字";
            
            if ($requireUppercase) {
                $descriptions[] = "大文字と小文字を含む";
            }
            
            if ($requireNumber) {
                $descriptions[] = "数字を含む";
            }
            
            if ($requireSymbol) {
                $descriptions[] = "記号を含む";
            }
        } else {
            $descriptions[] = "At least {$minLength} characters";
            
            if ($requireUppercase) {
                $descriptions[] = "mixed case letters";
            }
            
            if ($requireNumber) {
                $descriptions[] = "numbers";
            }
            
            if ($requireSymbol) {
                $descriptions[] = "symbols";
            }
        }

        return implode($locale === 'ja' ? '、' : ', ', $descriptions);
    }
}
