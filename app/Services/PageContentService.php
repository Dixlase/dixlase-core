<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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

namespace App\Services;

use App\Enums\ContentEditorType;
use App\Enums\ContentStorageType;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Parsedown;

/**
 * Page content management service
 *
 * Manages page content, front page design, and other
 * editable content saving, loading, and conversion
 */
class PageContentService
{
    /**
     * Base directory for file storage
     */
    protected string $baseDirectory = 'pages';

    /**
     * Set base directory
     */
    public function setBaseDirectory(string $directory): self
    {
        $this->baseDirectory = $directory;

        return $this;
    }

    /**
     * Save content
     *
     * @param  string  $identifier  Page slug or ID
     * @param  string  $content  Content
     * @param  ContentStorageType  $storageType  Storage method
     * @param  ContentEditorType  $editorType  Editor type
     * @return array ['success' => bool, 'path' => string|null, 'message' => string]
     */
    public function saveContent(
        string $identifier,
        string $content,
        ContentStorageType $storageType,
        ContentEditorType $editorType
    ): array {
        try {
            if ($storageType === ContentStorageType::FILE) {
                return $this->saveToFile($identifier, $content, $editorType);
            }

            // For DATABASE, save at caller side
            return [
                'success' => true,
                'path' => null,
                'message' => 'Content will be saved to database',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to save content', [
                'identifier' => $identifier,
                'storage_type' => $storageType->value,
                'editor_type' => $editorType->value,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'path' => null,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Load content
     *
     * @param  string  $identifier  Page slug or ID
     * @param  ContentStorageType  $storageType  Storage method
     * @param  ContentEditorType  $editorType  Editor type
     * @param  string|null  $dbContent  Content for DB storage
     */
    public function loadContent(
        string $identifier,
        ContentStorageType $storageType,
        ContentEditorType $editorType,
        ?string $dbContent = null
    ): ?string {
        try {
            if ($storageType === ContentStorageType::FILE) {
                return $this->loadFromFile($identifier, $editorType);
            }

            // For DATABASE
            return $dbContent;
        } catch (\Exception $e) {
            Log::error('Failed to load content', [
                'identifier' => $identifier,
                'storage_type' => $storageType->value,
                'editor_type' => $editorType->value,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Convert content for rendering
     *
     * @param  string  $content  Content
     * @param  ContentEditorType  $editorType  Editor type
     * @param  ContentStorageType  $storageType  Storage method
     */
    public function renderContent(
        string $content,
        ContentEditorType $editorType,
        ContentStorageType $storageType
    ): string {
        try {
            return match ($editorType) {
                ContentEditorType::MARKDOWN => $this->renderMarkdown($content),
                ContentEditorType::HTML => $content,
                ContentEditorType::BLADE => $storageType === ContentStorageType::FILE
                    ? $content // Blade files are rendered separately
                    : $content,
                ContentEditorType::GUI => $this->renderGui($content),
            };
        } catch (\Exception $e) {
            Log::error('Failed to render content', [
                'editor_type' => $editorType->value,
                'error' => $e->getMessage(),
            ]);

            return $content;
        }
    }

    /**
     * Save to file
     */
    protected function saveToFile(
        string $identifier,
        string $content,
        ContentEditorType $editorType
    ): array {
        $filename = $this->generateFilename($identifier, $editorType);
        $path = storage_path("app/{$this->baseDirectory}/{$filename}");

        // Create directory if it doesn't exist
        $directory = dirname($path);
        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        // Save to file
        File::put($path, $content);

        return [
            'success' => true,
            'path' => $path,
            'message' => "Content saved to file: {$filename}",
        ];
    }

    /**
     * Load from file
     */
    protected function loadFromFile(
        string $identifier,
        ContentEditorType $editorType
    ): ?string {
        $filename = $this->generateFilename($identifier, $editorType);
        $path = storage_path("app/{$this->baseDirectory}/{$filename}");

        if (! File::exists($path)) {
            return null;
        }

        return File::get($path);
    }

    /**
     * Delete file
     */
    public function deleteFile(string $identifier, ContentEditorType $editorType): bool
    {
        try {
            $filename = $this->generateFilename($identifier, $editorType);
            $path = storage_path("app/{$this->baseDirectory}/{$filename}");

            if (File::exists($path)) {
                File::delete($path);

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Failed to delete content file', [
                'identifier' => $identifier,
                'editor_type' => $editorType->value,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Generate filename
     */
    protected function generateFilename(string $identifier, ContentEditorType $editorType): string
    {
        $slug = Str::slug($identifier);
        $extension = $editorType->fileExtension();

        return "{$slug}.{$extension}";
    }

    /**
     * Get file path
     */
    public function getFilePath(string $identifier, ContentEditorType $editorType): string
    {
        $filename = $this->generateFilename($identifier, $editorType);

        return storage_path("app/{$this->baseDirectory}/{$filename}");
    }

    /**
     * Check if file exists
     */
    public function fileExists(string $identifier, ContentEditorType $editorType): bool
    {
        $path = $this->getFilePath($identifier, $editorType);

        return File::exists($path);
    }

    /**
     * Render Markdown
     */
    protected function renderMarkdown(string $content): string
    {
        $parsedown = new Parsedown();
        $parsedown->setSafeMode(false); // Allow HTML tags

        return $parsedown->text($content);
    }

    /**
     * Render GUI content (future implementation)
     */
    protected function renderGui(string $content): string
    {
        // TODO: Convert GUI editor JSON format to HTML
        // Return as-is for now
        return $content;
    }

    /**
     * Render as Blade view
     *
     * @param  string  $identifier  Page slug or ID
     * @param  array  $data  Data to pass to view
     */
    public function renderBladeView(string $identifier, array $data = []): string
    {
        try {
            $viewPath = "{$this->baseDirectory}.".str_replace('/', '.', Str::slug($identifier));

            if (view()->exists($viewPath)) {
                return view($viewPath, $data)->render();
            }

            return '';
        } catch (\Exception $e) {
            Log::error('Failed to render Blade view', [
                'identifier' => $identifier,
                'error' => $e->getMessage(),
            ]);

            return '';
        }
    }

    /**
     * Change storage method (migration)
     *
     * @param  string  $identifier  Page slug or ID
     * @param  ContentStorageType  $fromStorage  Original storage method
     * @param  ContentStorageType  $toStorage  New storage method
     * @param  ContentEditorType  $editorType  Editor type
     * @param  string|null  $dbContent  Content for DB storage
     * @return array ['success' => bool, 'content' => string|null, 'message' => string]
     */
    public function migrateStorage(
        string $identifier,
        ContentStorageType $fromStorage,
        ContentStorageType $toStorage,
        ContentEditorType $editorType,
        ?string $dbContent = null
    ): array {
        try {
            // Load content
            $content = $this->loadContent($identifier, $fromStorage, $editorType, $dbContent);

            if ($content === null) {
                return [
                    'success' => false,
                    'content' => null,
                    'message' => 'Content not found',
                ];
            }

            // Save with new storage method
            $result = $this->saveContent($identifier, $content, $toStorage, $editorType);

            // Delete original file (when FILE → DATABASE)
            if ($fromStorage === ContentStorageType::FILE && $toStorage === ContentStorageType::DATABASE) {
                $this->deleteFile($identifier, $editorType);
            }

            return [
                'success' => $result['success'],
                'content' => $content,
                'message' => $result['message'],
            ];
        } catch (\Exception $e) {
            Log::error('Failed to migrate storage', [
                'identifier' => $identifier,
                'from_storage' => $fromStorage->value,
                'to_storage' => $toStorage->value,
                'editor_type' => $editorType->value,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'content' => null,
                'message' => $e->getMessage(),
            ];
        }
    }
}
