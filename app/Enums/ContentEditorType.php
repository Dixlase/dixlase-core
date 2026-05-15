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
 * Content editor type enum
 *
 * Defines how editable content such as page content and front page design
 * can be edited
 */
enum ContentEditorType: int
{
    /**
     * GUI Editor (future implementation)
     * - Block editor or WYSIWYG editor
     * - Build layouts with drag & drop
     * - No technical knowledge required
     */
    case GUI = 1;

    /**
     * Markdown format
     * - Written in Markdown syntax
     * - With preview feature
     * - Simple with low learning cost
     */
    case MARKDOWN = 2;

    /**
     * Direct HTML editing
     * - Write HTML tags directly
     * - Full control available
     * - Technical knowledge required
     */
    case HTML = 3;

    /**
     * Blade template (FILE storage only)
     * - Written in Laravel Blade syntax
     * - Dynamic content support
     * - Most flexible but technical knowledge required
     */
    case BLADE = 4;

    /**
     * Get legacy string identifier (slug)
     *
     * Used to maintain compatibility with JS/Alpine.js
     * Use this method when passing to form values or JS
     */
    public function slug(): string
    {
        return match ($this) {
            self::GUI => 'gui',
            self::MARKDOWN => 'markdown',
            self::HTML => 'html',
            self::BLADE => 'blade',
        };
    }

    /**
     * Get Enum instance from slug string
     *
     * Convert strings sent from form submissions or JS to Enum
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
     * Get Font Awesome icon class
     */
    public function iconClass(): string
    {
        return match ($this) {
            self::GUI => 'fas fa-paint-brush',
            self::HTML => 'fas fa-code',
            self::MARKDOWN => 'fab fa-markdown',
            self::BLADE => 'fas fa-file-code',
        };
    }

    /**
     * Get icon color
     */
    public function iconColor(): string
    {
        return match ($this) {
            self::GUI => '#9333ea',
            self::MARKDOWN => '#2563eb',
            self::HTML => '#ea580c',
            self::BLADE => '#16a34a',
        };
    }

    /**
     * Get translation key
     */
    public function translationKey(): string
    {
        return match ($this) {
            self::GUI => 'common.content_editor.gui',
            self::MARKDOWN => 'common.content_editor.markdown',
            self::HTML => 'common.content_editor.html',
            self::BLADE => 'common.content_editor.blade',
        };
    }

    /**
     * Get description translation key
     */
    public function descriptionKey(): string
    {
        return match ($this) {
            self::GUI => 'common.content_editor.gui_description',
            self::MARKDOWN => 'common.content_editor.markdown_description',
            self::HTML => 'common.content_editor.html_description',
            self::BLADE => 'common.content_editor.blade_description',
        };
    }

    /**
     * Get available editor types for the specified storage method
     *
     * DATABASE: GUI, Markdown, HTML
     * FILE: Blade, Markdown, HTML
     */
    public static function availableFor(ContentStorageType $storageType): array
    {
        return match ($storageType) {
            ContentStorageType::DATABASE => [
                self::GUI,      // GUI is DATABASE only (saved in JSON format)
                self::HTML,     // HTML is DATABASE or FILE
                self::MARKDOWN, // Markdown is DATABASE or FILE
            ],
            ContentStorageType::FILE => [
                self::BLADE,    // Blade is FILE only (requires .blade.php file)
                self::HTML,     // HTML is DATABASE or FILE
                self::MARKDOWN, // Markdown is DATABASE or FILE
            ],
        };
    }

    /**
     * Get available storage methods for the specified editor type
     */
    public static function availableStorageTypes(self $editorType): array
    {
        return match ($editorType) {
            self::GUI => [ContentStorageType::DATABASE],           // GUI is DATABASE only
            self::BLADE => [ContentStorageType::FILE],             // Blade is FILE only
            self::MARKDOWN, self::HTML => [                        // Markdown/HTML supports both
                ContentStorageType::DATABASE,
                ContentStorageType::FILE,
            ],
        };
    }

    /**
     * Get all choices
     */
    public static function options(): array
    {
        return [
            self::GUI->slug() => __('common.content_editor.gui'),
            self::MARKDOWN->slug() => __('common.content_editor.markdown'),
            self::HTML->slug() => __('common.content_editor.html'),
            self::BLADE->slug() => __('common.content_editor.blade'),
        ];
    }

    /**
     * Get available choices for the specified storage method
     */
    public static function optionsFor(ContentStorageType $storageType): array
    {
        $available = self::availableFor($storageType);
        $options = [];

        foreach ($available as $type) {
            $options[$type->slug()] = __($type->translationKey());
        }

        return $options;
    }

    /**
     * Get available options with descriptions for the specified save method
     */
    public static function optionsWithDescriptionFor(ContentStorageType $storageType): array
    {
        $available = self::availableFor($storageType);
        $options = [];

        foreach ($available as $type) {
            $options[$type->slug()] = [
                'label' => __($type->translationKey()),
                'description' => __($type->descriptionKey()),
            ];
        }

        return $options;
    }

    /**
     * Get option array for radio card group
     *
     * Returns in a format that can be passed directly to the <x-form-radio-card-group> component
     * GUI editor is disabled with Coming Soon badge if not provided by plugin
     *
     * @param  ContentStorageType|null  $storageType  Filter by save method (all if null)
     * @param  array<string>  $exclude  Editor type values to exclude
     * @param  array<string>  $enabledByPlugin  Editor type slug made available by plugin
     * @return array<int, array{value: string, label: string, icon: string, description: string, disabled?: bool, badge?: string, badgeColor?: string}>
     */
    public static function radioCardOptions(?ContentStorageType $storageType = null, array $exclude = [], array $enabledByPlugin = []): array
    {
        $types = $storageType ? self::availableFor($storageType) : self::cases();
        $options = [];

        foreach ($types as $type) {
            if (in_array($type->slug(), $exclude, true)) {
                continue;
            }

            $option = [
                'value' => $type->slug(),
                'label' => __($type->translationKey()),
                'icon' => $type->iconClass(),
                'description' => __($type->descriptionKey()),
            ];

            // GUI is always selectable (shows placeholder in content area when plugin not provided)

            $options[] = $option;
        }

        return $options;
    }

    /**
     * Get file extension
     */
    public function fileExtension(): string
    {
        return match ($this) {
            self::GUI => 'json',
            self::MARKDOWN => 'md',
            self::HTML => 'html',
            self::BLADE => 'blade.php',
        };
    }
}
