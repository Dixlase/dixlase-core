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

class AdminSecurityLoginUpdateRequest extends FormRequest
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
            // ログイン識別子モード設定
            'login_identifier_mode' => 'nullable|integer|in:0,1,2',

            // ログイン通知設定
            'login_notification_mode' => 'nullable|integer|in:0,1,2,3',
            'login_notification_send_to_system' => 'nullable|boolean',
            'login_notification_system_email' => 'nullable|email|max:255',

            // ログイン試行制限設定
            'login_attempt_limit_enabled' => 'boolean',
            'login_attempt_max_attempts' => 'required|integer|min:1|max:100',
            'login_attempt_max_attempts_ip' => 'required|integer|min:1|max:200',
            'login_attempt_time_window' => 'required|integer|min:1|max:1440',
            'login_attempt_lockout_duration' => 'required|integer|min:1|max:10080',
            'login_attempt_lockout_notification_enabled' => 'boolean',

        ];
    }
}
