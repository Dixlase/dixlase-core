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

namespace App\Http\Requests\Admin\Contents\Pages;

use Illuminate\Foundation\Http\FormRequest;

class AdminContentsPagesStoreRequest extends FormRequest
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

        // ページIDがリクエストされているかで判断
        $isUpdate = $this->route('page') !== null;


        return [
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:pages,slug,' . ($isUpdate ? $this->route('page')->id : 'NULL'),
            'content' => 'required|string',
            'status' => 'required|numeric|in:0,1',
            //'meta_title' => 'nullable|string|max:255',
            //'meta_description' => 'nullable|string|max:255',
            //'meta_keywords' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => __('validation.required'),
            'title.string' => __('validation.string'),
            'title.max' => __('validation.max.string', ['max' => 255]),
            'slug.required' => __('validation.required'),
            'slug.string' => __('validation.string'),
            'slug.max' => __('validation.max.string', ['max' => 255]),
            'slug.unique' => __('validation.unique'),
            'content.required' => __('validation.required'),
            'content.string' => __('validation.string'),
            'status.required' => __('validation.required'),
            'status.in' => __('validation.in'),
            //'meta_title.string' => __('validation.string'),
            //'meta_title.max' => __('validation.max.string', ['max' => 255]),
            //'meta_description.string' => __('validation.string'),
            //'meta_description.max' => __('validation.max.string', ['max' => 255]),
            //'meta_keywords.string' => __('validation.string'),
            //'meta_keywords.max' => __('validation.max.string', ['max' => 255]),
        ];
    }
}
