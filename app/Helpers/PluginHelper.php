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

namespace App\Helpers;

use App\Http\Middleware\EnsurePluginActiveOnSite;
use App\Models\Plugin;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class PluginHelper
{
    /**
     * Get list of enabled plugins
     */
    public static function getEnabledPlugins(): Collection
    {
        try {
            if (! Schema::hasTable('plugins')) {
                return collect();
            }

            return Plugin::enabled()->get();
        } catch (\Exception $e) {
            Log::error('PluginHelper: Failed to get enabled plugins', [
                'error' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    /**
     * Check if plugin is enabled
     *
     * @param  string  $slug  Plugin slug
     */
    public static function isEnabled(string $slug): bool
    {
        try {
            if (! Schema::hasTable('plugins')) {
                return false;
            }

            return Plugin::where('slug', $slug)->whereNotNull('enabled_at')->exists();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Runtime cache of capability information for enabled plugins
     *
     * @var array<string, array<int, string>>|null [slug => [capability, ...]]
     */
    private static ?array $enabledCapabilityCache = null;

    /**
     * Runtime cache of capability information for plugins with files (regardless of enabled status)
     *
     * @var array<string, array<int, string>>|null [directory => [capability, ...]]
     */
    private static ?array $installedCapabilityCache = null;

    /**
     * Check if any enabled plugin declares the specified capability
     *
     * Scans the `capabilities` array in plugin.json (e.g. ["seo", "backup"])
     * Plugins without defined capabilities are ignored
     *
     * @param  string  $capability  Capability identifier (e.g. 'seo')
     */
    public static function hasCapability(string $capability): bool
    {
        $map = self::getEnabledCapabilityMap();

        foreach ($map as $capabilities) {
            if (in_array($capability, $capabilities, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if any plugin with files added (directory exists)
     * declares the specified capability
     *
     * Detects as long as plugin.json exists, even if not enabled
     * Use when you want to guide users to plugins that are added but not yet enabled
     *
     * @param  string  $capability  Capability identifier
     */
    public static function hasCapabilityInAnyInstalled(string $capability): bool
    {
        $map = self::getInstalledCapabilityMap();

        foreach ($map as $capabilities) {
            if (in_array($capability, $capabilities, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if enabled plugin with specified slug declares the specified capability
     *
     * Use for checking feature support on a per-plugin basis
     * Example: SEO plugin checks if dixlase-pages declares support for seo-meta
     *
     * @param  string  $slug  Plugin slug (e.g. 'dixlase-pages')
     * @param  string  $capability  Capability identifier (e.g. 'seo-meta')
     */
    public static function pluginHasCapability(string $slug, string $capability): bool
    {
        $map = self::getEnabledCapabilityMap();

        return in_array($capability, $map[$slug] ?? [], true);
    }

    /**
     * Get list of slugs for enabled plugins that declare the specified capability
     *
     * For use cases like an SEO plugin retrieving a "list of plugins that support SEO meta"
     * to auto-generate integration settings UI
     *
     * @param  string  $capability  Capability identifier (e.g. 'seo-meta')
     * @return array<int, string> Array of plugin slugs
     */
    public static function getEnabledPluginSlugsByCapability(string $capability): array
    {
        $map = self::getEnabledCapabilityMap();
        $slugs = [];
        foreach ($map as $slug => $capabilities) {
            if (in_array($capability, $capabilities, true)) {
                $slugs[] = $slug;
            }
        }

        return $slugs;
    }

    /**
     * Get info records for enabled plugins that declare the specified capability
     *
     * Returns EnabledPluginRecord DTOs (slug, name, directory, description, version)
     * instead of Plugin Eloquent models, so plugins can build integration UIs
     * without depending on \App\Models\Plugin.
     *
     * @param  string  $capability  Capability identifier (e.g. 'seo-meta')
     * @return array<int, \App\DTO\Plugin\EnabledPluginRecord>
     */
    public static function getEnabledPluginInfosByCapability(string $capability): array
    {
        $targetSlugs = self::getEnabledPluginSlugsByCapability($capability);
        if (empty($targetSlugs)) {
            return [];
        }

        $repository = app(\App\Contracts\Repositories\PluginRepositoryInterface::class);

        return $repository->getEnabled()
            ->filter(fn (\App\DTO\Plugin\EnabledPluginRecord $record) => in_array($record->slug, $targetSlugs, true))
            ->values()
            ->all();
    }

    /**
     * Get capabilities declared by the plugin/theme in the specified directory
     *
     * Read and return the `capabilities` array from
     * plugins/{directory}/plugin.json or themes/{directory}/plugin.json
     * Returns empty array if undeclared or plugin.json does not exist
     *
     * @return array<int, string>
     */
    public static function getCapabilitiesForDirectory(string $directory): array
    {
        $map = self::getInstalledCapabilityMap();
        if (isset($map[$directory])) {
            return $map[$directory];
        }

        // Check theme directory if not found in plugin map
        $themePath = base_path("themes/{$directory}");
        $json = self::readPluginJson($themePath);

        return self::extractCapabilities($json);
    }

    /**
     * Clear runtime cache (mainly for testing)
     */
    public static function clearCapabilityCache(): void
    {
        self::$enabledCapabilityCache = null;
        self::$installedCapabilityCache = null;
    }

    /**
     * Get capability map of active plugins
     *
     * @return array<string, array<int, string>>
     */
    private static function getEnabledCapabilityMap(): array
    {
        if (self::$enabledCapabilityCache !== null) {
            return self::$enabledCapabilityCache;
        }

        $map = [];
        foreach (self::getEnabledPlugins() as $plugin) {
            $json = self::readPluginJson(self::getPluginPath($plugin->directory));
            $map[$plugin->slug] = self::extractCapabilities($json);
        }

        return self::$enabledCapabilityCache = $map;
    }

    /**
     * Get capability map of file-existing plugins
     *
     * @return array<string, array<int, string>>
     */
    private static function getInstalledCapabilityMap(): array
    {
        if (self::$installedCapabilityCache !== null) {
            return self::$installedCapabilityCache;
        }

        $map = [];
        $pluginsDir = base_path('plugins');
        if (! File::isDirectory($pluginsDir)) {
            return self::$installedCapabilityCache = $map;
        }

        foreach (File::directories($pluginsDir) as $dir) {
            $json = self::readPluginJson($dir);
            if ($json === null) {
                continue;
            }
            $map[basename($dir)] = self::extractCapabilities($json);
        }

        return self::$installedCapabilityCache = $map;
    }

    /**
     * Read plugin.json
     *
     * @return array<string, mixed>|null
     */
    private static function readPluginJson(string $pluginPath): ?array
    {
        $jsonPath = $pluginPath.'/plugin.json';
        if (! File::exists($jsonPath)) {
            return null;
        }

        try {
            $data = json_decode(File::get($jsonPath), true, 512, JSON_THROW_ON_ERROR);

            return is_array($data) ? $data : null;
        } catch (\JsonException $e) {
            Log::warning('PluginHelper: Failed to parse plugin.json', [
                'path' => $jsonPath,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Extract capabilities from plugin.json
     *
     * @param  array<string, mixed>|null  $json
     * @return array<int, string>
     */
    private static function extractCapabilities(?array $json): array
    {
        if ($json === null) {
            return [];
        }

        $capabilities = $json['capabilities'] ?? [];
        if (! is_array($capabilities)) {
            return [];
        }

        return array_values(array_filter($capabilities, 'is_string'));
    }

    /**
     * Get plugin path
     *
     * @param  string  $directory  Plugin directory name
     */
    public static function getPluginPath(string $directory): string
    {
        return base_path("plugins/{$directory}");
    }

    /**
     * Load admin panel routes for active plugins
     *
     * This method is intended to be called within an authenticated route group
     * in routes/admin.php. This ensures that authentication middleware is
     * automatically applied to plugin routes as well
     */
    public static function loadEnabledAdminRoutes(): void
    {
        // Skip if not yet installed or table does not exist.
        // config('app.installed') is consulted first because env('INSTALLED')
        // returns null once Laravel's config cache (bootstrap/cache/config.php)
        // is built; env() remains as a fallback for the pre-cache window.
        if (! file_exists(base_path('.env')) || ! (config('app.installed', false) ?: env('INSTALLED', false))) {
            return;
        }

        try {
            $enabledPlugins = self::getEnabledPlugins();

            foreach ($enabledPlugins as $plugin) {
                $adminRoutePath = self::getPluginPath($plugin->directory).'/routes/admin.php';

                if (File::exists($adminRoutePath)) {
                    include $adminRoutePath;
                }
            }
        } catch (\Exception $e) {
            Log::error('PluginHelper: Failed to load plugin admin routes', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Load web routes for enabled plugins
     *
     * This method is intended to be called within routes/web.php
     */
    public static function loadEnabledWebRoutes(): void
    {
        // Skip if not yet installed or table does not exist.
        // config('app.installed') is consulted first because env('INSTALLED')
        // returns null once Laravel's config cache (bootstrap/cache/config.php)
        // is built; env() remains as a fallback for the pre-cache window.
        if (! file_exists(base_path('.env')) || ! (config('app.installed', false) ?: env('INSTALLED', false))) {
            return;
        }

        try {
            $enabledPlugins = self::getEnabledPlugins();

            foreach ($enabledPlugins as $plugin) {
                $webRoutePath = self::getPluginPath($plugin->directory).'/routes/web.php';

                if (File::exists($webRoutePath)) {
                    include $webRoutePath;
                }
            }
        } catch (\Exception $e) {
            Log::error('PluginHelper: Failed to load plugin web routes', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Load API routes for enabled plugins.
     *
     * Two layouts are supported:
     *
     *   1. PREFERRED — plugins/{Name}/routes/api/v1.php
     *      Auto-wrapped by core in:
     *        Route::prefix('api/v1')
     *            ->middleware(EnsurePluginActiveOnSite::class.':'.$slug)
     *            ->group(...)
     *      Plugin authors only write the slug-relative path inside.
     *      The middleware returns a 404 JSON envelope if the plugin is
     *      not active on the resolved site.
     *
     *   2. LEGACY (deprecated) — plugins/{Name}/routes/api.php
     *      Loaded as-is at the application root with no auto prefix
     *      and no per-site activation gate. A deprecation warning is
     *      written to the application log on every boot until the
     *      plugin migrates to layout (1).
     *
     * If both files exist for the same plugin, only the v1 layout is
     * loaded and the legacy file is ignored (with a warning) so plugin
     * authors get a clear push toward the canonical layout.
     *
     * Called from PluginServiceProvider::boot().
     */
    public static function loadEnabledApiRoutes(): void
    {
        // Skip if not yet installed or table does not exist.
        // config('app.installed') is consulted first because env('INSTALLED')
        // returns null once Laravel's config cache (bootstrap/cache/config.php)
        // is built; env() remains as a fallback for the pre-cache window.
        if (! file_exists(base_path('.env')) || ! (config('app.installed', false) ?: env('INSTALLED', false))) {
            return;
        }

        try {
            $enabledPlugins = self::getEnabledPlugins();

            foreach ($enabledPlugins as $plugin) {
                $pluginPath = self::getPluginPath($plugin->directory);
                $v1Path = $pluginPath.'/routes/api/v1.php';
                $legacyPath = $pluginPath.'/routes/api.php';

                $hasV1 = File::exists($v1Path);
                $hasLegacy = File::exists($legacyPath);

                if ($hasV1) {
                    Route::prefix('api/v1')
                        ->middleware(EnsurePluginActiveOnSite::class.':'.$plugin->slug)
                        ->group(function () use ($v1Path) {
                            require $v1Path;
                        });

                    if ($hasLegacy) {
                        Log::warning('PluginHelper: routes/api.php ignored because routes/api/v1.php is also present', [
                            'plugin' => $plugin->directory,
                        ]);
                    }

                    continue;
                }

                if ($hasLegacy) {
                    Log::warning('PluginHelper: plugin uses deprecated routes/api.php; migrate to routes/api/v1.php for auto-prefix and per-site activation gating', [
                        'plugin' => $plugin->directory,
                    ]);
                    include $legacyPath;
                }
            }
        } catch (\Exception $e) {
            Log::error('PluginHelper: Failed to load plugin API routes', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Register a shortcode
     *
     * Call from the plugin's ServiceProvider to use
     *
     * Usage example:
     *   PluginHelper::registerShortcode('menu', MenuShortcode::class);
     *
     * @param  string  $name  Shortcode name
     * @param  string  $class  Shortcode class name
     * @return bool Whether registration was successful
     */
    public static function registerShortcode(string $name, string $class): bool
    {
        try {
            if (app()->bound('shortcode')) {
                $shortcode = app('shortcode');
                $shortcode->add($name, $class);

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('PluginHelper: Failed to register shortcode', [
                'name' => $name,
                'class' => $class,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Register multiple shortcodes in bulk
     *
     * Usage example:
     *   PluginHelper::registerShortcodes([
     *       'menu' => MenuShortcode::class,
     *       'submenu' => SubMenuShortcode::class,
     *   ]);
     *
     * @param  array  $shortcodes  Array of ['name' => 'ClassName']
     * @return int Number of successful registrations
     */
    public static function registerShortcodes(array $shortcodes): int
    {
        $count = 0;
        foreach ($shortcodes as $name => $class) {
            if (self::registerShortcode($name, $class)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Register a link source (for menu plugin)
     *
     * Register a link source to the menu plugin's MenuLinkSourceManager
     *
     * Usage example:
     *   PluginHelper::registerLinkSource(new PageLinkSource());
     *
     * @param  object  $source  Link source instance
     * @return bool Whether registration was successful
     */
    public static function registerLinkSource(object $source): bool
    {
        try {
            $managerClass = 'Plugins\\DixlaseMenus\\App\\Services\\MenuLinkSourceManager';

            if (app()->bound($managerClass)) {
                $manager = app($managerClass);
                $manager->register($source);

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('PluginHelper: Failed to register link source', [
                'source' => get_class($source),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Register multiple link sources in bulk
     *
     * Usage example:
     *   PluginHelper::registerLinkSources([
     *       new PageLinkSource(),
     *       new PostLinkSource(),
     *   ]);
     *
     * @param  array  $sources  Array of link source instances
     * @return int Number of successful registrations
     */
    public static function registerLinkSources(array $sources): int
    {
        $count = 0;
        foreach ($sources as $source) {
            if (self::registerLinkSource($source)) {
                $count++;
            }
        }

        return $count;
    }
}
