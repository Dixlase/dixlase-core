<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

namespace App\Http\Requests\Install;

use Illuminate\Foundation\Http\FormRequest;

class InstallSettingsRequest extends FormRequest
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
        return [
            'site_name' => 'required|string|max:60',
            'admin_account_name' => [
                'required',
                'string',
                'alpha_num',
                'min:3',
                'max:20',
            ],
            'admin_display_name' => 'nullable|string|max:255',
            'admin_email' => 'required|email',
            'admin_password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'admin_account_name.required' => __('install/step1.validation.admin_account_name_required'),
            'admin_account_name.alpha_num' => __('install/step1.validation.admin_account_name_alpha_num'),
            'admin_account_name.min' => __('install/step1.validation.admin_account_name_length'),
            'admin_account_name.max' => __('install/step1.validation.admin_account_name_length'),
            'admin_password.regex' => __('install/step1.password_strength_error'),
            'site_name.required' => __('validation.required', ['attribute' => __('validation.attributes.site_name')]),
            'admin_email.required' => __('validation.required', ['attribute' => __('validation.attributes.admin_email')]),
            'admin_email.email' => __('validation.email', ['attribute' => __('validation.attributes.admin_email')]),
            'admin_password.required' => __('validation.required', ['attribute' => __('validation.attributes.admin_password')]),
            'admin_password.min' => __('validation.min.string', ['attribute' => __('validation.attributes.admin_password'), 'min' => 8]),
        ];
    }
}
