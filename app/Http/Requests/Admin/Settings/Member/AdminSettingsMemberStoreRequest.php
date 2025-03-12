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

namespace App\Http\Requests\Admin\Settings\Member;

use Illuminate\Foundation\Http\FormRequest;

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
        $isUpdate = $this->route('admin') !== null;

        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:members,email,' . ($isUpdate ? $this->route('member')->id : 'NULL'),
            'password' => $isUpdate ? 'nullable|string|min:8' : 'required|string|min:8', // 作成時は必須、編集時は任意
            'role' => 'required||in:admin,super_admin,editor,author,receptionist',
            'appearance' => 'required|numeric|in:0,1,2',
            'status' => 'required|numeric|in:0,1',
        ];
    }

    /**
     * バリデーションメッセージをカスタマイズ（必要に応じて）
     */
    public function messages(): array
    {
        return [
            'name.required' => '名前は必須です。',
            'email.required' => 'メールアドレスは必須です。',
            'email.email' => 'メールアドレスの形式が正しくありません。',
            'email.unique' => 'このメールアドレスは既に登録されています。',
            'password.required' => 'パスワードは必須です。',
            'password.min' => 'パスワードは最低8文字必要です。',
            'password.confirmed' => 'パスワード確認が一致しません。',
        ];
    }
}
