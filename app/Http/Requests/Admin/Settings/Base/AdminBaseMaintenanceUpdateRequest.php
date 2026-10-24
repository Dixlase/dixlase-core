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

namespace App\Http\Requests\Admin\Settings\Base;

use App\Helpers\DateTimeHelper;
use Illuminate\Foundation\Http\FormRequest;

class AdminBaseMaintenanceUpdateRequest extends FormRequest
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
        $rules = [
            'maintenance_mode' => 'nullable|boolean',
            'maintenance_message' => 'nullable|string|max:2000',
            'maintenance_auto_release' => 'nullable|boolean',
            'maintenance_start_at' => 'nullable|date',
        ];

        // End date/time is required when automatic release is enabled
        if ($this->input('maintenance_auto_release') == '1') {
            $rules['maintenance_release_at'] = 'required|date|after:maintenance_start_at';
        } else {
            // For manual release, end date/time is ignored even if entered (set to null)
            $rules['maintenance_release_at'] = 'nullable|date|after:maintenance_start_at';
        }

        return $rules;
    }

    /**
     * Data processing after validation
     */
    protected function prepareForValidation(): void
    {
        // Set end date/time to null for manual release
        if ($this->input('maintenance_auto_release') == '0') {
            $this->merge([
                'maintenance_release_at' => null,
            ]);
        }

        $this->readWindowInDisplayTimezone();
    }

    /**
     * The window fields are <x-form-datetime>, so they submit a wall clock
     * in the display timezone, not UTC. Read here, before validation, so
     * maintenance_release_at's `after:maintenance_start_at` compares two
     * normalized values; stored raw, a window typed for 10:00 opened at
     * 19:00 on a site displaying Asia/Tokyo.
     */
    private function readWindowInDisplayTimezone(): void
    {
        $merge = [];

        foreach (['maintenance_start_at', 'maintenance_release_at'] as $field) {
            $value = $this->input($field);

            if (! is_string($value) || trim($value) === '') {
                continue;
            }

            $utc = DateTimeHelper::fromInput($value);

            // An unparseable value is left exactly as submitted, so the
            // field's `date` rule reports it. Merging the null would pass
            // `nullable` and drop what was typed without saying so.
            if ($utc !== null) {
                $merge[$field] = $utc;
            }
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }
}
