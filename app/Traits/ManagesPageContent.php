<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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

namespace App\Traits;

use App\Enums\ContentEditorType;
use App\Enums\ContentStorageType;
use App\Services\PageContentService;

/**
 * @internal Core use only. Do not reference from plugins/themes
 *
 * Page content management trait
 *
 * Used in controllers to easily save, load, and render page content.
 * Typically consumed by a page-tree plugin and by the front-page editing
 * functionality.
 */
trait ManagesPageContent
{
    /**
     * Get an instance of PageContentService
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
     * Save content
     *
     * @param  string  $identifier  Page slug or ID
     * @param  string  $content  Content
     * @param  string|ContentStorageType  $storageType  Save method
     * @param  string|ContentEditorType  $editorType  Editor type
     * @param  string|null  $baseDirectory  Base directory (when saving to FILE)
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
     * Load content
     *
     * @param  string  $identifier  Page slug or ID
     * @param  string|ContentStorageType  $storageType  Save method
     * @param  string|ContentEditorType  $editorType  Editor type
     * @param  string|null  $dbContent  Content when saved in DB
     * @param  string|null  $baseDirectory  Base directory (when saving to FILE)
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
     * Render content
     *
     * @param  string  $content  Content
     * @param  string|ContentEditorType  $editorType  Editor type
     * @param  string|ContentStorageType  $storageType  Save method
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
     * Render as Blade view
     *
     * @param  string  $identifier  Page slug or ID
     * @param  array  $data  Data to pass to view
     * @param  string|null  $baseDirectory  Base directory
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
     * Check if file exists
     *
     * @param  string  $identifier  Page slug or ID
     * @param  string|ContentEditorType  $editorType  Editor type
     * @param  string|null  $baseDirectory  Base directory
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
     * Get file path
     *
     * @param  string  $identifier  Page slug or ID
     * @param  string|ContentEditorType  $editorType  Editor type
     * @param  string|null  $baseDirectory  Base directory
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
     * Delete file
     *
     * @param  string  $identifier  Page slug or ID
     * @param  string|ContentEditorType  $editorType  Editor type
     * @param  string|null  $baseDirectory  Base directory
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
     * Change storage method (migration)
     *
     * @param  string  $identifier  Page slug or ID
     * @param  string|ContentStorageType  $fromStorage  Original storage method
     * @param  string|ContentStorageType  $toStorage  New storage method
     * @param  string|ContentEditorType  $editorType  Editor type
     * @param  string|null  $dbContent  Content when saved in DB
     * @param  string|null  $baseDirectory  Base directory
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
     * Get available editor types
     *
     * @param  string|ContentStorageType  $storageType  Save method
     */
    protected function getAvailableEditorTypes(string|ContentStorageType $storageType): array
    {
        $storageType = is_string($storageType)
            ? ContentStorageType::fromSlug($storageType)
            : $storageType;

        return ContentEditorType::optionsFor($storageType);
    }

    /**
     * Get available editor types with descriptions
     *
     * @param  string|ContentStorageType  $storageType  Save method
     */
    protected function getAvailableEditorTypesWithDescription(string|ContentStorageType $storageType): array
    {
        $storageType = is_string($storageType)
            ? ContentStorageType::fromSlug($storageType)
            : $storageType;

        return ContentEditorType::optionsWithDescriptionFor($storageType);
    }
}
