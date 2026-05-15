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

namespace App\Http\Requests\Admin\Settings\Security;

use App\Enums\CspBlocklistAction;
use App\Enums\CspMode;
use Illuminate\Foundation\Http\FormRequest;

class AdminSecurityCspUpdateRequest extends FormRequest
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
        $cspEnabled = filter_var($this->input('csp_enabled'), FILTER_VALIDATE_BOOLEAN);

        return [
            'csp_enabled' => 'boolean',
            'csp_mode' => $cspEnabled ? 'required|'.CspMode::validationRule() : 'nullable|'.CspMode::validationRule(),
            'csp_log_violations' => 'boolean',
            'csp_exclude_dev_tools' => 'boolean',
            'csp_trusted_domains' => 'nullable|string',
            'csp_denied_domains' => 'nullable|string',
            'csp_custom_directives_mode' => 'nullable|string|in:form,json',
            'csp_custom_directives' => 'nullable|string',
            'csp_directive_script_src' => 'nullable|string',
            'csp_directive_style_src' => 'nullable|string',
            'csp_directive_img_src' => 'nullable|string',
            'csp_directive_connect_src' => 'nullable|string',
            'csp_directive_font_src' => 'nullable|string',
            'csp_directive_frame_src' => 'nullable|string',
            'csp_blocklist_check_enabled' => 'boolean',
            'csp_blocklist_action' => $cspEnabled ? 'required|'.CspBlocklistAction::validationRule() : 'nullable|'.CspBlocklistAction::validationRule(),
            'csp_blocklist_categories' => 'nullable|array',
            'csp_blocklist_categories.*' => 'string',
        ];
    }
}
