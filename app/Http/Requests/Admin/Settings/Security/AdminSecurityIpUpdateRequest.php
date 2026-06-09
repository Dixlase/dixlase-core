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

use App\Helpers\IpAccessControlHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AdminSecurityIpUpdateRequest extends FormRequest
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
            'enable_allowed_admin_ips' => 'boolean',
            'allowed_admin_ips' => 'nullable|string',
            'enable_blocked_admin_ips' => 'boolean',
            'blocked_admin_ips' => 'nullable|string',
            'enable_allowed_front_ips' => 'boolean',
            'allowed_front_ips' => 'nullable|string',
            'enable_blocked_front_ips' => 'boolean',
            'blocked_front_ips' => 'nullable|string',
        ];
    }

    /**
     * Guard the admin from locking itself out of the admin panel.
     *
     * The IP this check uses is the same `$request->ip()` that AdminIpFilter
     * enforces, so it accurately predicts whether saving the submitted lists
     * would deny the current admin on its next request.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            // Format validation: reject any entry that is not a valid IPv4 / IPv6
            // address or CIDR range. Empty lists are fine (no entries means no
            // invalid entries).
            foreach (['allowed_admin_ips', 'blocked_admin_ips', 'allowed_front_ips', 'blocked_front_ips'] as $field) {
                $invalid = IpAccessControlHelper::invalidEntries((string) $this->input($field, ''));
                if ($invalid !== []) {
                    $validator->errors()->add(
                        $field,
                        __('admin/settings/security/ip.invalid_entries', ['entries' => implode(', ', $invalid)]),
                    );
                }
            }

            // Self-lockout guard for admin lists.
            $currentIp = $this->ip();

            if ($currentIp === null) {
                return;
            }

            if ($this->boolean('enable_allowed_admin_ips')
                && ! IpAccessControlHelper::listContainsIp($currentIp, (string) $this->input('allowed_admin_ips', ''))) {
                $validator->errors()->add(
                    'allowed_admin_ips',
                    __('admin/settings/security/ip.lockout_allowlist', ['ip' => $currentIp]),
                );
            }

            if ($this->boolean('enable_blocked_admin_ips')
                && IpAccessControlHelper::listContainsIp($currentIp, (string) $this->input('blocked_admin_ips', ''))) {
                $validator->errors()->add(
                    'blocked_admin_ips',
                    __('admin/settings/security/ip.lockout_blocklist', ['ip' => $currentIp]),
                );
            }
        });
    }
}
