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
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
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

use Illuminate\Support\Facades\Storage;

/**
 * Content file management trait
 * Provides common functionality for file-based content storage
 * Available for use in both Core and plugins
 */
trait ManagesContentFiles
{
    /**
     * Disk for file storage
     */
    protected string $disk = 'local';

    /**
     * Base path for file storage
     */
    protected string $basePath;

    /**
     * File extension corresponding to editor type
     */
    protected array $extensions = [
        'markdown' => 'md',
        'html' => 'html',
        'blade' => 'blade.php',
    ];

    /**
     * Default language (language that does not append language code to filename)
     */
    protected string $defaultLocale = 'en';

    /**
     * Set base path
     */
    public function setBasePath(string $basePath): self
    {
        $this->basePath = $basePath;

        return $this;
    }

    /**
     * Set disk
     */
    public function setDisk(string $disk): self
    {
        $this->disk = $disk;

        return $this;
    }

    /**
     * Set default language
     */
    public function setDefaultLocale(string $locale): self
    {
        $this->defaultLocale = $locale;

        return $this;
    }

    /**
     * Load content from file
     *
     * @param  string  $slug  Slug
     * @param  string  $locale  Language code
     * @param  string  $editorType  Editor type
     * @return string|null File contents, or null if not exists
     */
    public function loadFromFile(string $slug, string $locale, string $editorType): ?string
    {
        $filePath = $this->getFilePath($slug, $locale, $editorType);

        if (Storage::disk($this->disk)->exists($filePath)) {
            return Storage::disk($this->disk)->get($filePath);
        }

        return null;
    }

    /**
     * Save content to file
     *
     * @param  string  $slug  Slug
     * @param  string  $locale  Language code
     * @param  string  $editorType  Editor type
     * @param  string  $content  Content
     * @return bool True on successful save
     */
    public function saveToFile(string $slug, string $locale, string $editorType, string $content): bool
    {
        $filePath = $this->getFilePath($slug, $locale, $editorType);

        // Create directory if it does not exist
        $directory = dirname($filePath);
        if (! Storage::disk($this->disk)->exists($directory)) {
            Storage::disk($this->disk)->makeDirectory($directory);
        }

        return Storage::disk($this->disk)->put($filePath, $content);
    }

    /**
     * Delete file
     *
     * @param  string  $slug  Slug
     * @param  string  $locale  Language code
     * @param  string  $editorType  Editor type
     * @return bool true on successful deletion
     */
    public function deleteFile(string $slug, string $locale, string $editorType): bool
    {
        $filePath = $this->getFilePath($slug, $locale, $editorType);

        if (Storage::disk($this->disk)->exists($filePath)) {
            return Storage::disk($this->disk)->delete($filePath);
        }

        return true;
    }

    /**
     * Delete files for all languages
     *
     * @param  string  $slug  Slug
     * @param  string  $editorType  Editor type
     * @param  array  $locales  Array of language codes
     * @return bool true when all deletions succeed
     */
    public function deleteAllFiles(string $slug, string $editorType, array $locales): bool
    {
        $success = true;
        foreach ($locales as $locale) {
            if (! $this->deleteFile($slug, $locale, $editorType)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Rename directory when slug changes
     *
     * @param  string  $oldSlug  Old slug
     * @param  string  $newSlug  New slug
     * @param  string  $editorType  Editor type (unused, kept for compatibility)
     * @param  array  $locales  Array of language codes (unused, kept for compatibility)
     * @return bool true on successful rename
     */
    public function renameFiles(string $oldSlug, string $newSlug, string $editorType, array $locales): bool
    {
        $oldDir = "{$this->basePath}/{$oldSlug}";
        $newDir = "{$this->basePath}/{$newSlug}";

        // Rename if directory exists
        if (Storage::disk($this->disk)->exists($oldDir)) {
            return Storage::disk($this->disk)->move($oldDir, $newDir);
        }

        return true;
    }

    /**
     * Delete the slug's directory
     *
     * @param  string  $slug  Slug
     * @return bool true on successful deletion
     */
    public function deleteDirectory(string $slug): bool
    {
        $directory = "{$this->basePath}/{$slug}";

        if (Storage::disk($this->disk)->exists($directory)) {
            return Storage::disk($this->disk)->deleteDirectory($directory);
        }

        return true;
    }

    /**
     * Retrieve file path
     * Structure: {basePath}/{slug}/content.{locale}.{extension}
     * Default language does not include language code in filename
     *
     * @param  string  $slug  Slug
     * @param  string  $locale  Language code
     * @param  string  $editorType  Editor type
     * @return string File path
     */
    public function getFilePath(string $slug, string $locale, string $editorType): string
    {
        $extension = $this->extensions[$editorType] ?? 'txt';

        // Default language does not include language code in filename
        if ($locale === $this->defaultLocale) {
            return "{$this->basePath}/{$slug}/content.{$extension}";
        }

        return "{$this->basePath}/{$slug}/content.{$locale}.{$extension}";
    }

    /**
     * Retrieve absolute file path
     *
     * @param  string  $slug  Slug
     * @param  string  $locale  Language code
     * @param  string  $editorType  Editor type
     * @return string Absolute file path
     */
    public function getAbsoluteFilePath(string $slug, string $locale, string $editorType): string
    {
        $relativePath = $this->getFilePath($slug, $locale, $editorType);

        return Storage::disk($this->disk)->path($relativePath);
    }

    /**
     * Check if file exists
     *
     * @param  string  $slug  Slug
     * @param  string  $locale  Language code
     * @param  string  $editorType  Editor type
     * @return bool true if file exists
     */
    public function fileExists(string $slug, string $locale, string $editorType): bool
    {
        $filePath = $this->getFilePath($slug, $locale, $editorType);

        return Storage::disk($this->disk)->exists($filePath);
    }

    /**
     * Add or overwrite extension
     *
     * @param  string  $editorType  Editor type
     * @param  string  $extension  Extension
     */
    public function addExtension(string $editorType, string $extension): self
    {
        $this->extensions[$editorType] = $extension;

        return $this;
    }

    /**
     * Get base path
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Get disk name
     */
    public function getDisk(): string
    {
        return $this->disk;
    }

    /**
     * Get default language
     */
    public function getDefaultLocale(): string
    {
        return $this->defaultLocale;
    }
}
