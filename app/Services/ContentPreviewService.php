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

use App\Enums\ContentEditorType;
use App\Services\Editor\EditorManager;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Rendering service for content preview
 *
 * Converts content to HTML according to editor type
 * and expands shortcodes. Used in the admin panel preview feature.
 */
class ContentPreviewService
{
    public function __construct(
        protected EditorManager $editorManager,
    ) {}

    /**
     * Render content to HTML for preview.
     *
     * Security: when $editorType is BLADE, this method invokes Blade::render
     * on $content, which executes arbitrary PHP. Callers MUST ensure that
     * the editor type came from a trusted, stored value (e.g. a saved page's
     * editor_type column) and not from request input. For request-driven
     * preview endpoints use {@see renderFromSlug()} instead, which downgrades
     * BLADE to HTML by default.
     *
     * @param  string  $content  Raw content
     * @param  ContentEditorType  $editorType  Editor type (must come from a trusted source if BLADE)
     */
    public function render(string $content, ContentEditorType $editorType): string
    {
        if ($content === '') {
            return '';
        }

        try {
            $html = match ($editorType) {
                ContentEditorType::GUI => $this->editorManager->renderContent('gui', $content),
                ContentEditorType::MARKDOWN => Str::markdown($this->normalizeMarkdown($content)),
                ContentEditorType::BLADE => Blade::render($content),
                ContentEditorType::HTML => $content,
            };

            return shortcode_parse($html);
        } catch (\Exception $e) {
            Log::warning('Failed to render content preview', [
                'editor_type' => $editorType->slug(),
                'error' => $e->getMessage(),
            ]);

            return $content;
        }
    }

    /**
     * Markdown lenient preprocessing
     *
     * Auto-correct when there's no space after heading markers (#)
     * Example: `#Heading` → `# Heading`, `##Heading` → `## Heading`
     */
    protected function normalizeMarkdown(string $content): string
    {
        return preg_replace('/^(#{1,6})([^\s#])/m', '$1 $2', $content);
    }

    /**
     * Render content to HTML for preview from an editor type slug.
     *
     * Security: callers of this method typically pass the editor type slug
     * directly from request input (`request()->input('editor_type')`). Blade
     * is a server-side templating language — rendering attacker-controlled
     * Blade source would be a remote code execution vector.
     *
     * To prevent that, BLADE is silently downgraded to HTML rendering unless
     * the caller explicitly opts in via $allowExecutableTemplates. Callers
     * that opt in MUST first verify that the current actor has the privilege
     * to author Blade templates (e.g. by matching against the stored
     * editor_type of a saved page that the actor has permission to edit).
     *
     * Use {@see render()} with a typed ContentEditorType enum when the editor
     * type comes from a trusted, stored value rather than a request slug.
     *
     * @param  string  $content  Raw content
     * @param  string  $editorTypeSlug  Editor type slug (e.g., 'html', 'markdown', 'blade')
     * @param  bool  $allowExecutableTemplates  Opt-in to BLADE rendering. Caller must enforce its own permission check first.
     */
    public function renderFromSlug(string $content, string $editorTypeSlug, bool $allowExecutableTemplates = false): string
    {
        $editorType = ContentEditorType::tryFromSlug($editorTypeSlug);

        if ($editorType === null) {
            return '';
        }

        if ($editorType === ContentEditorType::BLADE && ! $allowExecutableTemplates) {
            // Treat as HTML to avoid invoking Blade::render on attacker-controlled input.
            $editorType = ContentEditorType::HTML;
        }

        return $this->render($content, $editorType);
    }
}
