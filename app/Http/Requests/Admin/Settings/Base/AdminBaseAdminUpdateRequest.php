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

namespace App\Http\Requests\Admin\Settings\Base;

use App\Rules\UniqueRouteSlug;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminBaseAdminUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * バリデーション前にデータを準備
     * プレフィックスとサフィックスを結合してadmin_urlを生成
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('admin_url_prefix') && $this->has('admin_url_suffix')) {
            $this->merge([
                'admin_url' => $this->input('admin_url_prefix').'-'.$this->input('admin_url_suffix'),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $prefixes = config('admin.url.admin_url_prefixes', ['admin']);

        return [
            'admin_url_prefix' => ['required', 'string', Rule::in($prefixes)],
            'admin_url_suffix' => ['required', 'string', 'min:4', 'max:50', 'regex:/^[a-z0-9]+$/'],
            'admin_url' => ['required', 'string', 'max:100', UniqueRouteSlug::for('core:admin_url')],
        ];
    }

    /**
     * バリデーションエラーメッセージ
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'admin_url_prefix.in' => __('validation/admin-url.prefix_invalid'),
            'admin_url_suffix.min' => __('validation/admin-url.suffix_min'),
            'admin_url_suffix.max' => __('validation/admin-url.suffix_max'),
            'admin_url_suffix.regex' => __('validation/admin-url.suffix_format'),
        ];
    }
}
