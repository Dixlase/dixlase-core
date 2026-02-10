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

namespace App\Http\Requests\Admin\Settings\Base;

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

        // 自動解除が有効な場合、終了日時は必須
        if ($this->input('maintenance_auto_release') == '1') {
            $rules['maintenance_release_at'] = 'required|date|after:maintenance_start_at';
        } else {
            // 手動解除の場合、終了日時は入力されていても無視（nullにする）
            $rules['maintenance_release_at'] = 'nullable|date|after:maintenance_start_at';
        }

        return $rules;
    }

    /**
     * バリデーション後のデータ加工
     */
    protected function prepareForValidation(): void
    {
        // 手動解除の場合、終了日時をnullにする
        if ($this->input('maintenance_auto_release') == '0') {
            $this->merge([
                'maintenance_release_at' => null,
            ]);
        }
    }
}
