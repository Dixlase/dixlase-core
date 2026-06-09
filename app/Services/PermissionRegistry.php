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

declare(strict_types=1);

namespace App\Services;

use App\Enums\MemberRole;
use App\Models\RolePermissionOverride;
use App\Support\Cache\CacheKey;
use Illuminate\Support\Facades\Cache;

/**
 * Permission Registry Service
 *
 * Merges default permissions (plugin roles.php — see resolvePluginRolesPath())
 * and overrides (DB)
 * Service that provides effective permissions
 */
class PermissionRegistry
{
    /**
     * Cache TTL (seconds)
     */
    protected const CACHE_TTL = 300;

    /**
     * Cache key domain under the core / plugin scope.
     */
    protected const CACHE_DOMAIN = 'permissions';

    /**
     * Registered plugin permission definitions
     *
     * @var array<string, array>
     */
    protected static array $pluginPermissions = [];

    /**
     * Register plugin permission definition
     * Called in ServiceProvider's register()
     */
    public static function registerPlugin(string $pluginSlug, array $permissions): void
    {
        self::$pluginPermissions[$pluginSlug] = $permissions;
        self::clearCache();
    }

    /**
     * Unregister plugin permission definition
     */
    public static function unregisterPlugin(string $pluginSlug): void
    {
        unset(self::$pluginPermissions[$pluginSlug]);
        self::clearCache();
    }

    /**
     * Get effective permission (Core feature)
     *
     * @param  string  $menuKey  Menu key (e.g., settings.base.index)
     * @return array{access_roles: int, view_roles: int}|null
     */
    public static function getEffective(string $menuKey): ?array
    {
        $cacheKey = CacheKey::core(self::CACHE_DOMAIN, $menuKey);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($menuKey) {
            // Get default value (from nested structure)
            $default = self::getDefaultFromNestedConfig($menuKey);

            if ($default === null) {
                return;
            }

            // Get override
            $override = RolePermissionOverride::getCoreOverride($menuKey);

            if ($override) {
                return [
                    'access_roles' => $override->access_roles ?? $default['access_roles'],
                    'view_roles' => $override->view_roles ?? $default['view_roles'],
                    'is_overridden' => true,
                    'default_access_roles' => $default['access_roles'],
                    'default_view_roles' => $default['view_roles'],
                ];
            }

            return [
                'access_roles' => $default['access_roles'],
                'view_roles' => $default['view_roles'],
                'is_overridden' => false,
                'default_access_roles' => $default['access_roles'],
                'default_view_roles' => $default['view_roles'],
            ];
        });
    }

    /**
     * Get permission from nested config using dot notation key
     *
     * @param  string  $menuKey  Menu key in dot notation (e.g., settings.base.index)
     * @return array{access_roles: int, view_roles: int}|null
     */
    protected static function getDefaultFromNestedConfig(string $menuKey): ?array
    {
        $permissions = config('roles.permissions', []);
        $parts = explode('.', $menuKey);

        $current = $permissions;
        foreach ($parts as $part) {
            if (! is_array($current)) {
                return null;
            }

            // If direct key exists
            if (isset($current[$part])) {
                // If access_roles exists, it's a permission definition
                if (isset($current[$part]['access_roles'])) {
                    return $current[$part];
                }
                // If children exists, go deeper
                if (isset($current[$part]['children'])) {
                    $current = $current[$part]['children'];

                    continue;
                }
                // Otherwise, go to next level
                $current = $current[$part];

                continue;
            }

            return null;
        }

        // Finally, if access_roles exists, it's a permission definition
        if (is_array($current) && isset($current['access_roles'])) {
            return $current;
        }

        return null;
    }

    /**
     * Get effective permission (plugin feature)
     *
     * @param  string  $pluginSlug  Plugin slug
     * @param  string  $menuKey  Menu key
     * @return array{access_roles: int, view_roles: int}|null
     */
    public static function getPluginEffective(string $pluginSlug, string $menuKey): ?array
    {
        $cacheKey = CacheKey::plugin($pluginSlug, self::CACHE_DOMAIN, $menuKey);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($pluginSlug, $menuKey) {
            // Get plugin default value
            $default = self::getPluginDefault($pluginSlug, $menuKey);

            if ($default === null) {
                // Use ADMIN permission as default when no default exists
                $default = [
                    'access_roles' => MemberRole::ADMIN->value,
                    'view_roles' => MemberRole::ADMIN->value,
                ];
            }

            // Get override
            $override = RolePermissionOverride::getPluginOverride($pluginSlug, $menuKey);

            if ($override) {
                return [
                    'access_roles' => $override->access_roles ?? $default['access_roles'],
                    'view_roles' => $override->view_roles ?? $default['view_roles'],
                    'is_overridden' => true,
                    'default_access_roles' => $default['access_roles'],
                    'default_view_roles' => $default['view_roles'],
                ];
            }

            return [
                'access_roles' => $default['access_roles'],
                'view_roles' => $default['view_roles'],
                'is_overridden' => false,
                'default_access_roles' => $default['access_roles'],
                'default_view_roles' => $default['view_roles'],
            ];
        });
    }

    /**
     * Resolve a plugin's roles.php path.
     *
     * The canonical location matches `plugins/CLAUDE.md`, `PLUGIN-API.md`,
     * and the `declares.configs.roles` declaration verified by
     * `DeclaresVerifier` / `PluginManifestSyncService`. Returns null when
     * the file does not exist.
     */
    public static function resolvePluginRolesPath(string $pluginSlug): ?string
    {
        $path = base_path("plugins/{$pluginSlug}/config/admin/roles.php");

        return file_exists($path) ? $path : null;
    }

    /**
     * Get plugin default permission (supports nested structure)
     */
    protected static function getPluginDefault(string $pluginSlug, string $menuKey): ?array
    {
        if (isset(self::$pluginPermissions[$pluginSlug][$menuKey])) {
            return self::$pluginPermissions[$pluginSlug][$menuKey];
        }

        $pluginRolesPath = self::resolvePluginRolesPath($pluginSlug);
        if ($pluginRolesPath !== null) {
            $pluginRoles = require $pluginRolesPath;
            $permissions = $pluginRoles['permissions'] ?? [];

            return self::getDefaultFromNestedArray($permissions, $menuKey);
        }

        return null;
    }

    /**
     * Get permission from nested array using dot notation key
     */
    protected static function getDefaultFromNestedArray(array $permissions, string $menuKey): ?array
    {
        $parts = explode('.', $menuKey);

        $current = $permissions;
        foreach ($parts as $part) {
            if (! is_array($current)) {
                return null;
            }

            // If direct key exists
            if (isset($current[$part])) {
                // If access_roles exists, it's a permission definition
                if (isset($current[$part]['access_roles'])) {
                    return $current[$part];
                }
                // If children exists, go deeper
                if (isset($current[$part]['children'])) {
                    $current = $current[$part]['children'];

                    continue;
                }
                // Otherwise, go to next level
                $current = $current[$part];

                continue;
            }

            return null;
        }

        // Finally, if access_roles exists, it's a permission definition
        if (is_array($current) && isset($current['access_roles'])) {
            return $current;
        }

        return null;
    }

    /**
     * Get all Core permission definitions (default + override merged)
     * Return preserving nested structure
     */
    public static function getAllCorePermissions(): array
    {
        $cacheKey = CacheKey::core(self::CACHE_DOMAIN, 'all');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            $defaults = config('roles.permissions', []);
            $overrides = RolePermissionOverride::getAllCoreOverrides()->keyBy('menu_key');

            return self::mergePermissionsWithOverrides($defaults, $overrides);
        });
    }

    /**
     * Get all Core permission definitions in flat format (default + override merged)
     * Keys are in dot notation (e.g., settings.base.index)
     */
    public static function getAllCorePermissionsFlat(): array
    {
        $cacheKey = CacheKey::core(self::CACHE_DOMAIN, 'all-flat');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            $defaults = config('roles.permissions', []);
            $overrides = RolePermissionOverride::getAllCoreOverrides()->keyBy('menu_key');

            $flat = [];
            self::flattenPermissions($defaults, '', $flat);

            $result = [];
            foreach ($flat as $menuKey => $default) {
                $override = $overrides->get($menuKey);

                $result[$menuKey] = [
                    'access_roles' => $override?->access_roles ?? $default['access_roles'],
                    'view_roles' => $override?->view_roles ?? $default['view_roles'],
                    'is_overridden' => $override !== null,
                    'default_access_roles' => $default['access_roles'],
                    'default_view_roles' => $default['view_roles'],
                ];
            }

            return $result;
        });
    }

    /**
     * Flatten nested permission definitions
     */
    protected static function flattenPermissions(array $permissions, string $prefix, array &$result): void
    {
        foreach ($permissions as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;

            if (isset($value['access_roles'])) {
                // Permission definition
                $result[$fullKey] = $value;
            }

            if (isset($value['children'])) {
                // Recursively process child elements
                self::flattenPermissions($value['children'], $fullKey, $result);
            }
        }
    }

    /**
     * Merge overrides into nested permission definitions
     */
    protected static function mergePermissionsWithOverrides(array $permissions, $overrides, string $prefix = ''): array
    {
        $result = [];

        foreach ($permissions as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;

            if (isset($value['access_roles'])) {
                // Permission definition
                $override = $overrides->get($fullKey);
                $result[$key] = [
                    'access_roles' => $override?->access_roles ?? $value['access_roles'],
                    'view_roles' => $override?->view_roles ?? $value['view_roles'],
                    'is_overridden' => $override !== null,
                    'default_access_roles' => $value['access_roles'],
                    'default_view_roles' => $value['view_roles'],
                ];

                // Recursively process if children exist
                if (isset($value['children'])) {
                    $result[$key]['children'] = self::mergePermissionsWithOverrides(
                        $value['children'],
                        $overrides,
                        $fullKey
                    );
                }
            } elseif (isset($value['children'])) {
                // Only children without permission definition
                $result[$key] = [
                    'children' => self::mergePermissionsWithOverrides(
                        $value['children'],
                        $overrides,
                        $fullKey
                    ),
                ];
            }
        }

        return $result;
    }

    /**
     * Get all plugin permission definitions (default + override merged)
     * Return preserving nested structure
     */
    public static function getAllPluginPermissions(string $pluginSlug): array
    {
        $cacheKey = CacheKey::plugin($pluginSlug, self::CACHE_DOMAIN, 'all');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($pluginSlug) {
            // Get default permissions for plugin
            $defaults = self::$pluginPermissions[$pluginSlug] ?? [];

            $pluginRolesPath = self::resolvePluginRolesPath($pluginSlug);
            if ($pluginRolesPath !== null) {
                $pluginRoles = require $pluginRolesPath;
                $defaults = array_merge_recursive($defaults, $pluginRoles['permissions'] ?? []);
            }

            $overrides = RolePermissionOverride::getAllPluginOverrides($pluginSlug)->keyBy('menu_key');

            return self::mergePermissionsWithOverrides($defaults, $overrides);
        });
    }

    /**
     * Get all plugin permission definitions in flat format (default + override merged)
     * Keys are in dot notation (e.g., pages.index)
     */
    public static function getAllPluginPermissionsFlat(string $pluginSlug): array
    {
        $cacheKey = CacheKey::plugin($pluginSlug, self::CACHE_DOMAIN, 'all-flat');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($pluginSlug) {
            // Get default permissions for plugin
            $defaults = self::$pluginPermissions[$pluginSlug] ?? [];

            $pluginRolesPath = self::resolvePluginRolesPath($pluginSlug);
            if ($pluginRolesPath !== null) {
                $pluginRoles = require $pluginRolesPath;
                $defaults = array_merge_recursive($defaults, $pluginRoles['permissions'] ?? []);
            }

            $overrides = RolePermissionOverride::getAllPluginOverrides($pluginSlug)->keyBy('menu_key');

            $flat = [];
            self::flattenPermissions($defaults, '', $flat);

            $result = [];
            foreach ($flat as $menuKey => $default) {
                $override = $overrides->get($menuKey);

                $result[$menuKey] = [
                    'access_roles' => $override?->access_roles ?? $default['access_roles'],
                    'view_roles' => $override?->view_roles ?? $default['view_roles'],
                    'is_overridden' => $override !== null,
                    'default_access_roles' => $default['access_roles'],
                    'default_view_roles' => $default['view_roles'],
                ];
            }

            return $result;
        });
    }

    /**
     * Get permission definitions for all plugins
     */
    public static function getAllPluginsPermissions(): array
    {
        $result = [];

        // Register in memory
        foreach (array_keys(self::$pluginPermissions) as $pluginSlug) {
            $result[$pluginSlug] = self::getAllPluginPermissions($pluginSlug);
        }

        $pluginsPath = base_path('plugins');
        if (is_dir($pluginsPath)) {
            foreach (glob($pluginsPath.'/*', GLOB_ONLYDIR) ?: [] as $pluginDir) {
                $pluginSlug = basename($pluginDir);
                if (isset($result[$pluginSlug])) {
                    continue;
                }
                if (self::resolvePluginRolesPath($pluginSlug) !== null) {
                    $result[$pluginSlug] = self::getAllPluginPermissions($pluginSlug);
                }
            }
        }

        return $result;
    }

    /**
     * Check if user can access menu (Core feature)
     */
    public static function canAccess(string $menuKey, MemberRole $userRole): bool
    {
        if ($userRole === MemberRole::SUPER_ADMIN) {
            return true;
        }

        $effective = self::getEffective($menuKey);

        if ($effective === null) {
            // If no permission definition exists, check child item permissions
            $allPermissions = self::getAllCorePermissionsFlat();

            foreach ($allPermissions as $key => $permission) {
                if (str_starts_with($key, $menuKey.'.')) {
                    if ($userRole->value >= $permission['access_roles']) {
                        return true;
                    }
                }
            }

            return false;
        }

        return $userRole->value >= $effective['access_roles'];
    }

    /**
     * Check if user can view menu (Core feature)
     */
    public static function canView(string $menuKey, MemberRole $userRole): bool
    {
        if ($userRole === MemberRole::SUPER_ADMIN) {
            return true;
        }

        $effective = self::getEffective($menuKey);

        if ($effective === null) {
            // If no permission definition exists, check child item permissions
            $allPermissions = self::getAllCorePermissionsFlat();

            foreach ($allPermissions as $key => $permission) {
                if (str_starts_with($key, $menuKey.'.')) {
                    if ($userRole->value >= $permission['view_roles']) {
                        return true;
                    }
                }
            }

            return false;
        }

        return $userRole->value >= $effective['view_roles'];
    }

    /**
     * Check if user can access plugin menu
     */
    public static function canAccessPlugin(string $pluginSlug, string $menuKey, MemberRole $userRole): bool
    {
        if ($userRole === MemberRole::SUPER_ADMIN) {
            return true;
        }

        $effective = self::getPluginEffective($pluginSlug, $menuKey);

        if ($effective === null) {
            // If no default exists, accessible by ADMIN or higher
            return $userRole->value >= MemberRole::ADMIN->value;
        }

        return $userRole->value >= $effective['access_roles'];
    }

    /**
     * Check if user can view plugin menu
     */
    public static function canViewPlugin(string $pluginSlug, string $menuKey, MemberRole $userRole): bool
    {
        if ($userRole === MemberRole::SUPER_ADMIN) {
            return true;
        }

        $effective = self::getPluginEffective($pluginSlug, $menuKey);

        if ($effective === null) {
            return $userRole->value >= MemberRole::ADMIN->value;
        }

        return $userRole->value >= $effective['view_roles'];
    }

    /**
     * Clear cache
     */
    public static function clearCache(): void
    {
        // Clear Core permission cache (flatten and get all keys)
        $corePermissions = config('roles.permissions', []);
        $flat = [];
        self::flattenPermissions($corePermissions, '', $flat);

        foreach (array_keys($flat) as $menuKey) {
            Cache::forget(CacheKey::core(self::CACHE_DOMAIN, $menuKey));
        }
        Cache::forget(CacheKey::core(self::CACHE_DOMAIN, 'all'));
        Cache::forget(CacheKey::core(self::CACHE_DOMAIN, 'all-flat'));

        // Clear plugin permission cache
        foreach (array_keys(self::$pluginPermissions) as $pluginSlug) {
            // Clear entire plugin cache
            Cache::forget(CacheKey::plugin($pluginSlug, self::CACHE_DOMAIN, 'all'));

            // Clear cache for individual plugin menu keys
            $pluginPerms = self::$pluginPermissions[$pluginSlug] ?? [];
            $pluginFlat = [];
            self::flattenPermissions($pluginPerms, '', $pluginFlat);

            foreach (array_keys($pluginFlat) as $menuKey) {
                Cache::forget(CacheKey::plugin($pluginSlug, self::CACHE_DOMAIN, $menuKey));
            }
        }
    }

    /**
     * Clear cache for specific menu key
     */
    public static function clearMenuCache(string $menuKey, ?string $pluginSlug = null): void
    {
        if ($pluginSlug) {
            Cache::forget(CacheKey::plugin($pluginSlug, self::CACHE_DOMAIN, $menuKey));
            Cache::forget(CacheKey::plugin($pluginSlug, self::CACHE_DOMAIN, 'all'));
            Cache::forget(CacheKey::plugin($pluginSlug, self::CACHE_DOMAIN, 'all-flat'));
        } else {
            Cache::forget(CacheKey::core(self::CACHE_DOMAIN, $menuKey));
            Cache::forget(CacheKey::core(self::CACHE_DOMAIN, 'all'));
            Cache::forget(CacheKey::core(self::CACHE_DOMAIN, 'all-flat'));
        }
    }

    /**
     * Get list of registered plugin slugs
     */
    public static function getRegisteredPlugins(): array
    {
        return array_keys(self::$pluginPermissions);
    }
}
