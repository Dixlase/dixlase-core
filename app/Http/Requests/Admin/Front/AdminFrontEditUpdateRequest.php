<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

namespace App\Http\Requests\Admin\Front;

use Illuminate\Foundation\Http\FormRequest;

class AdminFrontEditUpdateRequest extends FormRequest
{
    /**
     * リクエストの認可判定
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * バリデーションルール
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // 保存形式は初回作成時のみ選択可能。編集時はフィールドを受け付けない。
        return [
            'content' => ['nullable', 'string', 'max:500000'],
            'custom_js' => ['nullable', 'string', 'max:500000'],
            'custom_css' => ['nullable', 'string', 'max:500000'],
        ];
    }

    /**
     * バリデーションエラーメッセージ
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'content.max' => __('admin/front.edit.validation.content_max'),
            'custom_js.max' => __('admin/front.edit.validation.custom_js_max'),
            'custom_css.max' => __('admin/front.edit.validation.custom_css_max'),
        ];
    }
}
