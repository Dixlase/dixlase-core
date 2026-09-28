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

namespace App\Http\Requests\Admin\Settings\Base;

use App\Rules\UniqueRouteSlug;
use App\Support\HttpsEnforcement;
use Illuminate\Contracts\Validation\Validator;
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
     * Prepare data before validation
     * Generate admin_url by combining prefix and suffix
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
            'admin_login_notice' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Refuse force_ssl when the site cannot be served over https.
     *
     * Saving it in that state used to leave the admin panel unstyled and
     * unusable, and this very screen is the only one that can switch it back.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->boolean('force_ssl')) {
                return;
            }

            if (HttpsEnforcement::isForceable((string) config('app.url'))) {
                return;
            }

            $validator->errors()->add(
                'force_ssl',
                __('admin/settings/base/admin.force_ssl_unavailable', [
                    'url' => (string) config('app.url'),
                ]),
            );
        });
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
