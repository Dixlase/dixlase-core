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

class AdminSecurityAuthenticationUpdateRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            // 二段階認証基本設定
            'two_fa_force_mode' => ['nullable', 'integer', 'in:0,1,2,3'],
            'two_fa_passkey_mode' => ['nullable', 'integer', 'in:0,1,2'],
            'two_fa_default_method' => ['nullable', 'integer', 'in:0,1'],

            // 二段階認証詳細設定
            'two_fa_expire_minutes' => ['nullable', 'integer', 'min:1', 'max:60'],
            'two_fa_resend_interval_seconds' => ['nullable', 'integer', 'min:30', 'max:300'],
            'two_fa_max_attempts' => ['nullable', 'integer', 'min:3', 'max:10'],
            'two_fa_attempt_window' => ['nullable', 'integer', 'min:5', 'max:60'],
            'two_fa_lockout_duration' => ['nullable', 'integer', 'min:5', 'max:1440'],
            'two_fa_lockout_notification_enabled' => ['nullable', 'boolean'],
            'two_fa_recovery_codes_count' => ['nullable', 'integer', 'min:5', 'max:20'],
            'two_fa_recovery_code_regenerate_interval' => ['nullable', 'integer', 'min:1', 'max:365'],
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'two_fa_lockout_notification_enabled' => $this->has('two_fa_lockout_notification_enabled') ? true : false,
        ]);
    }
}
