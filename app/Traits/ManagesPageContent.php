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

namespace App\Traits;

use App\Enums\ContentEditorType;
use App\Enums\ContentStorageType;
use App\Services\PageContentService;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * ページコンテンツ管理トレイト
 *
 * コントローラーで使用し、ページコンテンツの保存・読み込み・レンダリングを簡単に行えます。
 * DixlasePagesプラグインやフロントページ編集機能で共通使用します。
 */
trait ManagesPageContent
{
    /**
     * PageContentServiceのインスタンスを取得
     */
    protected function getContentService(?string $baseDirectory = null): PageContentService
    {
        $service = app(PageContentService::class);

        if ($baseDirectory) {
            $service->setBaseDirectory($baseDirectory);
        }

        return $service;
    }

    /**
     * コンテンツを保存
     *
     * @param  string  $identifier  ページのスラッグやID
     * @param  string  $content  コンテンツ
     * @param  string|ContentStorageType  $storageType  保存方法
     * @param  string|ContentEditorType  $editorType  エディタータイプ
     * @param  string|null  $baseDirectory  ベースディレクトリ（FILE保存時）
     */
    protected function savePageContent(
        string $identifier,
        string $content,
        string|ContentStorageType $storageType,
        string|ContentEditorType $editorType,
        ?string $baseDirectory = null
    ): array {
        $service = $this->getContentService($baseDirectory);

        $storageType = is_string($storageType)
            ? ContentStorageType::fromSlug($storageType)
            : $storageType;

        $editorType = is_string($editorType)
            ? ContentEditorType::fromSlug($editorType)
            : $editorType;

        return $service->saveContent($identifier, $content, $storageType, $editorType);
    }

    /**
     * コンテンツを読み込み
     *
     * @param  string  $identifier  ページのスラッグやID
     * @param  string|ContentStorageType  $storageType  保存方法
     * @param  string|ContentEditorType  $editorType  エディタータイプ
     * @param  string|null  $dbContent  DB保存の場合のコンテンツ
     * @param  string|null  $baseDirectory  ベースディレクトリ（FILE保存時）
     */
    protected function loadPageContent(
        string $identifier,
        string|ContentStorageType $storageType,
        string|ContentEditorType $editorType,
        ?string $dbContent = null,
        ?string $baseDirectory = null
    ): ?string {
        $service = $this->getContentService($baseDirectory);

        $storageType = is_string($storageType)
            ? ContentStorageType::fromSlug($storageType)
            : $storageType;

        $editorType = is_string($editorType)
            ? ContentEditorType::fromSlug($editorType)
            : $editorType;

        return $service->loadContent($identifier, $storageType, $editorType, $dbContent);
    }

    /**
     * コンテンツをレンダリング
     *
     * @param  string  $content  コンテンツ
     * @param  string|ContentEditorType  $editorType  エディタータイプ
     * @param  string|ContentStorageType  $storageType  保存方法
     */
    protected function renderPageContent(
        string $content,
        string|ContentEditorType $editorType,
        string|ContentStorageType $storageType
    ): string {
        $service = $this->getContentService();

        $editorType = is_string($editorType)
            ? ContentEditorType::fromSlug($editorType)
            : $editorType;

        $storageType = is_string($storageType)
            ? ContentStorageType::fromSlug($storageType)
            : $storageType;

        return $service->renderContent($content, $editorType, $storageType);
    }

    /**
     * Bladeビューとしてレンダリング
     *
     * @param  string  $identifier  ページのスラッグやID
     * @param  array  $data  ビューに渡すデータ
     * @param  string|null  $baseDirectory  ベースディレクトリ
     */
    protected function renderBladeView(
        string $identifier,
        array $data = [],
        ?string $baseDirectory = null
    ): string {
        $service = $this->getContentService($baseDirectory);

        return $service->renderBladeView($identifier, $data);
    }

    /**
     * ファイルが存在するか確認
     *
     * @param  string  $identifier  ページのスラッグやID
     * @param  string|ContentEditorType  $editorType  エディタータイプ
     * @param  string|null  $baseDirectory  ベースディレクトリ
     */
    protected function contentFileExists(
        string $identifier,
        string|ContentEditorType $editorType,
        ?string $baseDirectory = null
    ): bool {
        $service = $this->getContentService($baseDirectory);

        $editorType = is_string($editorType)
            ? ContentEditorType::fromSlug($editorType)
            : $editorType;

        return $service->fileExists($identifier, $editorType);
    }

    /**
     * ファイルパスを取得
     *
     * @param  string  $identifier  ページのスラッグやID
     * @param  string|ContentEditorType  $editorType  エディタータイプ
     * @param  string|null  $baseDirectory  ベースディレクトリ
     */
    protected function getContentFilePath(
        string $identifier,
        string|ContentEditorType $editorType,
        ?string $baseDirectory = null
    ): string {
        $service = $this->getContentService($baseDirectory);

        $editorType = is_string($editorType)
            ? ContentEditorType::fromSlug($editorType)
            : $editorType;

        return $service->getFilePath($identifier, $editorType);
    }

    /**
     * ファイルを削除
     *
     * @param  string  $identifier  ページのスラッグやID
     * @param  string|ContentEditorType  $editorType  エディタータイプ
     * @param  string|null  $baseDirectory  ベースディレクトリ
     */
    protected function deleteContentFile(
        string $identifier,
        string|ContentEditorType $editorType,
        ?string $baseDirectory = null
    ): bool {
        $service = $this->getContentService($baseDirectory);

        $editorType = is_string($editorType)
            ? ContentEditorType::fromSlug($editorType)
            : $editorType;

        return $service->deleteFile($identifier, $editorType);
    }

    /**
     * 保存方法を変更（マイグレーション）
     *
     * @param  string  $identifier  ページのスラッグやID
     * @param  string|ContentStorageType  $fromStorage  元の保存方法
     * @param  string|ContentStorageType  $toStorage  新しい保存方法
     * @param  string|ContentEditorType  $editorType  エディタータイプ
     * @param  string|null  $dbContent  DB保存の場合のコンテンツ
     * @param  string|null  $baseDirectory  ベースディレクトリ
     */
    protected function migrateContentStorage(
        string $identifier,
        string|ContentStorageType $fromStorage,
        string|ContentStorageType $toStorage,
        string|ContentEditorType $editorType,
        ?string $dbContent = null,
        ?string $baseDirectory = null
    ): array {
        $service = $this->getContentService($baseDirectory);

        $fromStorage = is_string($fromStorage)
            ? ContentStorageType::fromSlug($fromStorage)
            : $fromStorage;

        $toStorage = is_string($toStorage)
            ? ContentStorageType::fromSlug($toStorage)
            : $toStorage;

        $editorType = is_string($editorType)
            ? ContentEditorType::fromSlug($editorType)
            : $editorType;

        return $service->migrateStorage(
            $identifier,
            $fromStorage,
            $toStorage,
            $editorType,
            $dbContent
        );
    }

    /**
     * 利用可能なエディタータイプを取得
     *
     * @param  string|ContentStorageType  $storageType  保存方法
     */
    protected function getAvailableEditorTypes(string|ContentStorageType $storageType): array
    {
        $storageType = is_string($storageType)
            ? ContentStorageType::fromSlug($storageType)
            : $storageType;

        return ContentEditorType::optionsFor($storageType);
    }

    /**
     * 利用可能なエディタータイプを説明付きで取得
     *
     * @param  string|ContentStorageType  $storageType  保存方法
     */
    protected function getAvailableEditorTypesWithDescription(string|ContentStorageType $storageType): array
    {
        $storageType = is_string($storageType)
            ? ContentStorageType::fromSlug($storageType)
            : $storageType;

        return ContentEditorType::optionsWithDescriptionFor($storageType);
    }
}
