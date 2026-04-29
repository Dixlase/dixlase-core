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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
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

namespace App\Http\Requests\Admin\Settings\Security;

use Illuminate\Foundation\Http\FormRequest;

class AdminSecurityCaptchaUpdateRequest extends FormRequest
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
        return [
            'captcha_enabled' => 'boolean',
            'captcha_driver' => 'required_if:captcha_enabled,1|in:google,google_enterprise,turnstile',
            'captcha_site_key' => 'required_if:captcha_enabled,1|nullable|string|max:255',
            'captcha_secret_key' => 'required_if:captcha_enabled,1|nullable|string|max:255',
            'captcha_google_version' => 'nullable|in:v2_checkbox,v2_invisible,v3',
            'captcha_google_min_score' => 'nullable|numeric|min:0|max:1',
            'captcha_google_project_id' => 'nullable|string|max:255',
            'captcha_authentication_result' => 'nullable|boolean',
            'forms' => 'nullable|array',
            'forms.*' => 'nullable|boolean',
        ];
    }
}
