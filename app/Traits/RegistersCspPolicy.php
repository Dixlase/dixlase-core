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

use App\Services\Csp\CspExtensionLoader;

/**
 * CSP policy registration trait
 *
 * By using this trait in a plugin or theme ServiceProvider,
 * CSP settings defined in plugin.json/theme.json can be automatically registered to CspPolicyRegistry.
 *
 * @example
 * class MyPluginServiceProvider extends ServiceProvider
 * {
 *     use RegistersCspPolicy;
 *
 *     public function boot()
 *     {
 *         $this->registerCspFromJson('plugin', 'my-plugin');
 *     }
 * }
 */
trait RegistersCspPolicy
{
    /**
     * Load CSP settings from plugin.json/theme.json and register to registry
     *
     * @param  string  $type  'plugin' or 'theme'
     * @param  string  $slug  Plugin/theme slug
     * @return array Registered directives
     */
    protected function registerCspFromJson(string $type, string $slug): array
    {
        try {
            $loader = app(CspExtensionLoader::class);

            if ($type === 'plugin') {
                return $loader->loadPlugin($slug);
            } else {
                return $loader->loadTheme($slug);
            }
        } catch (\Exception $e) {
            // Failed to register CSP
        }

        return [];
    }

    /**
     * Register CSP directives directly
     *
     * Use when registering CSP directives directly from code without using plugin.json/theme.json
     *
     * @param  array  $directives  Directives array
     * @param  string|null  $source  Source name (for debugging)
     */
    protected function registerCspDirectives(array $directives, ?string $source = null): void
    {
        try {
            $registry = app(\App\Services\Csp\CspPolicyRegistry::class);
            if (! empty($directives)) {
                $registry->addDirectives($directives, $source);
            }
        } catch (\Exception $e) {
            // Failed to register directives
        }
    }
}
