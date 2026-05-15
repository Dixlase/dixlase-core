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

namespace App\Enums;

/**
 * Enum for content storage types
 *
 * Defines how editable content such as page content and front page designs
 * are stored
 */
enum ContentStorageType: int
{
    /**
     * Store in database
     * - Stored in DB content column, etc.
     * - Editable directly from admin panel
     * - Backup via DB
     */
    case DATABASE = 0;

    /**
     * Store as file
     * - Stored in storage/app/pages/{slug}.blade.php, etc.
     * - Editable directly in local editor
     * - Version controllable with Git
     */
    case FILE = 1;

    /**
     * Get legacy string identifier (slug)
     *
     * Used to maintain compatibility with JS/Alpine.js
     * Use this method when passing values to forms or JS
     */
    public function slug(): string
    {
        return match ($this) {
            self::DATABASE => 'database',
            self::FILE => 'file',
        };
    }

    /**
     * Get Enum instance from slug string
     *
     * @throws \ValueError When slug is not found
     */
    public static function fromSlug(string $slug): self
    {
        foreach (self::cases() as $case) {
            if ($case->slug() === $slug) {
                return $case;
            }
        }

        throw new \ValueError("\"$slug\" is not a valid slug for ".self::class);
    }

    /**
     * Get Enum instance from slug string (returns null on failure)
     */
    public static function tryFromSlug(string $slug): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->slug() === $slug) {
                return $case;
            }
        }

        return null;
    }

    /**
     * Get translation key
     */
    public function translationKey(): string
    {
        return match ($this) {
            self::DATABASE => 'common.content_storage.database',
            self::FILE => 'common.content_storage.file',
        };
    }

    /**
     * Get description translation key
     */
    public function descriptionKey(): string
    {
        return match ($this) {
            self::DATABASE => 'common.content_storage.database_description',
            self::FILE => 'common.content_storage.file_description',
        };
    }

    /**
     * Get all options
     */
    public static function options(): array
    {
        return [
            self::DATABASE->slug() => __('common.content_storage.database'),
            self::FILE->slug() => __('common.content_storage.file'),
        ];
    }

    /**
     * Get all options with descriptions
     */
    public static function optionsWithDescription(): array
    {
        return [
            self::DATABASE->slug() => [
                'label' => __('common.content_storage.database'),
                'description' => __('common.content_storage.database_description'),
            ],
            self::FILE->slug() => [
                'label' => __('common.content_storage.file'),
                'description' => __('common.content_storage.file_description'),
            ],
        ];
    }
}
