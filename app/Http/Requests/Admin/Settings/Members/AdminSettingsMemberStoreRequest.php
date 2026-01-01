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

namespace App\Http\Requests\Admin\Settings\Members;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\MemberRole;
use App\Models\MemberSetting;
use App\Services\PasswordValidationService;

class AdminSettingsMemberStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // 管理者IDがリクエストされているかで判断
        $isUpdate = $this->route('member') !== null;
        $member = $this->route('member');
        
        // 初期メンバー（ID=1）かどうか
        $isInitialAdmin = $member && $member->id === 1;

        // パスワード設定を取得
        $passwordMinLength = (int) MemberSetting::getValue('password_min_length', 8);
        $passwordRequireUppercase = (bool) MemberSetting::getValue('password_require_uppercase', true);
        $passwordRequireLowercase = (bool) MemberSetting::getValue('password_require_lowercase', true);
        $passwordRequireNumber = (bool) MemberSetting::getValue('password_require_number', true);
        $passwordRequireSymbol = (bool) MemberSetting::getValue('password_require_symbol', false);
        $passwordCheckPwned = (bool) MemberSetting::getValue('password_check_pwned', false);

        // パスワードバリデーションルールを構築
        $passwordRules = PasswordValidationService::buildPasswordRules(
            $passwordMinLength,
            $passwordRequireUppercase,
            $passwordRequireLowercase,
            $passwordRequireNumber,
            $passwordRequireSymbol,
            !$isUpdate, // 新規作成時は必須、編集時は任意
            $passwordCheckPwned
        );

        $rules = [
            'account_name' => 'required|string|alpha_num|min:3|max:20',
            'member_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'email' => [
                'required',
                'email',
                Rule::unique('members', 'email')
                    ->ignore($this->route('member'))
                    ->whereNull('deleted_at'), // 削除されていないメンバーのみをチェック
            ],
            'password' => $passwordRules,
            // 初期メンバーの場合はroleを任意（フィールドが送信されないため）
            'role' => $isInitialAdmin 
                ? ['nullable', Rule::in(array_column(MemberRole::cases(), 'value'))]
                : ['required', Rule::in(array_column(MemberRole::cases(), 'value'))],
            'locale' => 'nullable|string|in:ja,en',
            'appearance' => 'required|numeric|in:0,1,2',
            'status' => 'required|numeric|in:0,1',
            'email_verified' => 'nullable|numeric|in:0,1',
            'login_notification' => 'nullable|numeric|in:0,1',
            'two_factor_mode' => 'nullable|numeric|in:1,2,3',
            'two_factor_method' => 'nullable|numeric',
        ];

        // メールアドレス確認のバリデーション
        if (!$isUpdate) {
            // 新規作成時は必須
            $rules['email_confirmation'] = 'required|email|same:email';
        } else if ($member && $this->input('email') !== $member->email) {
            // 編集時にメールアドレスが変更された場合も必須
            $rules['email_confirmation'] = 'required|email|same:email';
        }

        return $rules;
    }

    /**
     * バリデーションメッセージをカスタマイズ（必要に応じて）
     */
    public function messages(): array
    {
        return [
            'account_name.required' => __('admin/members/validation.account_name_required'),
            'account_name.alpha_num' => __('admin/members/validation.account_name_alpha_num'),
            'account_name.min' => __('admin/members/validation.account_name_length'),
            'account_name.max' => __('admin/members/validation.account_name_length'),
            'email.required' => __('admin/members/validation.email_required'),
            'email.email' => __('admin/members/validation.email_invalid'),
            'email.unique' => __('admin/members/validation.email_unique'),
            'password.required' => __('admin/members/validation.password_required'),
            'password.min' => __('admin/members/validation.password_min'),
            'password.confirmed' => __('admin/members/validation.password_confirmed'),
            'role.required' => __('admin/members/validation.role_required'),
            'role.in' => __('admin/members/validation.role_invalid'),
            'appearance.required' => __('admin/members/validation.appearance_required'),
            'appearance.in' => __('admin/members/validation.appearance_invalid'),
            'status.required' => __('admin/members/validation.status_required'),
            'status.in' => __('admin/members/validation.status_invalid'),
        ];
    }
}
