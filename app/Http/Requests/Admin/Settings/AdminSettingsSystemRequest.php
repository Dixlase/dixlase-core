<?php

/**
 * This file is part of Your Software Name.
 *
 * Copyright (C) 2024 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;

class AdminSettingsSystemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * バリデーションルールの設定
     */
    public function rules()
    {
        return [
            'site_name' => 'required|string|max:255',
            'language' => 'required|in:ja,en',
            'is_member_site' => 'required|boolean',
            'allow_external_registration' => 'required|boolean',
            'maintenance_mode' => 'required|boolean',
            'maintenance_message' => 'nullable|string',
            'allow_guest_registration' => 'required|boolean',
            'required_fields' => 'nullable|array',
            'required_fields.address' => 'boolean',
            'required_fields.phone' => 'boolean',
            'required_fields.gender' => 'boolean',
            'required_fields.birthday' => 'boolean',
        ];
    }

    /**
     * エラーメッセージのカスタマイズ
     */
    public function messages()
    {
        return [
            'site_name.required' => 'サイト名は必須です。',
            'language.required' => '言語を選択してください。',
            'is_member_site.required' => '会員サイトの設定は必須です。',
            'allow_external_registration.required' => '外部ユーザー登録の設定は必須です。',
            'maintenance_mode.required' => 'メンテナンスモードの設定は必須です。',
            'allow_guest_registration.required' => 'ゲスト申し込みの設定は必須です。',
            'required_fields.required' => '必須項目の設定を行ってください。',
        ];
    }
}
