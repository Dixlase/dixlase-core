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

namespace App\Traits;

use App\Enums\AppearanceMode;
use Illuminate\Support\Facades\Auth;

/**
 * Trait for common initialization after admin panel login
 */
trait AdminLoggedInTrait
{
    protected $member;

    protected $appearance;

    protected $breadcrumbs = [];

    protected $description = null;

    /**
     * Perform common initialization required after login
     */
    protected function initializeAfterLogin()
    {
        $this->middleware(function ($request, $next) {
            $this->setMember();

            // Apply member's language settings (same logic as SetMemberLocale middleware)
            if ($this->member && $this->member->locale) {
                $locale = $this->member->locale instanceof \App\Enums\Locale
                    ? $this->member->locale->value
                    : $this->member->locale;

                if (\App\Enums\Locale::isValid($locale)) {
                    app()->setLocale($locale);
                }
            }

            $transition = config('admin.transition_class');
            $this->viewParams['transition'] = $transition;

            // Auto-generate breadcrumbs (executed after locale settings)
            if (empty($this->breadcrumbs)) {
                $this->generateBreadcrumbsFromRoute();
            }

            // Auto-set page description
            if ($this->description === null) {
                $this->setDescription();
            }

            return $next($request);
        });
    }

    /**
     * Add item to breadcrumbs
     */
    protected function addBreadcrumb(?string $route, string $label): void
    {
        $this->breadcrumbs[] = [
            'route' => $route,
            'label' => $label,
        ];
    }

    /**
     * Set breadcrumbs to view parameters
     */
    protected function setBreadcrumbs(): void
    {
        $this->viewParams['breadcrumbs'] = $this->breadcrumbs;
    }

    /**
     * Auto-generate breadcrumbs from route name
     * Example: admin.members.settings.index → Dashboard > Member Management > Member Global Settings
     */
    protected function generateBreadcrumbsFromRoute(): void
    {
        $routeName = request()->route()?->getName();

        if (! $routeName) {
            return;
        }

        // For plugin route names
        if (str_contains($routeName, '::')) {
            $this->generatePluginBreadcrumbs($routeName);
        } else {
            $this->generateCoreBreadcrumbs($routeName);
        }

        $this->setBreadcrumbs();
    }

    /**
     * Generate Core breadcrumbs
     */
    protected function generateCoreBreadcrumbs(string $routeName): void
    {
        // Remove admin.
        $parts = explode('.', $routeName);
        array_shift($parts); // Remove 'admin'

        if (empty($parts)) {
            return;
        }

        // Remove if last part is 'index' (to avoid duplication)
        if (end($parts) === 'index') {
            array_pop($parts);
        }

        if (empty($parts)) {
            return;
        }

        // Add "Admin Panel" at the beginning (link to dashboard)
        $this->addBreadcrumb('admin.dashboard', __('common.admin_panel'));

        // Generate breadcrumbs for each level
        $currentPath = 'admin';
        $translationPath = 'admin';

        foreach ($parts as $index => $part) {
            $currentPath .= '.'.$part;
            $translationPath .= '/'.$part;

            // Last element (current page) has no link
            $route = null;
            if ($index < count($parts) - 1) {
                // Check if intermediate route exists (try with .index appended)
                $indexRouteName = $currentPath.'.index';
                if (\Route::has($indexRouteName)) {
                    $route = $indexRouteName;
                } elseif (\Route::has($currentPath)) {
                    $route = $currentPath;
                }
            }

            // Try translation keys: index.heading or nav.{part}
            $label = $this->resolveBreadcrumbLabel($translationPath, $part);

            if ($label) {
                $this->addBreadcrumb($route, $label);
            }
        }
    }

    /**
     * Generate plugin breadcrumbs
     */
    protected function generatePluginBreadcrumbs(string $routeName): void
    {
        // Separate plugin name and route part
        [$pluginPrefix, $route] = explode('::', $routeName, 2);

        // Remove admin.
        $parts = explode('.', $route);
        if ($parts[0] === 'admin') {
            array_shift($parts);
        }

        if (empty($parts)) {
            return;
        }

        // Remove if last part is 'index' (to avoid duplication)
        if (end($parts) === 'index') {
            array_pop($parts);
        }

        if (empty($parts)) {
            return;
        }

        // Add "Admin Panel" at the beginning (link to dashboard)
        $this->addBreadcrumb('admin.dashboard', __('common.admin_panel'));

        // Generate breadcrumbs for each level
        $currentPath = 'admin';
        $translationPath = 'admin';

        foreach ($parts as $index => $part) {
            $currentPath .= '.'.$part;
            $translationPath .= '/'.$part;

            // Last element (current page) has no link
            $route = null;
            if ($index < count($parts) - 1) {
                // Check if intermediate route exists (try with .index appended)
                $indexRouteName = $pluginPrefix.'::'.$currentPath.'.index';
                if (\Route::has($indexRouteName)) {
                    $route = $indexRouteName;
                } else {
                    $fullRouteName = $pluginPrefix.'::'.$currentPath;
                    if (\Route::has($fullRouteName)) {
                        $route = $fullRouteName;
                    }
                }
            }

            // Try translation key
            $label = $this->resolvePluginBreadcrumbLabel($pluginPrefix, $translationPath, $part);

            if ($label) {
                $this->addBreadcrumb($route, $label);
            }
        }
    }

    /**
     * Resolve Core translation label
     */
    protected function resolveBreadcrumbLabel(string $translationPath, string $part): ?string
    {
        // Pattern 1: {path}/index.heading
        $indexKey = $translationPath.'/index.heading';
        if (\Lang::has($indexKey)) {
            $label = __($indexKey);

            return $label;
        }

        // Pattern 2: {path}.heading
        $headingKey = $translationPath.'.heading';
        if (\Lang::has($headingKey)) {
            $label = __($headingKey);

            return $label;
        }

        // Pattern 3: parent nav.{part}
        $pathParts = explode('/', $translationPath);
        if (count($pathParts) >= 2) {
            $lastPart = array_pop($pathParts);
            $parentPath = implode('/', $pathParts);
            $navKey = $parentPath.'/index.nav.'.$lastPart;
            if (\Lang::has($navKey)) {
                $label = __($navKey);

                return $label;
            }
        }

        // Pattern 4: admin/navigation.{part}.text (if array)
        $navTextKey = 'admin/navigation.'.$part.'.text';
        if (\Lang::has($navTextKey)) {
            $label = __($navTextKey);

            return $label;
        }

        // Pattern 5: admin/navigation.{part} (if string)
        $navKey = 'admin/navigation.'.$part;
        if (\Lang::has($navKey)) {
            $value = __($navKey);
            // If array is returned, try .text
            if (is_array($value) && isset($value['text'])) {
                return $value['text'];
            }
            // If string, return as-is
            if (is_string($value)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Resolve plugin translation label
     */
    protected function resolvePluginBreadcrumbLabel(string $pluginPrefix, string $translationPath, string $part): ?string
    {
        // Pattern 1: plugin::{path}/index.heading
        $indexKey = $pluginPrefix.'::'.$translationPath.'/index.heading';
        if (\Lang::has($indexKey)) {
            return __($indexKey);
        }

        // Pattern 2: plugin::{path}.heading
        $headingKey = $pluginPrefix.'::'.$translationPath.'.heading';
        if (\Lang::has($headingKey)) {
            return __($headingKey);
        }

        // Pattern 3: parent nav.{part}
        $pathParts = explode('/', $translationPath);
        if (count($pathParts) >= 2) {
            $lastPart = array_pop($pathParts);
            $parentPath = implode('/', $pathParts);
            $navKey = $pluginPrefix.'::'.$parentPath.'/index.nav.'.$lastPart;
            if (\Lang::has($navKey)) {
                return __($navKey);
            }
        }

        // Pattern 4: plugin::admin/navigation.{part}.text (if array)
        $navTextKey = $pluginPrefix.'::admin/navigation.'.$part.'.text';
        if (\Lang::has($navTextKey)) {
            return __($navTextKey);
        }

        // Pattern 5: plugin::admin/navigation.{part} (if string)
        $navKey = $pluginPrefix.'::admin/navigation.'.$part;
        if (\Lang::has($navKey)) {
            $value = __($navKey);
            // If array is returned, try .text
            if (is_array($value) && isset($value['text'])) {
                return $value['text'];
            }
            // If string, return as-is
            if (is_string($value)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Set page description
     * When called without arguments, automatically generates translation key from current route name and retrieves description
     */
    protected function setDescription(?string $description = null): void
    {
        if ($description === null) {
            $description = $this->getDescriptionFromRoute();
        }

        $this->description = $description;
        $this->viewParams['description'] = $this->description;
    }

    /**
     * Generate translation key from current route name and retrieve description
     */
    protected function getDescriptionFromRoute(): ?string
    {
        $routeName = request()->route()?->getName();

        if (! $routeName) {
            return null;
        }

        // For plugin route names, process prefix
        // Example: dixlase-users::admin.users.index -> dixlase-users::admin/users/index.description
        if (str_contains($routeName, '::')) {
            [$pluginPrefix, $route] = explode('::', $routeName, 2);

            // Convert route part to path
            $routePath = str_replace('.', '/', $route);

            // Plugin translation key format: plugin-name::path.description
            $translationKey = $pluginPrefix.'::'.$routePath.'.description';
        } else {
            // Convert regular route name to translation key
            // Example: admin.members.settings -> admin/members/settings.description
            //     admin.settings.base.site -> admin/settings/base/site.description
            $translationKey = str_replace('.', '/', $routeName).'.description';
        }

        // Check if translation exists
        $translation = __($translationKey);

        // If the translation key is returned as-is, the translation does not exist
        if ($translation === $translationKey) {
            return null;
        }

        return $translation;
    }

    /**
     * Get and set administrator information
     */
    protected function setMember()
    {
        $this->member = Auth::guard('member')->user();
        $this->viewParams['member'] = $this->member;

        $this->appearance = $this->member->appearance?->value ?? AppearanceMode::Auto->value;
        $this->viewParams['appearance'] = $this->appearance;

        $this->viewParams['sidebar_hidden_menus'] = $this->member->sidebar_preferences['hidden'] ?? [];
        $this->viewParams['sidebar_menu_order'] = $this->member->sidebar_preferences['order'] ?? [];
    }
}
