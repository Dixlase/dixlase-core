<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

namespace App\Http\Requests\Admin\Settings\Systems;

use App\Contracts\Backup\BackupServiceInterface;
use Illuminate\Foundation\Http\FormRequest;

class AdminSystemBackupSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $availableTargets = app(BackupServiceInterface::class)->getAvailableTargets();

        return [
            'default_targets' => ['required', 'array', 'min:1'],
            'default_targets.*' => ['string', 'in:'.implode(',', $availableTargets)],
            'default_retention_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            // Unchecked toggles are simply absent from the request
            'exclude_node_modules' => ['nullable', 'boolean'],
            'exclude_vendor' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'default_targets.required' => __('admin/settings/systems/backup/settings.validation.targets_required'),
            'default_targets.min' => __('admin/settings/systems/backup/settings.validation.targets_required'),
            'default_targets.*.in' => __('admin/settings/systems/backup/settings.validation.target_invalid'),
            'default_retention_days.integer' => __('admin/settings/systems/backup/settings.validation.retention_invalid'),
            'default_retention_days.min' => __('admin/settings/systems/backup/settings.validation.retention_invalid'),
            'default_retention_days.max' => __('admin/settings/systems/backup/settings.validation.retention_invalid'),
        ];
    }
}
