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

namespace App\Contracts\PluginIntegration;

/**
 * Minimal contract for linkable content
 *
 * Used as foundation for plugin integration
 * Common interface for referencing content across multiple plugins
 * such as menus, search, and tagging
 */
interface LinkableInterface
{
    /**
     * Get the unique ID (ULID/UUID) of the content
     */
    public function getId(): string;

    /**
     * Get the title of the content
     */
    public function getTitle(): string;

    /**
     * Get the URL of the content
     */
    public function getUrl(): string;

    /**
     * Get the type of the content
     *
     * e.g., 'post', 'page', 'media', 'product', 'inquiry'
     */
    public function getType(): string;

    /**
     * Get the source (provider) of the content
     *
     * - For Core: 'core'
     * - For plugin: plugin slug (e.g., 'dixlase-blog')
     */
    public function getSource(): string;

    /**
     * Get the source table name of the content (optional)
     *
     * Used for debugging and data integrity checks
     */
    public function getSourceTable(): ?string;
}
