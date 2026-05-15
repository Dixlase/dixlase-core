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

use App\DTO\PluginIntegration\SeoMetaDTO;

/**
 * Interface to provide read/write access to SEO meta information per content
 *
 * SEO plugins provide the implementation, which is used by page generation plugins (static pages,
 * legal pages, blog posts, etc.). When the SEO plugin
 * is not installed, no implementation of this contract is registered in the container, so
 * callers should check with `app()->has(SeoMetaProviderInterface::class)`
 * before resolving, or use the optional resolution pattern.
 *
 * Meta information is uniquely identified by the `(plugin_slug, entity_id)` pair.
 * `plugin_slug` is the slug of the calling plugin (e.g. `dixlase-pages`),
 * `entity_id` is the identifier of the target entity within each plugin
 * (typically the content model ID) passed as a string.
 */
interface SeoMetaProviderInterface
{
    /**
     * Get meta information for the specified entity of the specified plugin
     *
     * @param  string  $pluginSlug  Slug of the calling plugin
     * @param  string  $entityId  ID of the target entity
     * @return SeoMetaDTO|null null if not registered
     */
    public function getMeta(string $pluginSlug, string $entityId): ?SeoMetaDTO;

    /**
     * Save (upsert) meta information
     *
     * @param  string  $pluginSlug  Slug of the calling plugin
     * @param  string  $entityId  ID of the target entity
     * @param  SeoMetaDTO  $meta  Meta information to save
     */
    public function saveMeta(string $pluginSlug, string $entityId, SeoMetaDTO $meta): void;

    /**
     * Delete a single meta information entry
     *
     * @param  string  $pluginSlug  Slug of the calling plugin
     * @param  string  $entityId  ID of the target entity
     */
    public function deleteMeta(string $pluginSlug, string $entityId): void;

    /**
     * Bulk delete all meta information for the specified plugin
     *
     * Used for cascade cleanup when uninstalling a plugin.
     *
     * @param  string  $pluginSlug  Slug of the target plugin
     * @return int Number of deleted items
     */
    public function purgeByPlugin(string $pluginSlug): int;

    /**
     * Check if the SEO meta feature for the specified plugin is
     * enabled in the SEO plugin settings
     *
     * Used to determine whether to display SEO fields on the edit screen
     * of each page generation plugin
     *
     * @param  string  $pluginSlug  Slug of the target plugin
     */
    public function isEnabledForPlugin(string $pluginSlug): bool;
}
