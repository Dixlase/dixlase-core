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

namespace App\Support\Cache;

/**
 * @api Site-scoped fluent Builder returned by `CacheKey::site($id)`.
 *
 * Produces composite cache keys whose scope is partitioned by site, suitable
 * for any cache entry that varies between sites in a multisite install.
 *
 * @see docs/development/cache-key-convention.md
 * @see CacheKey
 */
final class SiteScopedCacheKey
{
    public function __construct(
        private readonly int|string $siteId,
    ) {}

    /**
     * Build a site-only key with no nested scope.
     *
     * Format: `dixlase:site:{site_id}:{domain}:{key}`.
     */
    public function key(string $domain, string $key): string
    {
        return CacheKey::PREFIX.':site:'.$this->siteId.':'.$domain.':'.$key;
    }

    /**
     * Build a site-scoped core key.
     *
     * Format: `dixlase:site:{site_id}:core:{domain}:{key}`.
     */
    public function core(string $domain, string $key): string
    {
        return CacheKey::PREFIX.':site:'.$this->siteId.':core:'.$domain.':'.$key;
    }

    /**
     * Build a site-scoped plugin key.
     *
     * Format: `dixlase:site:{site_id}:plugin:{plugin_slug}:{domain}:{key}`.
     */
    public function plugin(string $pluginSlug, string $domain, string $key): string
    {
        return CacheKey::PREFIX.':site:'.$this->siteId.':plugin:'.$pluginSlug.':'.$domain.':'.$key;
    }

    /**
     * Build a site-scoped theme key.
     *
     * Format: `dixlase:site:{site_id}:theme:{theme_slug}:{domain}:{key}`.
     */
    public function theme(string $themeSlug, string $domain, string $key): string
    {
        return CacheKey::PREFIX.':site:'.$this->siteId.':theme:'.$themeSlug.':'.$domain.':'.$key;
    }

    /**
     * Build a site-scoped flush tag.
     *
     * Format: `dixlase:tag:site:{site_id}:{scope}:{owner}`.
     */
    public function tag(string $scope, string $owner): string
    {
        return CacheKey::PREFIX.':tag:site:'.$this->siteId.':'.$scope.':'.$owner;
    }
}
