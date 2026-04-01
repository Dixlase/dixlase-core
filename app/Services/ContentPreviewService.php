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

namespace App\Services;

use App\Enums\ContentEditorType;
use App\Services\Editor\EditorManager;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * @api プラグイン/テーマから使用可能な安定APIです
 *
 * コンテンツプレビュー用レンダリングサービス
 *
 * エディタータイプに応じてコンテンツをHTMLに変換し、
 * ショートコードを展開します。管理画面のプレビュー機能で使用します。
 */
class ContentPreviewService
{
    public function __construct(
        protected EditorManager $editorManager,
    ) {}

    /**
     * コンテンツをプレビュー用HTMLにレンダリング
     *
     * @param  string  $content  生コンテンツ
     * @param  ContentEditorType  $editorType  エディタータイプ
     */
    public function render(string $content, ContentEditorType $editorType): string
    {
        if ($content === '') {
            return '';
        }

        try {
            $html = match ($editorType) {
                ContentEditorType::GUI => $this->editorManager->renderContent('gui', $content),
                ContentEditorType::MARKDOWN => Str::markdown($content),
                ContentEditorType::BLADE => Blade::render($content),
                ContentEditorType::HTML => $content,
            };

            return shortcode_parse($html);
        } catch (\Exception $e) {
            Log::warning('コンテンツプレビューのレンダリングに失敗', [
                'editor_type' => $editorType->slug(),
                'error' => $e->getMessage(),
            ]);

            return $content;
        }
    }

    /**
     * エディタータイプスラッグからコンテンツをプレビュー用HTMLにレンダリング
     *
     * @param  string  $content  生コンテンツ
     * @param  string  $editorTypeSlug  エディタータイプのスラッグ（例: 'html', 'markdown'）
     */
    public function renderFromSlug(string $content, string $editorTypeSlug): string
    {
        $editorType = ContentEditorType::tryFromSlug($editorTypeSlug);

        if ($editorType === null) {
            return '';
        }

        return $this->render($content, $editorType);
    }
}
