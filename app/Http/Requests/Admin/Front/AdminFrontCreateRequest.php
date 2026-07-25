<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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
     * Determine if the request is authorized
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules
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
            'custom_js' => ['nullable', 'string', 'max:500000'],
            'custom_css' => ['nullable', 'string', 'max:500000'],
        ];
    }

    /**
     * Validation error messages
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
            'custom_js.max' => __('admin/front.create.validation.custom_js_max'),
            'custom_css.max' => __('admin/front.create.validation.custom_css_max'),
        ];
    }
}
