<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

namespace App\Contracts\Plugin;

use App\Enums\ContentStorageType;

/**
 * エディター提供機能を宣言するインターフェース
 *
 * GUIエディターなどのコンテンツエディターを提供するプラグインが実装します。
 * PluginServiceResolver 経由で発見・解決されます。
 *
 * エディタープラグインは特別な権限を必要としません（UIを提供するのみ）。
 */
interface EditorCapableInterface extends PluginCapabilityInterface
{
    /**
     * Get the editor type slug this plugin provides
     *
     * Must match a ContentEditorType slug (e.g. 'gui').
     */
    public function getEditorTypeSlug(): string;

    /**
     * Get the editor display label
     *
     * @return array{en: string, ja: string}
     */
    public function getEditorLabel(): array;

    /**
     * Get the editor description
     *
     * @return array{en: string, ja: string}
     */
    public function getEditorDescription(): array;

    /**
     * Get the Font Awesome icon class for this editor
     */
    public function getEditorIcon(): string;

    /**
     * Get supported storage types
     *
     * @return array<ContentStorageType>
     */
    public function getSupportedStorageTypes(): array;

    /**
     * Get the Blade view name for the editor panel
     *
     * The view will receive editor state via Alpine.js events.
     */
    public function getEditorViewName(): string;

    /**
     * Get the plugin directory name for asset loading
     *
     * Used with load_plugin_assets() to inject editor JS/CSS.
     */
    public function getPluginDirectoryName(): string;

    /**
     * Get asset files to load for the editor
     *
     * @return array{js: array<string>, css: array<string>}
     */
    public function getEditorAssets(): array;

    /**
     * Get the content storage format identifier
     *
     * @return string e.g. 'json' for block editors
     */
    public function getContentFormat(): string;

    /**
     * Render stored content as HTML for front-end display
     */
    public function renderContent(string $storedContent): string;

    /**
     * Validate content before storing
     */
    public function validateContent(string $content): bool;

    /**
     * Convert Dixlase JSON to editor-native format
     *
     * Dixlase JSON is the canonical storage format.
     * Editors convert to their native format for editing.
     */
    public function toEditorFormat(string $dixlaseJson): string;

    /**
     * Convert editor-native format to Dixlase JSON
     *
     * Editors convert their native output back to Dixlase JSON for storage.
     */
    public function fromEditorFormat(string $editorData): string;
}
