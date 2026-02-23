<?php

/**
 * This file is part of Dixlase Legal.
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

namespace Plugins\DixlaseLegal\App\Http\Requests\Admin;

use App\Enums\ContentEditorType;
use App\Enums\ContentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 法務ページコンテンツ更新用FormRequest
 */
class DixlaseLegalUpdateContentRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'max:500000'],
            'editor_type' => ['required', Rule::in([ContentEditorType::HTML->value, ContentEditorType::MARKDOWN->value])],
            'status' => ['required', Rule::in([ContentStatus::DRAFT->value, ContentStatus::PUBLISHED->value, ContentStatus::SCHEDULED->value])],
            'published_at' => ['nullable', 'date', 'required_if:status,' . ContentStatus::SCHEDULED->value],
        ];
    }

    /**
     * カスタムエラーメッセージ
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.max' => __('dixlase-legal::admin/legal-pages/contents.validation.title_max'),
            'content.max' => __('dixlase-legal::admin/legal-pages/contents.validation.content_max'),
            'editor_type.required' => __('dixlase-legal::admin/legal-pages/contents.validation.editor_type_required'),
            'editor_type.in' => __('dixlase-legal::admin/legal-pages/contents.validation.editor_type_in'),
            'status.required' => __('dixlase-legal::admin/legal-pages/contents.validation.status_required'),
            'status.in' => __('dixlase-legal::admin/legal-pages/contents.validation.status_in'),
            'published_at.required_if' => __('dixlase-legal::admin/legal-pages/contents.validation.published_at_required'),
            'published_at.date' => __('dixlase-legal::admin/legal-pages/contents.validation.published_at_date'),
        ];
    }
}
