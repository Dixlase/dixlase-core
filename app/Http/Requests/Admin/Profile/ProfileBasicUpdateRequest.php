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

namespace App\Http\Requests\Admin\Profile;

use App\Enums\Locale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ProfileBasicUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $member = Auth::guard('member')->user();
        
        $rules = [
            'account_name' => 'required|string|alpha_num|min:3|max:20',
            'display_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'email' => 'required|string|email|max:255|unique:members,email,' . $member->id,
            'locale' => 'nullable|string|in:' . implode(',', Locale::values()),
        ];

        // メールアドレスが変更された場合は確認フィールドを必須に
        if ($this->input('email') !== $member->email) {
            $rules['email_confirmation'] = 'required|email|same:email';
        }

        return $rules;
    }
}
