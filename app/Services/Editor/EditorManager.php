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

namespace App\Services\Editor;

use App\Contracts\Plugin\EditorCapableInterface;
use App\DTO\Editor\EditorInfo;
use App\Models\SiteSetting;
use App\Services\Plugin\PluginServiceResolver;

/**
 * Service that manages detection, selection, and rendering of editor plugins
 *
 * Wraps PluginServiceResolver and provides editor-specific operations
 */
class EditorManager
{
    /**
     * Setting key for preferred GUI editor
     */
    public const PREFERRED_GUI_EDITOR_KEY = 'preferred_gui_editor';

    public function __construct(
        protected PluginServiceResolver $resolver,
    ) {}

    /**
     * Get all available editor providers
     *
     * @return array<EditorInfo>
     */
    public function getAvailableEditors(): array
    {
        $results = $this->resolver->resolveAll(EditorCapableInterface::class);
        $editors = [];

        foreach ($results as $result) {
            if ($result->isResolved() && $result->instance instanceof EditorCapableInterface) {
                $editors[] = EditorInfo::fromCapability($result->instance);
            }
        }

        return $editors;
    }

    /**
     * Get available editors for a specific editor type
     *
     * @return array<EditorInfo>
     */
    public function getEditorsForType(string $typeSlug): array
    {
        return array_values(array_filter(
            $this->getAvailableEditors(),
            fn (EditorInfo $info) => $info->typeSlug === $typeSlug,
        ));
    }

    /**
     * Get the preferred editor for a given type
     *
     * Reads the admin setting to determine which plugin to use.
     * Falls back to the first available if no preference is set.
     */
    public function getPreferredEditor(string $typeSlug): ?EditorInfo
    {
        $editors = $this->getEditorsForType($typeSlug);

        if (empty($editors)) {
            return null;
        }

        if (count($editors) === 1) {
            return $editors[0];
        }

        $preferredSlug = SiteSetting::get(self::PREFERRED_GUI_EDITOR_KEY);
        if ($preferredSlug) {
            foreach ($editors as $editor) {
                if ($editor->pluginSlug === $preferredSlug) {
                    return $editor;
                }
            }
        }

        return $editors[0];
    }

    /**
     * Get the EditorCapableInterface instance for a specific editor type
     */
    public function getEditorCapability(string $typeSlug, ?string $pluginSlug = null): ?EditorCapableInterface
    {
        $results = $this->resolver->resolveAll(EditorCapableInterface::class);

        foreach ($results as $result) {
            if (! $result->isResolved() || ! $result->instance instanceof EditorCapableInterface) {
                continue;
            }

            if ($result->instance->getEditorTypeSlug() !== $typeSlug) {
                continue;
            }

            if ($pluginSlug !== null && $result->instance->getPluginSlug() !== $pluginSlug) {
                continue;
            }

            return $result->instance;
        }

        // If no specific plugin matched, try the preferred one
        if ($pluginSlug === null) {
            $preferred = $this->getPreferredEditor($typeSlug);
            if ($preferred) {
                return $this->getEditorCapability($typeSlug, $preferred->pluginSlug);
            }
        }

        return null;
    }

    /**
     * Check if an editor is available for the given type
     */
    public function hasEditor(string $typeSlug): bool
    {
        return ! empty($this->getEditorsForType($typeSlug));
    }

    /**
     * Render stored content as HTML using the appropriate editor plugin
     */
    public function renderContent(string $editorTypeSlug, string $content): string
    {
        $capability = $this->getEditorCapability($editorTypeSlug);

        if ($capability === null) {
            return '';
        }

        return $capability->renderContent($content);
    }

    /**
     * Get editor types that have available plugin implementations
     *
     * @return array<string> Editor type slugs with available plugins
     */
    public function getAvailableEditorTypes(): array
    {
        $editors = $this->getAvailableEditors();

        return array_values(array_unique(
            array_map(fn (EditorInfo $info) => $info->typeSlug, $editors)
        ));
    }
}
