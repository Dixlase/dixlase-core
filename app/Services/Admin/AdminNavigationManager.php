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

namespace App\Services\Admin;

use App\Contracts\Admin\AdminNavigationManagerInterface;

/**
 * @internal Core use only. Do not reference from plugins/themes
 *
 * Concrete implementation of admin panel navigation management
 *
 * Merge plugin navigation settings into Core's config('admin.navigation')
 * Supports ordering via _insert_before / _insert_after and children merging.
 */
class AdminNavigationManager implements AdminNavigationManagerInterface
{
    /**
     * {@inheritDoc}
     */
    public function mergeNavigationFile(string $configFile): void
    {
        if (! file_exists($configFile)) {
            return;
        }

        $pluginNavigation = require $configFile;

        if (! is_array($pluginNavigation)) {
            return;
        }

        foreach ($pluginNavigation as $key => $value) {
            $this->mergeNavigationItem($key, $value);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function mergeNavConfig(string $configFile): void
    {
        if (! file_exists($configFile)) {
            return;
        }

        $pluginConfig = require $configFile;

        if (! isset($pluginConfig['nav']) || ! is_array($pluginConfig['nav'])) {
            return;
        }

        foreach ($pluginConfig['nav'] as $key => $value) {
            $this->mergeNavigationItem($key, $value);
        }
    }

    /**
     * Merge a single navigation item
     *
     * @param  string  $key  Navigation key
     * @param  array<string, mixed>  $value  Navigation item settings
     */
    private function mergeNavigationItem(string $key, array $value): void
    {
        if (isset($value['_insert_before'])) {
            $this->insertOrdered('admin.navigation', $key, $value, $value['_insert_before'], 'before');
        } elseif (isset($value['_insert_after'])) {
            $this->insertOrdered('admin.navigation', $key, $value, $value['_insert_after'], 'after');
        } else {
            $existingValue = config("admin.navigation.{$key}");
            if ($existingValue !== null && is_array($existingValue)) {
                // If existing settings exist, merge only children and preserve other properties
                if (isset($value['children']) && is_array($value['children'])) {
                    $existingChildren = $existingValue['children'] ?? [];
                    $existingValue['children'] = $this->mergeChildren($existingChildren, $value['children']);
                }
                config(["admin.navigation.{$key}" => $existingValue]);
            } else {
                // Add new
                config(["admin.navigation.{$key}" => $value]);
            }
        }
    }

    /**
     * Recursively merge children array
     *
     * While preserving existing child element properties (text, icon, route, etc.),
     * merge new children. Add new keys as-is
     *
     * @param  array<string, mixed>  $existingChildren  Existing children array
     * @param  array<string, mixed>  $newChildren  children array to merge
     * @return array<string, mixed> Merge result
     */
    private function mergeChildren(array $existingChildren, array $newChildren): array
    {
        foreach ($newChildren as $childKey => $childValue) {
            if (isset($existingChildren[$childKey]) && is_array($existingChildren[$childKey]) && is_array($childValue)) {
                // If existing child elements exist, recursively merge children and overwrite other properties
                if (isset($childValue['children']) && is_array($childValue['children'])) {
                    $existingGrandchildren = $existingChildren[$childKey]['children'] ?? [];
                    $existingChildren[$childKey]['children'] = $this->mergeChildren($existingGrandchildren, $childValue['children']);
                }

                // Overwrite if properties other than children are specified
                foreach ($childValue as $prop => $propValue) {
                    if ($prop !== 'children') {
                        $existingChildren[$childKey][$prop] = $propValue;
                    }
                }
            } else {
                // Add new child elements as-is
                $existingChildren[$childKey] = $childValue;
            }
        }

        return $existingChildren;
    }

    /**
     * Insert element before or after the specified key
     *
     * @param  string  $configKey  Key to store in config()
     * @param  string  $insertKey  Key to insert
     * @param  array<string, mixed>  $insertValue  Data to insert
     * @param  string  $targetKey  Before or after which key to insert
     * @param  string  $position  'before' or 'after'
     */
    private function insertOrdered(string $configKey, string $insertKey, array $insertValue, string $targetKey, string $position = 'before'): void
    {
        unset($insertValue['_insert_before'], $insertValue['_insert_after']);

        $existingConfig = config($configKey, []);

        $newConfig = [];
        $inserted = false;

        foreach ($existingConfig as $key => $value) {
            if ($position === 'before' && $key === $targetKey) {
                $newConfig[$insertKey] = $insertValue;
                $inserted = true;
            }

            $newConfig[$key] = $value;

            if ($position === 'after' && $key === $targetKey) {
                $newConfig[$insertKey] = $insertValue;
                $inserted = true;
            }
        }

        // Add at the end if target key does not exist
        if (! $inserted) {
            $newConfig[$insertKey] = $insertValue;
        }

        config([$configKey => $newConfig]);
    }
}
