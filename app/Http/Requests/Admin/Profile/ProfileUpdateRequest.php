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

namespace App\Http\Requests\Admin\Profile;

use App\Enums\AppearanceMode;
use App\Enums\Locale;
use App\Enums\AuthenticationMode;
use App\Enums\TwoFaMethod;
use App\Models\SecuritySetting;
use App\Services\PasswordService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Enum;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // プロフィール更新は認証済みユーザーのみアクセス可能
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $member = Auth::guard('member')->user();
        
        // パスワード設定を取得（セキュリティ設定から）
        $passwordMinLength = (int) SecuritySetting::getValue('password_min_length', 8);
        $passwordRequireUppercase = (bool) SecuritySetting::getValue('password_require_uppercase', true);
        $passwordRequireLowercase = (bool) SecuritySetting::getValue('password_require_lowercase', true);
        $passwordRequireNumber = (bool) SecuritySetting::getValue('password_require_number', true);
        $passwordRequireSymbol = (bool) SecuritySetting::getValue('password_require_symbol', false);
        $passwordCheckPwned = (bool) SecuritySetting::getValue('password_check_pwned', false);

        // パスワードバリデーションルールを構築（任意入力）
        $passwordRules = PasswordService::buildPasswordRules(
            $passwordMinLength,
            $passwordRequireUppercase,
            $passwordRequireLowercase,
            $passwordRequireNumber,
            $passwordRequireSymbol,
            false, // プロフィール更新時は任意
            $passwordCheckPwned
        );

        $rules = [
            'account_name' => 'required|string|alpha_num|min:3|max:20',
            'display_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'email' => 'required|string|email|max:255|unique:members,email,' . $member->id,
            'locale' => 'nullable|string|in:' . implode(',', Locale::values()),
            'password' => $passwordRules,
            'appearance' => ['nullable', new Enum(AppearanceMode::class)],
            'login_notification_mode' => ['nullable', new Enum(AuthenticationMode::class)],
            'two_fa_mode' => ['nullable', new Enum(AuthenticationMode::class)],
            'two_fa_passkey_enabled' => 'nullable|boolean',
            'two_fa_default_method' => 'nullable|integer|in:0,1',
        ];

        // メールアドレスが変更された場合は確認フィールドを必須に
        if ($this->input('email') !== $member->email) {
            $rules['email_confirmation'] = 'required|email|same:email';
        }

        return $rules;
    }
}
