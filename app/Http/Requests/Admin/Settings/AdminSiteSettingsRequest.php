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

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminSiteSettingsRequest extends FormRequest
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
     * Convert toggle fields to boolean
     */
    public function prepareForValidation()
    {
        $this->merge([
            'force_ssl' => filter_var($this->input('force_ssl'), FILTER_VALIDATE_BOOLEAN),
            'maintenance_mode' => filter_var($this->input('maintenance_mode'), FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    /**
     * Configure validation rules
     */
    public function rules()
    {
        $availableLocales = array_keys(config('admin.locale.available', []));

        return [
            'app_name' => 'required|string|max:60',
            'site_description' => 'nullable|string|max:500',
            'site_keywords' => 'nullable|string|max:500',
            'locale' => ['required', Rule::in($availableLocales)],
            'timezone' => 'required|timezone',
            'mail_mailer' => ['required', Rule::in(array_keys(trans('mail-server/config.mailers')))],
            'mail_host' => 'nullable|string',
            'mail_port' => 'nullable|numeric',
            'mail_username' => 'nullable|string',
            'mail_password' => 'nullable|string',
            'mail_encryption' => ['nullable', Rule::in(array_keys(trans('mail-server/config.encryptions')))],
            'mail_from_address' => 'nullable|email|max:255',
            'mail_from_name' => 'nullable|string|max:255',
            'maintenance_mode' => 'required|boolean',
            'maintenance_message' => 'nullable|string',
            'system_admin_email' => 'nullable|email|max:255',
            'admin_url' => 'required|string|max:255',
            'force_ssl' => 'nullable|boolean',
            'default_ogp_image_id' => 'nullable|exists:media,id',
            'twitter_card_type' => ['nullable', Rule::in(['summary', 'summary_large_image', 'app', 'player'])],
        ];
    }

    /**
     * Customize error messages
     */
    public function messages()
    {
        return [
            'app_name.required' => __('admin/settings/base/validation.app_name_required'),
            'locale.required' => __('admin/settings/base/validation.locale_required'),
            'timezone.timezone' => __('admin/settings/base/validation.timezone_invalid'),
            'mail_mailer.required' => __('mail-server/config.validation.mail_mailer_required'),
            'mail_host.required' => __('mail-server/config.validation.mail_host_required'),
            'mail_port.required' => __('mail-server/config.validation.mail_port_required'),
            'mail_port.numeric' => __('mail-server/config.validation.mail_port_numeric'),
            'maintenance_mode.required' => __('admin/settings/base/validation.maintenance_mode_required'),
        ];
    }
}
