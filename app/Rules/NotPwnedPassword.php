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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
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

namespace App\Rules;

use App\Traits\PwnedPasswordTrait;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * パスワード漏洩チェックバリデーションルール
 *
 * Have I Been Pwned APIを使用してパスワードが漏洩データベースに含まれていないかチェック
 */
class NotPwnedPassword implements ValidationRule
{
    use PwnedPasswordTrait;

    private string $settingKey;

    private bool $skipOnApiError;

    /**
     * コンストラクタ
     *
     * @param  string  $settingKey  設定キー（デフォルト: 'pwned_password_check_enabled'）
     * @param  bool  $skipOnApiError  APIエラー時にバリデーションをスキップするか（デフォルト: true）
     */
    public function __construct(string $settingKey = 'pwned_password_check_enabled', bool $skipOnApiError = true)
    {
        $this->settingKey = $settingKey;
        $this->skipOnApiError = $skipOnApiError;
    }

    /**
     * バリデーション実行
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // 辞書攻撃対策が無効の場合はスキップ
        if (! $this->isPwnedPasswordCheckEnabled($this->settingKey)) {
            return;
        }

        // パスワードが文字列でない場合はスキップ
        if (! is_string($value)) {
            return;
        }

        $safetyCheck = $this->validatePasswordSafety($value, $this->settingKey);

        // APIエラーの場合
        if ($safetyCheck['pwned_info']['error']) {
            if (! $this->skipOnApiError) {
                $fail(__('validation.pwned_password_api_error'));
            }

            return;
        }

        // パスワードが漏洩している場合
        if (! $safetyCheck['is_safe']) {
            $fail($safetyCheck['message']);
        }
    }

    /**
     * 静的ファクトリーメソッド
     *
     * @param  string  $settingKey  設定キー
     * @param  bool  $skipOnApiError  APIエラー時にスキップするか
     */
    public static function using(string $settingKey = 'pwned_password_check_enabled', bool $skipOnApiError = true): static
    {
        return new static($settingKey, $skipOnApiError);
    }
}
