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

namespace App\Http\Requests\Admin\Settings\Security;

use Illuminate\Foundation\Http\FormRequest;

class AdminSettngsSecurityUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation()
    {
        $this->merge([
            'enable_allowed_admin_ips' => filter_var($this->input('enable_allowed_admin_ips'), FILTER_VALIDATE_BOOLEAN),
            'enable_blocked_admin_ips' => filter_var($this->input('enable_blocked_admin_ips'), FILTER_VALIDATE_BOOLEAN),
            'allowed_admin_ips' => $this->input('allowed_admin_ips') === '' ? null : $this->input('allowed_admin_ips'),
            'blocked_admin_ips' => $this->input('blocked_admin_ips') === '' ? null : $this->input('blocked_admin_ips'),
            'force_ssl' => filter_var($this->input('force_ssl'), FILTER_VALIDATE_BOOLEAN),
        ]);
    }


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [

            'admin_url' => 'required|string|max:255',
            'enable_allowed_admin_ips' => 'required|boolean',
            'allowed_admin_ips' => 'nullable|string|regex:/^(\d{1,3}\.){3}\d{1,3}(,\s*(\d{1,3}\.){3}\d{1,3})*$/',
            'enable_blocked_admin_ips' => 'required|boolean',
            'blocked_admin_ips' => 'nullable|string|regex:/^(\d{1,3}\.){3}\d{1,3}(,\s*(\d{1,3}\.){3}\d{1,3})*$/',
            'force_ssl' => 'required|boolean',
        ];
    }

    /**
     * カスタムエラーメッセージ
     */
    public function messages(): array
    {
        return [
            'allowed_admin_ips.regex' => 'IPアドレスはカンマ区切りの形式で入力してください (例: 192.168.1.1, 127.0.0.1)。',
            'blocked_admin_ips.regex' => 'IPアドレスはカンマ区切りの形式で入力してください (例: 192.168.1.1, 127.0.0.1)。',
        ];
    }
}
