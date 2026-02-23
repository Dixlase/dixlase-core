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

use App\Services\LegalPageService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * 法務ページURL更新リクエストのバリデーション
 */
class DixlaseLegalUpdateLegalPagesRequest extends FormRequest
{
    /**
     * リクエストの認可判定
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * バリデーションルールの設定
     *
     * LegalPageService のページ種別から動的にルールを生成する。
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $legalPageService = app(LegalPageService::class);
        $pageTypes = $legalPageService->getPageTypes();

        $rules = [];

        foreach (array_keys($pageTypes) as $slug) {
            $rules["urls.{$slug}"] = ['nullable', 'url', 'max:2048'];
        }

        return $rules;
    }

    /**
     * エラーメッセージのカスタマイズ
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $legalPageService = app(LegalPageService::class);
        $pageTypes = $legalPageService->getPageTypes();

        $messages = [];

        foreach ($pageTypes as $slug => $type) {
            $name = __($type['name']);
            $messages["urls.{$slug}.url"] = __('dixlase-legal::admin/legal-pages/index.validation.url', ['name' => $name]);
            $messages["urls.{$slug}.max"] = __('dixlase-legal::admin/legal-pages/index.validation.max', ['name' => $name]);
        }

        return $messages;
    }
}
