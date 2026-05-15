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

namespace App\Support\Cache;

/**
 * @api Stable cache-key Builder for plugins and themes.
 *
 * Builds Dixlase cache keys following the convention
 * `dixlase:{scope}:{owner}:{domain}:{key}` (or the site-scoped composite
 * `dixlase:site:{site_id}:{scope}:{owner}:{domain}:{key}`).
 *
 * The Builder is a pure string generator: it never reads `plugin.json` /
 * `theme.json`, never touches the cache store, and never enforces tag use.
 * Callers pass slugs and site ids explicitly.
 *
 * @see docs/development/cache-key-convention.md
 */
final class CacheKey
{
    /**
     * Project-wide cache-key prefix.
     */
    public const PREFIX = 'dixlase';

    /**
     * Build a key under the core scope.
     *
     * Format: `dixlase:core:{domain}:{key}`.
     */
    public static function core(string $domain, string $key): string
    {
        return self::PREFIX.':core:'.$domain.':'.$key;
    }

    /**
     * Build a key under a plugin scope.
     *
     * Format: `dixlase:plugin:{plugin_slug}:{domain}:{key}`.
     */
    public static function plugin(string $pluginSlug, string $domain, string $key): string
    {
        return self::PREFIX.':plugin:'.$pluginSlug.':'.$domain.':'.$key;
    }

    /**
     * Build a key under a theme scope.
     *
     * Format: `dixlase:theme:{theme_slug}:{domain}:{key}`.
     */
    public static function theme(string $themeSlug, string $domain, string $key): string
    {
        return self::PREFIX.':theme:'.$themeSlug.':'.$domain.':'.$key;
    }

    /**
     * Enter the site-scoped Builder.
     *
     * Use the returned Builder to compose keys / tags for a specific site:
     *
     *     CacheKey::site(1)->key('nav', 'public');
     *     CacheKey::site(1)->plugin('dixlase-pages', 'manifest', 'v1');
     *     CacheKey::site(1)->theme('dixlase-onepage', 'view', 'home');
     *     CacheKey::site(1)->core('routes', 'list');
     *     CacheKey::site(1)->tag('plugin', 'dixlase-pages');
     */
    public static function site(int|string $siteId): SiteScopedCacheKey
    {
        return new SiteScopedCacheKey($siteId);
    }

    /**
     * Build a flush tag for the given scope and owner.
     *
     * Format: `dixlase:tag:{scope}:{owner}`. Tags are only honoured by drivers
     * implementing `Illuminate\Cache\TaggableStore` (Redis, Memcached, array).
     */
    public static function tag(string $scope, string $owner): string
    {
        return self::PREFIX.':tag:'.$scope.':'.$owner;
    }
}
