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

namespace App\Http\Requests\Admin\Settings\Security;

use App\Enums\SecurityAction;
use Illuminate\Foundation\Http\FormRequest;

class AdminSecurityExtensionsUpdateRequest extends FormRequest
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
            'extension_security_preset' => 'required|in:strict,balanced,development,custom',
            'extension_require_signature' => 'boolean',
            'extension_require_permission_definition' => 'boolean',
            'extension_allow_undefined_permissions' => 'boolean',
            'extension_plugin_max_health_level' => 'required|integer|min:0|max:3',
            'extension_theme_max_health_level' => 'required|integer|min:0|max:3',
            'extension_allow_logic_themes' => 'boolean',
            'extension_permission_mismatch_action' => 'required|'.SecurityAction::validationRule(),
            'extension_notify_on_install' => 'boolean',
            'extension_notify_on_uninstall' => 'boolean',
            'extension_notify_on_enable' => 'boolean',
            'extension_notify_on_disable' => 'boolean',
            'extension_notify_on_unhealthy' => 'boolean',
            'extension_log_operations' => 'boolean',
            // Extension source settings
            'extension_source_type' => 'required|in:'.implode(',', array_keys(config('extension-sources.presets', ['github' => []]))),
            'extension_source_owner' => 'nullable|string|max:100',
            'extension_source_token' => 'nullable|string|max:500',
            'extension_update_check_interval' => 'required|integer|in:'.implode(',', array_keys(config('extension-sources.check_intervals', [86400 => '']))),
        ];
    }
}
