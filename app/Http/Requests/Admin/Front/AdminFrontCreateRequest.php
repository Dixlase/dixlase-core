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

namespace App\Http\Requests\Admin\Front;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminFrontCreateRequest extends FormRequest
{
    /**
     * リクエストの認可判定
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * バリデーションルール
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $languageKeys = array_keys(config('language.languages', []));

        return [
            'lang' => ['required', 'string', Rule::in($languageKeys)],
            'editor_type' => ['required', 'string', Rule::in(['html', 'markdown'])],
            'storage_type' => ['required', 'string', Rule::in(['database', 'file'])],
            'content' => ['nullable', 'string', 'max:500000'],
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
            'lang.required' => __('admin/front.create.validation.lang_required'),
            'lang.in' => __('admin/front.create.validation.lang_in'),
            'editor_type.required' => __('admin/front.create.validation.editor_type_required'),
            'editor_type.in' => __('admin/front.create.validation.editor_type_in'),
            'storage_type.required' => __('admin/front.create.validation.storage_type_required'),
            'storage_type.in' => __('admin/front.create.validation.storage_type_in'),
            'content.max' => __('admin/front.create.validation.content_max'),
        ];
    }
}
