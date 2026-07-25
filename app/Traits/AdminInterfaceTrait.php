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

namespace App\Traits;

use App\Contracts\Repositories\SiteSettingRepositoryInterface;
use App\Services\Site\SettingResolver;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/**
 * Trait for initializing common admin panel interface
 */
trait AdminInterfaceTrait
{
    // Variable declarations
    protected $siteName;

    protected $heading = '';

    protected $viewParams = [];

    protected $routeName = '';

    protected $settings = [];

    /**
     * Initialization process
     */
    public function initialize()
    {
        // Skip if not yet installed
        if (! file_exists(base_path('.env'))) {
            return;
        }

        // Check if installed (retrieve via config to support caching)
        $isInstalled = config('app.installed', false) ?: env('INSTALLED', false);
        if (! $isInstalled) {
            return;
        }

        try {
            if (! Schema::hasTable('site_settings') || ! Schema::hasTable('global_settings')) {
                return;
            }
        } catch (\Throwable $e) {
            return;
        }

        $this->getSiteName();
        $this->getSiteSettings();
        $this->setRouteName();
        $this->setHeading();
    }

    protected function getSiteName()
    {
        // env > config > resolver (PerSite via SettingResolver) > fallback
        $this->siteName = env('APP_NAME')
            ?? config('app.name')
            ?? app(SettingResolver::class)->get('site_name')
            ?? 'Dixlase';
        $this->viewParams['site_name'] = $this->siteName;
    }

    protected function getSiteSettings()
    {
        // Repository::all() iterates the SettingDefinitionRegistry and
        // resolves each value via SettingResolver. The result is a
        // flat [key => value] map (Global + PerSite + Overridable).
        $this->settings = app(SiteSettingRepositoryInterface::class)->all();
        $this->viewParams['settings'] = $this->settings;
    }

    protected function setRouteName()
    {
        $this->routeName = Route::currentRouteName();
        $this->viewParams['route_name'] = $this->routeName;
    }

    protected function setHeading()
    {
        $routeName = Route::currentRouteName();

        if ($routeName === null) {
            $this->viewParams['heading'] = '';

            return;
        }

        // Determine if it's a plugin route (it's a plugin if :: is included)
        if (strpos($routeName, '::') !== false) {
            $this->heading = $this->resolvePluginHeadingKey($routeName);
        } else {
            $this->heading = $this->resolveCoreHeadingKey($routeName);
        }

        $this->viewParams['heading'] = $this->heading;
    }

    /**
     * Resolve Core translation key
     * Auto-detect appropriate translation file path and key from route name
     *
     * @param  string  $routeName  Route name (e.g., admin.settings.security.captcha)
     * @return string Translation key (e.g., admin/settings/security/captcha.heading)
     */
    protected function resolveCoreHeadingKey(string $routeName): string
    {
        // admin.controller.action → ['controller', 'action']
        $keys = explode('.', $routeName);
        array_shift($keys); // Remove 'admin'

        if (empty($keys)) {
            return 'admin/dashboard.heading';
        }

        // Pattern 1: Full path (e.g., admin/settings/security/captcha.heading)
        $fullPath = 'admin/'.implode('/', $keys).'.heading';
        if (Lang::has($fullPath)) {
            return $fullPath;
        }

        // Pattern 2: For directory structure, reference index.php (e.g., admin/settings/systems/logs/index.heading)
        // admin.settings.systems.logs → admin/settings/systems/logs/index.heading
        $indexPath = 'admin/'.implode('/', $keys).'/index.heading';
        if (Lang::has($indexPath)) {
            return $indexPath;
        }

        // Pattern 3: Reference parent directory's index.php (e.g., admin.settings.systems.logs.files → admin/settings/systems/logs/index.heading)
        if (count($keys) >= 2) {
            $parentKeys = array_slice($keys, 0, -1);
            $parentIndexPath = 'admin/'.implode('/', $parentKeys).'/index.heading';
            if (Lang::has($parentIndexPath)) {
                return $parentIndexPath;
            }
        }

        // Pattern 4: Last element is a key within the file (e.g., admin/media.index.heading)
        if (count($keys) >= 2) {
            $keysCopy = $keys;
            $lastKey = array_pop($keysCopy);
            $filePath = 'admin/'.implode('/', $keysCopy).'.'.$lastKey.'.heading';
            if (Lang::has($filePath)) {
                return $filePath;
            }
        }

        // Pattern 5: Direct heading in a single file (e.g., admin/dashboard.heading)
        if (count($keys) === 1) {
            $singlePath = 'admin/'.$keys[0].'.heading';
            if (Lang::has($singlePath)) {
                return $singlePath;
            }
        }

        // Fallback: Return the first attempted path
        return $fullPath;
    }

    /**
     * Resolve plugin translation key
     *
     * @param  string  $routeName  Route name (e.g., admin.dixlase-inquiry::admin.settings.index)
     * @return string Translation key
     */
    protected function resolvePluginHeadingKey(string $routeName): string
    {
        // New format: admin.plugin-name::admin.controller.action
        if (strpos($routeName, 'admin.') === 0) {
            $withoutAdminPrefix = substr($routeName, 6); // Remove 'admin.'
            [$pluginNamespace, $route] = explode('::', $withoutAdminPrefix, 2);

            // Apply the same logic within plugins
            $keys = explode('.', $route);

            // Pattern 1: Full path
            $fullPath = implode('/', $keys).'.heading';
            if (Lang::has($pluginNamespace.'::'.$fullPath)) {
                return $pluginNamespace.'::'.$fullPath;
            }

            // Pattern 2: Last element is a key within the file
            if (count($keys) >= 2) {
                $lastKey = array_pop($keys);
                $filePath = implode('/', $keys).'.'.$lastKey.'.heading';
                if (Lang::has($pluginNamespace.'::'.$filePath)) {
                    return $pluginNamespace.'::'.$filePath;
                }
            }

            // Fallback
            return $pluginNamespace.'::'.$fullPath;
        }

        // Old format: plugin-name::admin.controller.action
        [$pluginNamespace, $route] = explode('::', $routeName, 2);
        $keys = explode('.', $route);

        // Pattern 1: Full path (directory-based)
        $fullPath = implode('/', $keys).'.heading';
        if (Lang::has($pluginNamespace.'::'.$fullPath)) {
            return $pluginNamespace.'::'.$fullPath;
        }

        // Pattern 2: Last element is a key within the file
        if (count($keys) >= 2) {
            $keysCopy = $keys;
            $lastKey = array_pop($keysCopy);
            $filePath = implode('/', $keysCopy).'.'.$lastKey.'.heading';
            if (Lang::has($pluginNamespace.'::'.$filePath)) {
                return $pluginNamespace.'::'.$filePath;
            }
        }

        // Fallback: Old format dot notation
        array_shift($keys); // Remove 'admin'
        $headingKey = implode('.', $keys).'.heading';

        return $pluginNamespace.'::admin.'.$headingKey;
    }
}
