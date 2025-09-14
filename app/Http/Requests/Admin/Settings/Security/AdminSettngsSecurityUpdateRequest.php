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

namespace App\Http\Requests\Admin\Settings\Security;

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
            // Notification settings
            'notification_enabled' => filter_var($this->input('notification_enabled'), FILTER_VALIDATE_BOOLEAN),
            // CAPTCHA validation status
            'captcha_validation_status' => filter_var($this->input('captcha_validation_status'), FILTER_VALIDATE_BOOLEAN),
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
            // reCAPTCHA validation rules
            'captcha_enabled' => 'required|boolean',
            'captcha_driver' => 'nullable|string|in:google,turnstile',
            'captcha_google_version' => 'nullable|string|in:v2_checkbox,v2_invisible,v3',
            'captcha_google_min_score' => 'nullable|numeric|between:0,1',
            // Turnstile validation rules
            'captcha_turnstile_site_key' => 'nullable|string|max:255',
            'captcha_turnstile_secret_key' => 'nullable|string|max:255',
            'captcha_contact_form' => 'required|boolean',
            'captcha_registration_form' => 'required|boolean',
            'captcha_login_form' => 'required|boolean',
            'captcha_comment_form' => 'required|boolean',
            // Notification validation rules
            'notification_enabled' => 'required|boolean',
            'notification_log_levels' => 'nullable|array',
        ];

        // CAPTCHAが有効な場合の条件付きバリデーション
        if ($this->boolean('captcha_enabled')) {
            $captchaDriver = $this->input('captcha_driver', 'google');
            
            // CAPTCHA認証が必須
            $rules['captcha_validation_status'] = 'required|boolean|accepted';
            $rules['captcha_validation_token'] = 'required|string';
            
            // Google reCAPTCHA条件付きバリデーション
            if ($this->input('captcha_enabled') && ($this->input('captcha_driver') === 'google' || $this->input('captcha_driver') === 'google_enterprise')) {
                $rules['captcha_google_site_key'] = 'required|string';
                $rules['captcha_google_secret_key'] = 'required|string';
                
                // Enterprise使用時はプロジェクトIDも必須
                if ($this->input('captcha_driver') === 'google_enterprise') {
                    $rules['captcha_google_project_id'] = 'required|string';
                }
            } elseif ($captchaDriver === 'turnstile') {
                $rules['captcha_turnstile_site_key'] = 'required|string|max:255';
                $rules['captcha_turnstile_secret_key'] = 'required|string|max:255';
            }
        } else {
            $rules['captcha_google_site_key'] = 'nullable|string|max:255';
            $rules['captcha_google_secret_key'] = 'nullable|string|max:255';
            $rules['captcha_google_project_id'] = 'nullable|string|max:255';
            $rules['captcha_validation_status'] = 'nullable|boolean';
            $rules['captcha_validation_token'] = 'nullable|string';
        }

        return $rules;
    }

    /**
     * カスタムエラーメッセージ
     */
    public function messages(): array
    {
        return [
            'allowed_admin_ips.regex' => __('admin.security.validation.allowed_admin_ips_format'),
            'blocked_admin_ips.regex' => __('admin.security.validation.blocked_admin_ips_format'),
            'allowed_front_ips.regex' => __('admin.security.validation.allowed_front_ips_format'),
            'blocked_front_ips.regex' => __('admin.security.validation.blocked_front_ips_format'),
            'captcha_google_version.in' => __('admin.security.validation.captcha_google_version_invalid'),
            'captcha_google_min_score.between' => __('admin.security.validation.captcha_google_min_score_range'),
            'captcha_driver.in' => __('admin.security.validation.captcha_driver_invalid'),
            // CAPTCHA必須バリデーションメッセージ
            'captcha_google_site_key.required' => __('admin.security.validation.captcha_google_site_key_required'),
            'captcha_google_secret_key.required' => __('admin.security.validation.captcha_google_secret_key_required'),
            'captcha_turnstile_site_key.required' => __('admin.security.validation.captcha_turnstile_site_key_required'),
            'captcha_turnstile_secret_key.required' => __('admin.security.validation.captcha_turnstile_secret_key_required'),
            // CAPTCHA認証バリデーションメッセージ
            'captcha_validation_status.accepted' => __('admin.security.validation.captcha_validation_required'),
            'captcha_validation_token.required' => __('admin.security.validation.captcha_validation_required'),
        ];
    }
}
