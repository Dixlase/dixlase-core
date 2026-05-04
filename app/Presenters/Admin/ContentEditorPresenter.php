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

namespace App\Presenters\Admin;

use App\DTO\Editor\EditorInfo;
use App\Enums\ContentEditorType;
use App\Enums\ContentStorageType;
use App\Services\Editor\EditorManager;

/**
 * Presenter for content editor component display data
 *
 * Prepares data to pass to the form-content-editor Blade component
 *
 * Consolidates logic from @php blocks here
 */
class ContentEditorPresenter
{
    /**
     * Build storage type radio card options
     *
     * @return array<int, array{value: string, label: string, description: string, icon: string}>
     */
    public static function storageOptions(): array
    {
        $options = [];
        foreach (ContentStorageType::optionsWithDescription() as $value => $option) {
            $options[] = [
                'value' => $value,
                'label' => $option['label'],
                'description' => $option['description'],
                'icon' => $value === 'database' ? 'fas fa-database' : 'fas fa-file-code',
            ];
        }

        return $options;
    }

    /**
     * Build editor type radio card options with plugin editor awareness
     *
     * @param  ContentStorageType|null  $storageType  Filter by storage type
     * @param  array<string>  $exclude  Editor type slugs to exclude
     * @return array<int, array{value: string, label: string, icon: string, description: string, disabled?: bool, badge?: string, badgeColor?: string}>
     */
    public static function editorOptions(?ContentStorageType $storageType = null, array $exclude = []): array
    {
        $editorManager = app(EditorManager::class);
        $enabledByPlugin = $editorManager->getAvailableEditorTypes();

        return ContentEditorType::radioCardOptions($storageType, $exclude, $enabledByPlugin);
    }

    /**
     * Get plugin editor info for the GUI editor type
     *
     * Returns the preferred GUI editor info, or null if none available.
     */
    public static function guiEditorInfo(): ?EditorInfo
    {
        $editorManager = app(EditorManager::class);

        return $editorManager->getPreferredEditor('gui');
    }

    /**
     * Get all available plugin editors indexed by type slug
     *
     * @return array<string, EditorInfo>
     */
    public static function pluginEditors(): array
    {
        $editorManager = app(EditorManager::class);
        $editors = $editorManager->getAvailableEditors();
        $indexed = [];

        foreach ($editors as $editor) {
            $indexed[$editor->typeSlug] = $editor;
        }

        return $indexed;
    }

    /**
     * Build the translations array for the editor component JS
     *
     * @return array<string, string>
     */
    public static function editorTranslations(): array
    {
        return [
            'common.content_editor.gui' => __('common.content_editor.gui'),
            'common.content_editor.gui_description' => __('common.content_editor.gui_description'),
            'common.content_editor.markdown' => __('common.content_editor.markdown'),
            'common.content_editor.markdown_description' => __('common.content_editor.markdown_description'),
            'common.content_editor.html' => __('common.content_editor.html'),
            'common.content_editor.html_description' => __('common.content_editor.html_description'),
            'common.content_editor.blade' => __('common.content_editor.blade'),
            'common.content_editor.blade_description' => __('common.content_editor.blade_description'),
            'common.content_editor.gui_unavailable' => __('common.content_editor.gui_unavailable'),
        ];
    }

    /**
     * Get editor icon mapping for JS
     *
     * @return array<string, string>
     */
    public static function editorIcons(): array
    {
        return [
            'gui' => ContentEditorType::GUI->iconClass(),
            'markdown' => ContentEditorType::MARKDOWN->iconClass(),
            'html' => ContentEditorType::HTML->iconClass(),
            'blade' => ContentEditorType::BLADE->iconClass(),
        ];
    }

    /**
     * Get the asset HTML for a plugin editor
     */
    public static function editorAssetHtml(EditorInfo $editorInfo): string
    {
        $allFiles = array_merge(
            $editorInfo->assets['css'] ?? [],
            $editorInfo->assets['js'] ?? [],
        );

        if (empty($allFiles)) {
            return '';
        }

        return load_plugin_assets($editorInfo->pluginDirectory, $allFiles);
    }
}
