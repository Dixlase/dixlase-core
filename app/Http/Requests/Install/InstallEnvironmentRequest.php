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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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

namespace App\Http\Requests\Install;

use App\Enums\AdminMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InstallEnvironmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'app_debug' => filter_var($this->input('app_debug'), FILTER_VALIDATE_BOOLEAN),
            'force_ssl' => filter_var($this->input('force_ssl'), FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isSimpleMode = (int) session('install_data.install_mode', 0) === AdminMode::Simple->value;

        if ($isSimpleMode) {
            return [
                'app_url' => 'required|string',
                'app_timezone' => 'required|timezone',
            ];
        }

        $prefixes = config('admin.url.admin_url_prefixes', ['admin']);

        return [
            'app_env' => 'required|in:local,staging,production',
            'app_debug' => 'nullable|boolean',
            'app_url' => 'required|string',
            'admin_url_prefix' => ['required', 'string', Rule::in($prefixes)],
            'admin_url_suffix' => ['required', 'string', 'min:4', 'max:50', 'regex:/^[a-z0-9]+$/'],
            'app_timezone' => 'required|timezone',
            'force_ssl' => 'nullable|boolean',
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
            'admin_url_prefix.in' => __('validation/admin-url.prefix_invalid'),
            'admin_url_suffix.min' => __('validation/admin-url.suffix_min'),
            'admin_url_suffix.max' => __('validation/admin-url.suffix_max'),
            'admin_url_suffix.regex' => __('validation/admin-url.suffix_format'),
        ];
    }
}
