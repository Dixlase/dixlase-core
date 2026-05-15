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

namespace App\Http\Requests\Admin\Settings\Members;

use App\Enums\MemberRole;
use App\Models\SecuritySetting;
use App\Services\PasswordService;
use App\Traits\TwoFa\TwoFactorEnableCheck;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminSettingsMemberStoreRequest extends FormRequest
{
    use TwoFactorEnableCheck;

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
        // Determine based on whether an administrator ID is requested
        $isUpdate = $this->route('member') !== null;
        $member = $this->route('member');

        // Whether it's the initial member (ID=1)
        $isInitialAdmin = $member && $member->id === 1;

        // Get password settings (from security settings)
        $passwordMinLength = (int) SecuritySetting::getValue('password_min_length', 8);
        $passwordRequireUppercase = (bool) SecuritySetting::getValue('password_require_uppercase', true);
        $passwordRequireLowercase = (bool) SecuritySetting::getValue('password_require_lowercase', true);
        $passwordRequireNumber = (bool) SecuritySetting::getValue('password_require_number', true);
        $passwordRequireSymbol = (bool) SecuritySetting::getValue('password_require_symbol', false);

        // Build password validation rules
        $passwordRules = PasswordService::buildPasswordRules(
            $passwordMinLength,
            $passwordRequireUppercase,
            $passwordRequireLowercase,
            $passwordRequireNumber,
            $passwordRequireSymbol,
            ! $isUpdate // required on create, optional on update
        );

        $rules = [
            'account_name' => 'required|string|alpha_num|min:3|max:20',
            'display_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'email' => [
                'required',
                'email',
                Rule::unique('members', 'email')
                    ->ignore($this->route('member'))
                    ->whereNull('deleted_at'), // check only non-deleted members
            ],
            'password' => $passwordRules,
            // For initial member, role is optional (because the field is not sent)
            'role' => $isInitialAdmin
                ? ['nullable', Rule::in(array_column(MemberRole::cases(), 'value'))]
                : ['required', Rule::in(array_column(MemberRole::cases(), 'value'))],
            'locale' => 'nullable|string|in:ja,en',
            'appearance' => 'required|numeric|in:0,1,2',
            'status' => 'required|numeric|in:0,1',
            'email_verified' => 'nullable|numeric|in:0,1',
            'login_notification_mode' => 'nullable|numeric|in:0,1,2',
            'two_fa_mode' => 'nullable|numeric|in:0,1,2',
            'two_fa_passkey_enabled' => 'nullable|integer|in:0,1',
            'passkey_prompt_dismissed' => 'nullable|integer|in:0,1',
            'default_two_fa_method' => 'nullable|integer|in:0,1',
        ];

        // Email address confirmation validation
        if (! $isUpdate) {
            // Required when creating new
            $rules['email_confirmation'] = 'required|email|same:email';
        } elseif ($member && $this->input('email') !== $member->email) {
            // Also required when email address is changed during editing
            $rules['email_confirmation'] = 'required|email|same:email';
        }

        return $rules;
    }

    /**
     * Post-validation processing
     */
    protected function passedValidation()
    {
        // If two-factor authentication is enforced in global settings, override individual settings
        $globalTwoFaMode = (int) SecuritySetting::getValue('two_fa_mode', 3); // 3 = Follow profile settings

        if ($globalTwoFaMode !== 3) {
            // If global settings is other than 'Follow profile settings', enforce global settings
            $this->merge([
                'two_fa_mode' => $globalTwoFaMode,
            ]);
        }
    }

    /**
     * Add custom validation rules
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $member = $this->route('member');
            $twoFaMode = (int) $this->input('two_fa_mode', 0);

            // If attempting to enable 2FA (mode 1 or 2)
            if ($twoFaMode === 1 || $twoFaMode === 2) {
                // Check only when editing existing member
                if ($member) {
                    if (! $member->canEnableTwoFa()) {
                        $validator->errors()->add(
                            'two_fa_mode',
                            __('admin/members/validation.two_fa_cannot_enable')
                        );
                    }
                }
                // For new creation, OK if mail server is configured
                // (Because recovery codes and passkeys can be registered after creation)
                else {
                    $mailConfigured = \App\Services\MailServerValidatorService::isMailServerTested();
                    if (! $mailConfigured) {
                        $validator->errors()->add(
                            'two_fa_mode',
                            __('admin/members/validation.two_fa_cannot_enable_new_member')
                        );
                    }
                }
            }
        });
    }

    /**
     * Customize validation messages (if necessary)
     */
    public function messages(): array
    {
        return [
            'account_name.required' => __('admin/members/validation.account_name_required'),
            'account_name.alpha_num' => __('admin/members/validation.account_name_alpha_num'),
            'account_name.min' => __('admin/members/validation.account_name_length'),
            'account_name.max' => __('admin/members/validation.account_name_length'),
            'email.required' => __('admin/members/validation.email_required'),
            'email.email' => __('admin/members/validation.email_invalid'),
            'email.unique' => __('admin/members/validation.email_unique'),
            'password.required' => __('admin/members/validation.password_required'),
            'password.min' => __('admin/members/validation.password_min'),
            'password.confirmed' => __('admin/members/validation.password_confirmed'),
            'role.required' => __('admin/members/validation.role_required'),
            'role.in' => __('admin/members/validation.role_invalid'),
            'appearance.required' => __('admin/members/validation.appearance_required'),
            'appearance.in' => __('admin/members/validation.appearance_invalid'),
            'status.required' => __('admin/members/validation.status_required'),
            'status.in' => __('admin/members/validation.status_invalid'),
        ];
    }
}
