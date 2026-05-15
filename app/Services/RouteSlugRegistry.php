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

namespace App\Services;

use App\Contracts\RouteSlugProvider;
use App\DTO\RouteSlug\RegisteredSlug;
use App\Repositories\SiteSettingRepository;

/**
 * Route Slug Registry
 *
 * Registry that collects and manages system-wide top-level URL slugs.
 * Integrates plugin-registered providers, Core settings, and reserved paths,
 * and performs slug duplication checks.
 */
class RouteSlugRegistry
{
    /**
     * Registered slug providers
     *
     * @var array<string, RouteSlugProvider>
     */
    protected array $providers = [];

    /**
     * Memoized slug cache
     *
     * @var array<RegisteredSlug>|null
     */
    protected ?array $cachedSlugs = null;

    /**
     * Register a slug provider
     *
     * @param  string  $name  Provider name (plugin slug, etc.)
     * @param  RouteSlugProvider  $provider  Provider instance
     */
    public function registerProvider(string $name, RouteSlugProvider $provider): void
    {
        $this->providers[$name] = $provider;
        $this->clearCache();
    }

    /**
     * Unregister a slug provider
     */
    public function unregisterProvider(string $name): void
    {
        unset($this->providers[$name]);
        $this->clearCache();
    }

    /**
     * Get all registered slugs
     *
     * @return array<RegisteredSlug>
     */
    public function getAllSlugs(): array
    {
        if ($this->cachedSlugs !== null) {
            return $this->cachedSlugs;
        }

        $slugs = [];

        // 1. Add system reserved paths
        $reserved = config('admin.reserved-slugs.reserved', []);
        foreach ($reserved as $path) {
            $slugs[] = RegisteredSlug::reserved($path);
        }

        // 2. Add Core dynamic slugs (admin_url)
        $slugs = array_merge($slugs, $this->getCoreAdminSlug());

        // 3. Collect slugs from providers
        foreach ($this->providers as $provider) {
            $providerSlugs = $provider->getRouteSlugs();
            foreach ($providerSlugs as $slug) {
                $slugs[] = $slug;
            }
        }

        $this->cachedSlugs = $slugs;

        return $slugs;
    }

    /**
     * Search for slug conflicts
     *
     * @param  string  $slug  Slug to check
     * @param  string|null  $excludeOwner  Owner to exclude (exclude own slug)
     * @return RegisteredSlug|null Conflicting slug. Returns null if no conflict
     */
    public function findConflict(string $slug, ?string $excludeOwner = null): ?RegisteredSlug
    {
        $normalizedSlug = mb_strtolower(trim($slug));

        foreach ($this->getAllSlugs() as $registered) {
            if (mb_strtolower($registered->slug) === $normalizedSlug) {
                if ($excludeOwner !== null && $registered->owner === $excludeOwner) {
                    continue;
                }

                return $registered;
            }
        }

        return null;
    }

    /**
     * Determine if a slug is available
     *
     * @param  string  $slug  Slug to check
     * @param  string|null  $excludeOwner  Owner to exclude
     */
    public function isAvailable(string $slug, ?string $excludeOwner = null): bool
    {
        return $this->findConflict($slug, $excludeOwner) === null;
    }

    /**
     * Get list of registered provider names
     *
     * @return array<string>
     */
    public function getProviderNames(): array
    {
        return array_keys($this->providers);
    }

    /**
     * Clear memoization cache
     */
    public function clearCache(): void
    {
        $this->cachedSlugs = null;
    }

    /**
     * Get Core admin panel URL slug
     *
     * @return array<RegisteredSlug>
     */
    protected function getCoreAdminSlug(): array
    {
        try {
            $repo = app(SiteSettingRepository::class);
            $adminUrl = $repo->get('admin_url', config('admin.url.admin_url', 'admin'));
        } catch (\Exception $e) {
            $adminUrl = config('admin.url.admin_url', 'admin');
        }

        return [
            new RegisteredSlug(
                slug: $adminUrl,
                owner: 'core:admin_url',
                label: 'validation/route-slug.owners.core_admin_url',
            ),
        ];
    }
}
