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

namespace App\Http\Requests\Admin\Settings\Security;

use App\Enums\ExtensionSecurityPreset;
use Illuminate\Foundation\Http\FormRequest;

class AdminSettngsSecurityUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation()
    {
        $this->merge([
            'enable_allowed_admin_ips' => filter_var($this->input('enable_allowed_admin_ips'), FILTER_VALIDATE_BOOLEAN),
            'enable_blocked_admin_ips' => filter_var($this->input('enable_blocked_admin_ips'), FILTER_VALIDATE_BOOLEAN),
            'allowed_admin_ips' => $this->input('allowed_admin_ips') === '' ? null : $this->input('allowed_admin_ips'),
            'blocked_admin_ips' => $this->input('blocked_admin_ips') === '' ? null : $this->input('blocked_admin_ips'),
            'enable_allowed_front_ips' => filter_var($this->input('enable_allowed_front_ips'), FILTER_VALIDATE_BOOLEAN),
            'allowed_front_ips' => $this->input('allowed_front_ips') === '' ? null : $this->input('allowed_front_ips'),
            'enable_blocked_front_ips' => filter_var($this->input('enable_blocked_front_ips'), FILTER_VALIDATE_BOOLEAN),
            'blocked_front_ips' => $this->input('blocked_front_ips') === '' ? null : $this->input('blocked_front_ips'),
            // reCAPTCHA settings
            'captcha_enabled' => filter_var($this->input('captcha_enabled'), FILTER_VALIDATE_BOOLEAN),
            'captcha_contact_form' => filter_var($this->input('captcha_contact_form'), FILTER_VALIDATE_BOOLEAN),
            'captcha_registration_form' => filter_var($this->input('captcha_registration_form'), FILTER_VALIDATE_BOOLEAN),
            'captcha_login_form' => filter_var($this->input('captcha_login_form'), FILTER_VALIDATE_BOOLEAN),
            'captcha_comment_form' => filter_var($this->input('captcha_comment_form'), FILTER_VALIDATE_BOOLEAN),
            // Session settings
            'session_encrypt' => filter_var($this->input('session_encrypt'), FILTER_VALIDATE_BOOLEAN),
            // Notification settings
            'notification_enabled' => filter_var($this->input('notification_enabled'), FILTER_VALIDATE_BOOLEAN),
            // Password security settings
            'pwned_password_check_enabled' => filter_var($this->input('pwned_password_check_enabled'), FILTER_VALIDATE_BOOLEAN),
            // CAPTCHA authentication result
            'captcha_authentication_result' => filter_var($this->input('captcha_authentication_result'), FILTER_VALIDATE_BOOLEAN),
            // Extension security settings
            'extension_require_signature' => filter_var($this->input('extension_require_signature'), FILTER_VALIDATE_BOOLEAN),
            'extension_require_permission_definition' => filter_var($this->input('extension_require_permission_definition'), FILTER_VALIDATE_BOOLEAN),
            'extension_allow_undefined_permissions' => filter_var($this->input('extension_allow_undefined_permissions'), FILTER_VALIDATE_BOOLEAN),
            'extension_allow_logic_themes' => filter_var($this->input('extension_allow_logic_themes'), FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'enable_allowed_admin_ips' => 'required|boolean',
            'allowed_admin_ips' => 'nullable|string|regex:/^(\d{1,3}\.){3}\d{1,3}(,\s*(\d{1,3}\.){3}\d{1,3})*$/',
            'enable_blocked_admin_ips' => 'required|boolean',
            'blocked_admin_ips' => 'nullable|string|regex:/^(\d{1,3}\.){3}\d{1,3}(,\s*(\d{1,3}\.){3}\d{1,3})*$/',
            'enable_allowed_front_ips' => 'required|boolean',
            'allowed_front_ips' => 'nullable|string|regex:/^(\d{1,3}\.){3}\d{1,3}(,\s*(\d{1,3}\.){3}\d{1,3})*$/',
            'enable_blocked_front_ips' => 'required|boolean',
            'blocked_front_ips' => 'nullable|string|regex:/^(\d{1,3}\.){3}\d{1,3}(,\s*(\d{1,3}\.){3}\d{1,3})*$/',
            // Session validation rules
            'session_driver' => 'required|string|in:file,database,redis,memcached,cookie,array',
            'session_encrypt' => 'required|boolean',
            'session_lifetime' => 'required|integer|min:1|max:43200',
            // reCAPTCHA validation rules
            'captcha_enabled' => 'required|boolean',
            'captcha_driver' => 'nullable|string|in:google,google_enterprise,turnstile',
            'captcha_google_version' => 'nullable|string|in:v2_checkbox,v2_invisible,v3',
            'captcha_google_min_score' => 'nullable|numeric|between:0,1',
            'captcha_google_project_id' => 'nullable|string|max:255',
            // Common CAPTCHA fields
            'captcha_site_key' => 'nullable|string|max:255',
            'captcha_secret_key' => 'nullable|string|max:255',
            'captcha_contact_form' => 'required|boolean',
            'captcha_registration_form' => 'required|boolean',
            'captcha_login_form' => 'required|boolean',
            'captcha_comment_form' => 'required|boolean',
            // Notification validation rules
            'notification_enabled' => 'required|boolean',
            'notification_log_levels' => 'nullable|array',
            // Password security validation rules
            'pwned_password_check_enabled' => 'required|boolean',
            // Extension security validation rules
            'extension_security_preset' => 'required|string|in:'.implode(',', array_map(fn ($p) => $p->value, ExtensionSecurityPreset::cases())),
            'extension_require_signature' => 'nullable|boolean',
            'extension_require_permission_definition' => 'nullable|boolean',
            'extension_allow_undefined_permissions' => 'nullable|boolean',
            'extension_plugin_max_health_level' => 'nullable|integer|min:0|max:3',
            'extension_theme_max_health_level' => 'nullable|integer|min:0|max:3',
            'extension_allow_logic_themes' => 'nullable|boolean',
            'extension_permission_mismatch_action' => 'nullable|string|in:warn,block',
        ];

        // Conditional validation when CAPTCHA is enabled
        if ($this->boolean('captcha_enabled')) {
            $captchaDriver = $this->input('captcha_driver', 'google');

            // Make common fields required
            $rules['captcha_site_key'] = 'required|string';
            $rules['captcha_secret_key'] = 'required|string';

            // Require min_score only for Google reCAPTCHA v3 or Enterprise
            if (($captchaDriver === 'google' && $this->input('captcha_google_version') === 'v3') || $captchaDriver === 'google_enterprise') {
                $rules['captcha_google_min_score'] = 'required|numeric|between:0,1';
            }

            // Project ID is also required when using Enterprise
            if ($captchaDriver === 'google_enterprise') {
                $rules['captcha_google_project_id'] = 'required|string';
            }

            // Test is not required even when CAPTCHA is enabled (only settings are required)
        } else {
            $rules['captcha_google_site_key'] = 'nullable|string|max:255';
            $rules['captcha_google_secret_key'] = 'nullable|string|max:255';
            $rules['captcha_google_project_id'] = 'nullable|string|max:255';
        }

        return $rules;
    }

    /**
     * Custom error messages
     */
    public function messages(): array
    {
        return [
            'allowed_admin_ips.regex' => __('admin/settings/security/validation.allowed_admin_ips_format'),
            'blocked_admin_ips.regex' => __('admin/settings/security/validation.blocked_admin_ips_format'),
            'allowed_front_ips.regex' => __('admin/settings/security/validation.allowed_front_ips_format'),
            'blocked_front_ips.regex' => __('admin/settings/security/validation.blocked_front_ips_format'),
            'captcha_google_version.in' => __('admin/settings/security/validation.captcha_google_version_invalid'),
            'captcha_google_min_score.between' => __('admin/settings/security/validation.captcha_google_min_score_range'),
            'captcha_driver.in' => __('admin/settings/security/validation.captcha_driver_invalid'),
            // CAPTCHA required validation message
            'captcha_site_key.required' => __('admin/settings/security/validation.captcha_site_key_required'),
            'captcha_secret_key.required' => __('admin/settings/security/validation.captcha_secret_key_required'),
            'captcha_google_project_id.required' => __('admin/settings/security/validation.captcha_google_project_id_required'),
        ];
    }
}
