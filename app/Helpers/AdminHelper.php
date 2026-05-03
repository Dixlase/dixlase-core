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

namespace App\Helpers;

use App\Enums\MemberRole;
use App\Models\Member;
use App\Models\SiteSetting;
use App\Services\PermissionRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/**
 * Admin utilities (admin URL prefix, navigation cache, permission helpers).
 * Plugins/themes that build admin views/middleware/routes may use this helper.
 */
class AdminHelper
{
    /**
     * Navigation settings cache
     * Managed with static variable because Laravel's config() is not reliable
     */
    private static $navigationCache = null;

    /**
     * Get currently logged-in member
     */
    public static function getMember(): ?Member
    {
        return Auth::guard('member')->user();
    }

    /**
     * Check if request matches admin panel URL
     * Support dynamically generated admin URLs
     */
    public static function isAdminRequest(Request $request): bool
    {
        $adminUrl = self::getAdminUrl();

        return $request->is($adminUrl) || $request->is($adminUrl.'/*');
    }

    public static function getAdminUrl()
    {
        // admin_url setting is defined in config/admin/url.php → 'admin.url.admin_url'
        $configDefault = config('admin.url.admin_url', 'admin');

        // Fallback to $_SERVER / $_ENV to prevent env() returning null when config is cached
        $installed = $_SERVER['INSTALLED'] ?? $_ENV['INSTALLED'] ?? env('INSTALLED') ?? config('app.installed');
        $isInstalled = ($installed === 'true' || $installed === true);

        // Return config value if before installation or on database connection error
        if (! file_exists(base_path('.env')) || ! $isInstalled) {
            return $configDefault;
        }

        try {
            if (Schema::hasTable('site_settings')) {
                return SiteSetting::getValue('admin_url', $configDefault);
            }

            return $configDefault;
        } catch (\Exception $e) {
            return $configDefault;
        }
    }

    public static function canAccessMenu(string $menuKey): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        // Profile is accessible to everyone (own settings)
        if (str_starts_with($menuKey, 'profile')) {
            return true;
        }

        // Dashboard is accessible to everyone (view only)
        if ($menuKey === 'dashboard') {
            return true;
        }

        if ($user->role->value === MemberRole::SUPER_ADMIN->value) {
            return true;
        }

        // Check effective permission using PermissionRegistry
        $effective = PermissionRegistry::getEffective($menuKey);

        // If no permission definition exists, check child item permissions
        if ($effective === null) {
            // Search child item permissions (e.g., media -> media.index, media.upload)
            $allPermissions = PermissionRegistry::getAllCorePermissionsFlat();
            $hasChildAccess = false;

            foreach ($allPermissions as $key => $permission) {
                if (str_starts_with($key, $menuKey.'.')) {
                    if ($user->role->value >= $permission['access_roles'] ||
                        $user->role->value >= $permission['view_roles']) {
                        $hasChildAccess = true;
                        break;
                    }
                }
            }

            return $hasChildAccess;
        }

        // Access granted if user's permission value is at or above the configured minimum permission value
        return $user->role->value >= $effective['access_roles'] ||
               $user->role->value >= $effective['view_roles'];
    }

    /**
     * Check menu view permission (can display but cannot edit)
     * Viewable if user's permission value is at or above view_roles
     */
    public static function canViewMenu(string $menuKey): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        // Profile is viewable by everyone (own settings)
        if (str_starts_with($menuKey, 'profile')) {
            return true;
        }

        // Dashboard is viewable by everyone
        if ($menuKey === 'dashboard') {
            return true;
        }

        if ($user->role->value === MemberRole::SUPER_ADMIN->value) {
            return true;
        }

        return PermissionRegistry::canView($menuKey, $user->role);
    }

    /**
     * Check menu edit permission
     * Editable if user's permission value is at or above access_roles
     */
    public static function canEditMenu(string $menuKey): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        // Profile is editable by everyone (own settings)
        if (str_starts_with($menuKey, 'profile')) {
            return true;
        }

        // Dashboard is view-only and not editable
        if ($menuKey === 'dashboard') {
            return false;
        }

        if ($user->role->value === MemberRole::SUPER_ADMIN->value) {
            return true;
        }

        return PermissionRegistry::canAccess($menuKey, $user->role);
    }

    /**
     * Check access permission for plugin menu
     */
    public static function canAccessPluginMenu(string $pluginSlug, string $menuKey): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        if ($user->role->value === MemberRole::SUPER_ADMIN->value) {
            return true;
        }

        // Use PermissionRegistry
        $effective = PermissionRegistry::getPluginEffective($pluginSlug, $menuKey);

        if ($effective === null) {
            // If no permission settings exist, accessible by ADMIN or higher
            return $user->role->value >= MemberRole::ADMIN->value;
        }

        return $user->role->value >= $effective['access_roles'] ||
               $user->role->value >= $effective['view_roles'];
    }

    /**
     * Check view permission for plugin menu
     */
    public static function canViewPluginMenu(string $pluginSlug, string $menuKey): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        if ($user->role->value === MemberRole::SUPER_ADMIN->value) {
            return true;
        }

        return PermissionRegistry::canViewPlugin($pluginSlug, $menuKey, $user->role);
    }

    /**
     * Check edit permission for plugin menu
     */
    public static function canEditPluginMenu(string $pluginSlug, string $menuKey): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        if ($user->role->value === MemberRole::SUPER_ADMIN->value) {
            return true;
        }

        return PermissionRegistry::canAccessPlugin($pluginSlug, $menuKey, $user->role);
    }

    /**
     * Check edit permission for menu (Core/plugin integration)
     *
     * @param  string|null  $pluginSlug  Plugin slug (null for Core menu)
     * @param  string  $menuKey  Menu key
     * @return bool True if edit permission exists
     */
    public static function canEditMenuOrPlugin(?string $pluginSlug, string $menuKey): bool
    {
        if ($pluginSlug) {
            return self::canEditPluginMenu($pluginSlug, $menuKey);
        }

        return self::canEditMenu($menuKey);
    }

    /**
     * Check view permission for menu (Core/plugin integration)
     *
     * @param  string|null  $pluginSlug  Plugin slug (null for Core menu)
     * @param  string  $menuKey  Menu key
     * @return bool True if view permission exists
     */
    public static function canViewMenuOrPlugin(?string $pluginSlug, string $menuKey): bool
    {
        if ($pluginSlug) {
            return self::canViewPluginMenu($pluginSlug, $menuKey);
        }

        return self::canViewMenu($menuKey);
    }

    /**
     * Merge admin panel navigation settings
     *
     * @param  string  $name  Plugin/theme name (for debugging)
     * @param  string  $configPath  Path to settings file
     */
    public static function mergeAdminNavigation(string $name = 'Unknown', ?string $configPath = null): void
    {
        $configFile = $configPath;

        if (! file_exists($configFile)) {
            return;
        }

        $config = require $configFile;

        if (! isset($config['nav']) || ! is_array($config['nav'])) {
            return;
        }

        // Get existing navigation settings
        // IMPORTANT: Cache with static variable to avoid Laravel's config() issues
        // Always use cache if available (preserve plugin merges)
        if (self::$navigationCache !== null) {
            $existingNav = self::$navigationCache;
        } else {
            // Get from config() on first access only
            // IMPORTANT: Always use app()->config['admin.navigation'] (to preserve plugin merges)
            $fromAppConfig = app()->config['admin.navigation'] ?? null;

            // Load from config() only when app()->config is null
            if ($fromAppConfig === null) {
                $fromAppConfig = config('admin.navigation', []);
            }

            $existingNav = $fromAppConfig;
        }

        // Check if Core settings are loaded correctly
        // However, do not reload if settings already exist (to preserve theme and plugin changes)
        if (empty($existingNav) ||
            (! isset($existingNav['settings']['children']) ||
             ! isset($existingNav['settings']['children']['base']) ||
             ! isset($existingNav['settings']['children']['security']))) {
            // Reload only if completely empty
            if (empty($existingNav)) {
                // Load Core settings file directly
                $coreConfigPath = config_path('admin.php');
                if (file_exists($coreConfigPath)) {
                    $coreConfig = require $coreConfigPath;
                    if (isset($coreConfig['nav'])) {
                        // Initialize with Core settings
                        $existingNav = $coreConfig['nav'];
                        config(['admin.nav' => $existingNav]);
                    }
                }
            }
        }

        // Merge navigation settings
        foreach ($config['nav'] as $key => $value) {
            // Save insertion position information
            $insertAfter = $value['_insert_after'] ?? null;
            $insertBefore = $value['_insert_before'] ?? null;

            // Remove _insert_after / _insert_before
            unset($value['_insert_after'], $value['_insert_before']);

            // Add plugin_slug information for plugin menus
            if ($name !== 'Unknown' && $name !== 'Theme') {
                $value['plugin_slug'] = $name;
                // Add plugin_slug to child items as well
                if (isset($value['children'])) {
                    $value['children'] = self::addPluginSlugToChildren($value['children'], $name);
                }
            }

            // Merge child items if existing settings are present
            if (isset($existingNav[$key])) {
                // Merge child items while preserving existing settings
                if (isset($value['children']) && isset($existingNav[$key]['children'])) {
                    // Merge recursively (handles 3rd level and deeper)
                    $existingNav[$key]['children'] = self::deepMergeNavigation(
                        $existingNav[$key]['children'],
                        $value['children'],
                        $name
                    );
                } elseif (isset($value['children'])) {
                    $existingNav[$key]['children'] = $value['children'];
                }
                // Update plugin_slug information
                if (isset($value['plugin_slug'])) {
                    $existingNav[$key]['plugin_slug'] = $value['plugin_slug'];
                }
            } else {
                // For new keys, add considering insertion position

                if ($insertAfter || $insertBefore) {
                    // When insertion position is specified
                    $existingNav = self::insertNavItem($existingNav, $key, $value, $insertAfter, $insertBefore, $name);
                } else {
                    // Add at the end if insertion position is not specified
                    $existingNav[$key] = $value;
                }
            }
        }

        // Apply merged settings
        // IMPORTANT: Cache in static variable for use in subsequent merges
        self::$navigationCache = $existingNav;

        // Also reflect in Laravel settings (used in views, etc.)
        app()->config['admin.nav'] = $existingNav;
        config(['admin.nav' => $existingNav]);
    }

    /**
     * Recursively add plugin_slug information to child items
     *
     * @param  array  $children  Array of child items
     * @param  string  $pluginSlug  Plugin slug
     * @return array Array of child items with plugin_slug information added
     */
    protected static function addPluginSlugToChildren(array $children, string $pluginSlug): array
    {
        foreach ($children as $key => $value) {
            $children[$key]['plugin_slug'] = $pluginSlug;
            if (isset($value['children']) && is_array($value['children'])) {
                $children[$key]['children'] = self::addPluginSlugToChildren($value['children'], $pluginSlug);
            }
        }

        return $children;
    }

    /**
     * Insert navigation item at specified position
     *
     * @param  array  $nav  Existing navigation array
     * @param  string  $key  Key of item to insert
     * @param  array  $value  Value of item to insert
     * @param  string|null  $insertAfter  Insert after this item
     * @param  string|null  $insertBefore  Insert before this item
     * @param  string  $name  Name for debugging
     * @return array Navigation array after insertion
     */
    protected static function insertNavItem(array $nav, string $key, array $value, ?string $insertAfter, ?string $insertBefore, string $name): array
    {
        $newNav = [];
        $inserted = false;

        foreach ($nav as $navKey => $navValue) {
            // Handle _insert_before
            if ($insertBefore && $navKey === $insertBefore && ! $inserted) {
                $newNav[$key] = $value;
                $inserted = true;
            }

            // Add existing items
            $newNav[$navKey] = $navValue;

            // Handle _insert_after
            if ($insertAfter && $navKey === $insertAfter && ! $inserted) {
                $newNav[$key] = $value;
                $inserted = true;
            }
        }

        // Add at the end if insertion position is not found
        if (! $inserted) {
            $newNav[$key] = $value;
        }

        return $newNav;
    }

    /**
     * Recursively merge navigation child items
     *
     * @param  array  $existing  Existing navigation array
     * @param  array  $new  New navigation array
     * @param  string  $name  Name for debugging
     * @return array Merged navigation array
     */
    protected static function deepMergeNavigation(array $existing, array $new, string $name = 'Unknown'): array
    {
        foreach ($new as $key => $value) {
            if (isset($existing[$key])) {
                // If existing item exists
                if (is_array($value) && is_array($existing[$key])) {
                    // If both are arrays
                    if (isset($value['children']) && isset($existing[$key]['children'])) {
                        // Recursively merge if there are child items
                        $existing[$key]['children'] = self::deepMergeNavigation(
                            $existing[$key]['children'],
                            $value['children'],
                            $name
                        );
                        // Preserve existing properties other than children
                    } elseif (isset($value['children'])) {
                        // Add children if the new item has them
                        $existing[$key]['children'] = $value['children'];
                    }
                    // Preserve existing properties for others (do not overwrite)
                } else {
                    // Overwrite if not an array
                    $existing[$key] = $value;
                }
            } else {
                // Add as-is if it's a new item
                $existing[$key] = $value;
            }
        }

        return $existing;
    }

    /**
     * Reorder an associative array by a list of keys.
     * Keys not in $orderedKeys are appended at the end in original order.
     *
     * @param  array<string, mixed>  $items
     * @param  array<int, string>  $orderedKeys
     * @return array<string, mixed>
     */
    public static function reorderByKeys(array $items, array $orderedKeys): array
    {
        $reordered = [];
        foreach ($orderedKeys as $key) {
            if (array_key_exists($key, $items)) {
                $reordered[$key] = $items[$key];
            }
        }
        foreach ($items as $key => $item) {
            if (! array_key_exists($key, $reordered)) {
                $reordered[$key] = $item;
            }
        }

        return $reordered;
    }
}
