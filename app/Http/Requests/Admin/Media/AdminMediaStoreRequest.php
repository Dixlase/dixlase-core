<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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

namespace App\Http\Requests\Admin\Media;

use App\Models\MediaSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class AdminMediaStoreRequest extends FormRequest
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
        $allowedFileTypes = $this->getAllowedFileTypes();
        $maxFileSize = $this->getMaxFileSize();
        $mimes = implode(',', $allowedFileTypes);

        // Supports both multiple file upload (files[]) and single file (file)
        if ($this->hasFile('files')) {
            return [
                'files' => 'required|array|min:1',
                'files.*' => 'file|mimes:'.$mimes.'|max:'.$maxFileSize,
            ];
        }

        return [
            'file' => 'required|file|mimes:'.$mimes.'|max:'.$maxFileSize,
        ];
    }

    public function messages()
    {
        $maxFileSize = MediaSetting::where('name', 'max_file_size')->value('value') ?? '2048';
        $maxFileSizeMB = round($maxFileSize / 1024);

        return [
            'file.required' => __('http/requests/admin/media/admin_media_store_request.file_is_required'),
            'file.file' => __('http/requests/admin/media/admin_media_store_request.please_upload_valid_file'),
            'file.mimes' => __('http/requests/admin/media/admin_media_store_request.allowed_file_types_are').implode(', ', $this->allowedTypes()).__('http/requests/admin/media/admin_media_store_request.period'),
            'file.max' => __('http/requests/admin/media/admin_media_store_request.file_size_exceeds_limit', ['maxFileSizeMB' => $maxFileSizeMB]),
        ];
    }

    protected function allowedTypes()
    {
        return json_decode(MediaSetting::where('name', 'allowed_file_types')->value('value'), true);
    }

    /**
     * Get allowed file types from settings
     */
    protected function getAllowedFileTypes(): array
    {
        $allowedTypes = MediaSetting::where('name', 'allowed_file_types')->value('value');

        return $allowedTypes ? json_decode($allowedTypes, true) : ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
    }

    /**
     * Get max file size from settings (in KB)
     */
    protected function getMaxFileSize(): int
    {
        $maxSize = MediaSetting::where('name', 'max_file_size')->value('value');

        return $maxSize ? (int) $maxSize : 2048;
    }

    public function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        Log::error('Validation error: '.json_encode($validator->errors()->all()));

        parent::failedValidation($validator);
    }
}
