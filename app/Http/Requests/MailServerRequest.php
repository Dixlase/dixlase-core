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

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Unified mail server settings request
 * Used in both admin panel and installer
 */
class MailServerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for mail server settings
     */
    public function rules()
    {
        return [
            'mail_mailer' => ['required', 'string', Rule::in(array_keys(trans('mail-server/config.mailers')))],
            'mail_host' => 'nullable|string',
            'mail_port' => 'nullable|numeric',
            'mail_username' => 'nullable|string',
            'mail_password' => 'nullable|string',
            'mail_encryption' => ['nullable', 'string', Rule::in(array_keys(trans('mail-server/config.encryptions')))],
            'mail_from_address' => 'nullable|email|max:255',
            'mail_from_name' => 'nullable|string|max:255',
        ];
    }

    /**
     * Customize error messages
     */
    public function messages()
    {
        return [
            'mail_mailer.required' => __('mail-server/config.validation.mail_mailer_required'),
            'mail_mailer.in' => __('mail-server/config.validation.mail_mailer_required'),
            'mail_host.required' => __('mail-server/config.validation.mail_host_required'),
            'mail_port.required' => __('mail-server/config.validation.mail_port_required'),
            'mail_port.numeric' => __('mail-server/config.validation.mail_port_numeric'),
            'mail_from_address.email' => __('mail-server/config.validation.mail_from_address_email'),
            'mail_encryption.in' => __('mail-server/config.validation.mail_mailer_required'),
        ];
    }
}
