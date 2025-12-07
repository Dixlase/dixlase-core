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

namespace App\Services;

use App\Models\FrontPage;

/**
 * フロントページコンテンツサービス
 * コアのContentFileServiceを継承し、フロントページ固有の機能を追加
 */
class FrontPageContentService extends ContentFileService
{
    /**
     * コンストラクタ
     */
    public function __construct()
    {
        // storage/app/private/front/
        parent::__construct('front', 'local', 'en');
    }

    /**
     * ファイルパスを取得する（フロントページ用にオーバーライド）
     * 構造: {basePath}/content.{locale}.{extension}
     * デフォルト言語はファイル名に言語コードを付けない
     *
     * @param string $slug スラッグ（フロントページでは使用しない）
     * @param string $locale 言語コード
     * @param string $editorType エディタータイプ
     * @return string ファイルパス
     */
    public function getFilePath(string $slug, string $locale, string $editorType): string
    {
        $extension = $this->extensions[$editorType] ?? 'txt';
        
        // デフォルト言語はファイル名に言語コードを付けない
        if ($locale === $this->defaultLocale) {
            return "{$this->basePath}/content.{$extension}";
        }
        
        return "{$this->basePath}/content.{$locale}.{$extension}";
    }

    /**
     * フロントページのコンテンツを取得する（DB or ファイル）
     *
     * @param FrontPage $frontPage フロントページモデル
     * @param string|null $locale 言語コード（nullの場合は現在の言語）
     * @return string|null コンテンツ
     */
    public function getContent(FrontPage $frontPage, ?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();
        $storageType = $frontPage->storage_type->value ?? 'database';
        $editorType = $frontPage->editor_type->value ?? 'html';

        if ($storageType === 'file') {
            return $this->loadFromFile($frontPage->page_type, $locale, $editorType);
        }

        // データベースから取得（エディタータイプ別カラム）
        $contentColumn = 'content_' . $editorType;
        return $frontPage->{$contentColumn} ?? $frontPage->content ?? null;
    }

    /**
     * フロントページのコンテンツを保存する（DB or ファイル）
     *
     * @param FrontPage $frontPage フロントページモデル
     * @param string $content コンテンツ
     * @param string|null $locale 言語コード（nullの場合は現在の言語）
     * @return bool 保存成功時はtrue
     */
    public function saveContent(FrontPage $frontPage, string $content, ?string $locale = null): bool
    {
        $locale = $locale ?? app()->getLocale();
        $storageType = $frontPage->storage_type->value ?? 'database';
        $editorType = $frontPage->editor_type->value ?? 'html';

        if ($storageType === 'file') {
            return $this->saveToFile($frontPage->page_type, $locale, $editorType, $content);
        }

        // データベースに保存（コントローラーで行う）
        return true;
    }
}
