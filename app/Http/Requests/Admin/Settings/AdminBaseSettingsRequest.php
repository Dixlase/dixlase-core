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

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminBaseSettingsRequest extends FormRequest
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
        $availableLocales = array_keys(config('admin.locale.available', []));
        
        return [
            'app_name' => 'required|string|max:255',
            'locale' => ['required', Rule::in($availableLocales)],
            'timezone' => 'required|timezone',
            'mail_mailer' => ['required', Rule::in(array_keys(trans('mail.mailers')))],
            'mail_host' => 'required|string',
            'mail_port' => 'required|numeric',
            'mail_username' => 'nullable|string',
            'mail_password' => 'nullable|string',
            'mail_encryption' => ['nullable', Rule::in(array_keys(trans('mail.encryptions')))],
            'mail_from_name' => 'nullable|string|max:255',
            'maintenance_mode' => 'required|boolean',
            'maintenance_message' => 'nullable|string',
        ];
    }

    /**
     * エラーメッセージのカスタマイズ
     */
    public function messages()
    {
        return [
            'site_name.required' => 'サイト名は必須です。',
            'locale.required' => '言語を選択してください。',
            'timezone.timezone' => '有効なタイムゾーンを選択してください。',
            'required_fields.required' => '必須項目の設定を行ってください。',
        ];
    }
}
