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

namespace App\Http\Requests\Admin\Users;

use Illuminate\Foundation\Http\FormRequest;

class AdminUserStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // 必要に応じて権限チェックを追加
        return true; // true を返すことで全リクエストを許可
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        // ユーザーIDがリクエストされているかで判断
        $isUpdate = $this->route('user') !== null;

        return [
            'last_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . ($isUpdate ? $this->route('user')->id : 'NULL'),
            'password' => $isUpdate ? 'nullable|string|min:8' : 'required|string|min:8', // 作成時は必須、編集時は任意
            'status' => 'required|numeric|in:0,1',
        ];
    }

    /**
     * バリデーションメッセージをカスタマイズ（必要に応じて）
     */
    public function messages(): array
    {
        return [
            'last_name.required' => '姓は必須です。',
            'first_name.required' => '名は必須です。',
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
