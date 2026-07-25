<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

namespace App\Services;

use App\Contracts\TranslationResolver;
use App\Enums\ContentStorageType;
use App\Models\FrontPage;
use Illuminate\Support\Facades\App;

/**
 * Front page content service
 * Inherits from Core's ContentFileService and adds front page specific functionality
 */
class FrontPageContentService extends ContentFileService
{
    /**
     * Constructor
     */
    public function __construct()
    {
        // storage/app/private/core/front/
        parent::__construct('core/front', 'local', 'en');
    }

    /**
     * Retrieve file path (overridden for front page)
     * Core operates with a single language, so language code is not appended to file name
     * Multilingual support is handled by the multilingual plugin
     *
     * @param  string  $slug  Slug (not used on front page)
     * @param  string  $locale  Language code (unused in Core, kept for plugin extension)
     * @param  string  $editorType  Editor type
     * @return string File path
     */
    public function getFilePath(string $slug, string $locale, string $editorType): string
    {
        $extension = $this->extensions[$editorType] ?? 'txt';

        return "{$this->basePath}/content.{$extension}";
    }

    /**
     * Retrieve front page content (DB or file)
     *
     * @param  FrontPage  $frontPage  Front page model
     * @param  string|null  $locale  Language code (current language if null)
     * @return string|null Content
     */
    public function getContent(FrontPage $frontPage, ?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();

        // Consult a registered TranslationResolver (DixlaseMultilingual) first
        // regardless of storage_type. The translation editor writes to the
        // polymorphic translations table even for FILE-backed entities, so a
        // per-locale override can exist independently of where the primary
        // content lives. We fall through to the storage-type branch below
        // when no published translation row exists for this locale.
        if (App::bound(TranslationResolver::class)) {
            $translated = App::make(TranslationResolver::class)
                ->resolve($frontPage, 'content', $locale);
            if (is_string($translated)) {
                return $translated;
            }
        }

        if ($frontPage->storage_type === ContentStorageType::FILE) {
            return $this->loadFromFile($frontPage->page_type, $locale, $frontPage->editor_type->slug());
        }

        // DB storage with no translation override: the row's primary-locale
        // column value is the canonical content.
        return $frontPage->content ?? null;
    }

    /**
     * Save front page content (DB or file)
     *
     * @param  FrontPage  $frontPage  Front page model
     * @param  string  $content  Content
     * @param  string|null  $locale  Language code (current language if null)
     * @return bool True on successful save
     */
    public function saveContent(FrontPage $frontPage, string $content, ?string $locale = null): bool
    {
        $locale = $locale ?? app()->getLocale();

        if ($frontPage->storage_type === ContentStorageType::FILE) {
            return $this->saveToFile($frontPage->page_type, $locale, $frontPage->editor_type->slug(), $content);
        }

        // Save to database (performed in controller)
        return true;
    }

    /**
     * Retrieve JS file path
     * Core operates with a single language, so language code is not appended to file name
     *
     * @param  string  $slug  Slug (not used on front page)
     * @param  string  $locale  Language code (unused in Core, kept for plugin extension)
     * @return string File path
     */
    public function getJsFilePath(string $slug, string $locale): string
    {
        return "{$this->basePath}/script.js";
    }

    /**
     * Retrieve CSS file path
     * Core operates with a single language, so language code is not appended to file name
     *
     * @param  string  $slug  Slug (not used on front page)
     * @param  string  $locale  Language code (unused in Core, kept for plugin extension)
     * @return string File path
     */
    public function getCssFilePath(string $slug, string $locale): string
    {
        return "{$this->basePath}/style.css";
    }

    /**
     * Retrieve JS content (DB or file)
     *
     * @param  FrontPage  $frontPage  Front page model
     * @param  string|null  $locale  Language code (current language if null)
     * @return string|null Content
     */
    public function getJsContent(FrontPage $frontPage, ?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();

        if ($frontPage->storage_type === ContentStorageType::FILE) {
            $filePath = $this->getJsFilePath($frontPage->page_type, $locale);

            if (\Illuminate\Support\Facades\Storage::disk($this->disk)->exists($filePath)) {
                return \Illuminate\Support\Facades\Storage::disk($this->disk)->get($filePath);
            }
        }

        return $frontPage->custom_js ?? null;
    }

    /**
     * Retrieve CSS content (DB or file)
     *
     * @param  FrontPage  $frontPage  Front page model
     * @param  string|null  $locale  Language code (current language if null)
     * @return string|null Content
     */
    public function getCssContent(FrontPage $frontPage, ?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();

        if ($frontPage->storage_type === ContentStorageType::FILE) {
            $filePath = $this->getCssFilePath($frontPage->page_type, $locale);

            if (\Illuminate\Support\Facades\Storage::disk($this->disk)->exists($filePath)) {
                return \Illuminate\Support\Facades\Storage::disk($this->disk)->get($filePath);
            }
        }

        return $frontPage->custom_css ?? null;
    }

    /**
     * Save JS content to file
     *
     * @param  string  $slug  Slug
     * @param  string  $locale  Language code
     * @param  string  $content  Content
     * @return bool True on successful save
     */
    public function saveJsToFile(string $slug, string $locale, string $content): bool
    {
        $filePath = $this->getJsFilePath($slug, $locale);

        return \Illuminate\Support\Facades\Storage::disk($this->disk)->put($filePath, $content);
    }

    /**
     * Save CSS content to file
     *
     * @param  string  $slug  Slug
     * @param  string  $locale  Language code
     * @param  string  $content  Content
     * @return bool True on successful save
     */
    public function saveCssToFile(string $slug, string $locale, string $content): bool
    {
        $filePath = $this->getCssFilePath($slug, $locale);

        return \Illuminate\Support\Facades\Storage::disk($this->disk)->put($filePath, $content);
    }

    /**
     * Delete JS file
     *
     * @param  string  $slug  Slug
     * @param  string  $locale  Language code
     * @return bool true on successful deletion
     */
    public function deleteJsFile(string $slug, string $locale): bool
    {
        $filePath = $this->getJsFilePath($slug, $locale);

        if (\Illuminate\Support\Facades\Storage::disk($this->disk)->exists($filePath)) {
            return \Illuminate\Support\Facades\Storage::disk($this->disk)->delete($filePath);
        }

        return true;
    }

    /**
     * Delete CSS file
     *
     * @param  string  $slug  Slug
     * @param  string  $locale  Language code
     * @return bool true on successful deletion
     */
    public function deleteCssFile(string $slug, string $locale): bool
    {
        $filePath = $this->getCssFilePath($slug, $locale);

        if (\Illuminate\Support\Facades\Storage::disk($this->disk)->exists($filePath)) {
            return \Illuminate\Support\Facades\Storage::disk($this->disk)->delete($filePath);
        }

        return true;
    }
}
