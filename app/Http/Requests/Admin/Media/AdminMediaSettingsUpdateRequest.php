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

namespace App\Http\Requests\Admin\Media;

use Illuminate\Foundation\Http\FormRequest;

class AdminMediaSettingsUpdateRequest extends FormRequest
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
        $fileExtensions = config('admin.fileExtensions');

        return [
            'allowed_file_types' => 'array',
            'allowed_file_types.*' => 'in:' . implode(',', $fileExtensions),
            'max_file_size' => 'required|integer|min:1|max:100',
            'max_file_size_image' => 'required|integer|min:1|max:100',
            'max_file_size_video' => 'required|integer|min:1|max:1000',
            'max_file_size_document' => 'required|integer|min:1|max:100',
            'max_file_size_archive' => 'required|integer|min:1|max:500',
            'svg_sanitization_enabled' => 'boolean',
            'zip_security_enabled' => 'boolean',
            'mime_validation_enabled' => 'boolean',
            'zip_max_compression_ratio' => 'required|integer|min:10|max:1000',
            'zip_max_file_count' => 'required|integer|min:10|max:10000',
        ];
    }
}
