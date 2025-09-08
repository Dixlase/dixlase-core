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

namespace App\Http\Requests\Admin\Settings\Members;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\MemberRole;

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

        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'email' => [
                'required',
                'email',
                Rule::unique('members', 'email')->ignore($this->route('member')),
            ],
            'password' => $isUpdate ? 'nullable|string|min:8' : 'required|string|min:8',
            'role' => ['required', Rule::in(array_column(MemberRole::cases(), 'value'))],
            'appearance' => 'required|numeric|in:0,1,2',
            'status' => 'required|numeric|in:0,1',
            'login_notification_mode' => 'required|numeric|in:1,2,3',
            'two_factor_mode' => 'nullable|numeric|in:1,2,3',
            'two_factor_method' => 'nullable|numeric',
        ];
    }

    /**
     * バリデーションメッセージをカスタマイズ（必要に応じて）
     */
    public function messages(): array
    {
        return [
            'name.required' => __('admin.members.validation.name_required'),
            'email.required' => __('admin.members.validation.email_required'),
            'email.email' => __('admin.members.validation.email_invalid'),
            'email.unique' => __('admin.members.validation.email_unique'),
            'password.required' => __('admin.members.validation.password_required'),
            'password.min' => __('admin.members.validation.password_min'),
            'password.confirmed' => __('admin.members.validation.password_confirmed'),
            'role.required' => __('admin.members.validation.role_required'),
            'role.in' => __('admin.members.validation.role_invalid'),
            'appearance.required' => __('admin.members.validation.appearance_required'),
            'appearance.in' => __('admin.members.validation.appearance_invalid'),
            'status.required' => __('admin.members.validation.status_required'),
            'status.in' => __('admin.members.validation.status_invalid'),
        ];
    }
}
