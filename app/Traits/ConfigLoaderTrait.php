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

namespace App\Traits;

/**
 * Settings file loading utility
 */
trait ConfigLoaderTrait
{
    /**
     * Load config file
     */
    private function loadConfigFiles($path)
    {
        $configs = [];

        if (is_dir($path)) {
            foreach (glob($path.'/*.php') as $file) {
                $key = basename($file, '.php');
                $configs[$key] = require $file;
            }
        }

        return $configs;
    }

    /**
     * Recursively traverse the entire config array and apply _insert_before / _insert_after within each array
     */
    public function reorderAllConfig(): void
    {
        // Get current full config
        $allConfig = config()->all();

        // Execute rearrangement process (already processed recursively)
        $reorderedConfig = $this->reorderConfigArray($allConfig);

        // Update for each top-level key
        foreach ($reorderedConfig as $key => $value) {
            config([$key => $value]);
        }
    }

    /**
     * Insert new element before the specified key
     */
    public function arrayInsertBeforeKey(array $array, string $targetKey, string $newKey, $newValue): array
    {
        $new = [];
        $inserted = false;
        foreach ($array as $k => $v) {
            if ($k === $targetKey) {
                $new[$newKey] = $newValue;
                $inserted = true;
            }
            $new[$k] = $v;
        }
        if (! $inserted) {
            $new[$newKey] = $newValue;
        }

        return $new;
    }

    /**
     * Insert new element after the specified key
     */
    public function arrayInsertAfterKey(array $array, string $targetKey, string $newKey, $newValue): array
    {
        $new = [];
        $inserted = false;
        foreach ($array as $k => $v) {
            $new[$k] = $v;
            if ($k === $targetKey) {
                $new[$newKey] = $newValue;
                $inserted = true;
            }
        }
        if (! $inserted) {
            $new[$newKey] = $newValue;
        }

        return $new;
    }

    /**
     * Recursively traverse the array and reorder according to _insert_before / _insert_after specifications within each array
     *
     * @param  array  $config  Array to be rearranged
     * @return array Rearranged array
     */
    public function reorderConfigArray(array $config): array
    {
        // First, recursively process child arrays
        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $config[$key] = $this->reorderConfigArray($value);
            }
        }

        // Rearrange at the current level if _insert_before/_insert_after exists
        $reordered = $config;
        foreach ($reordered as $key => $value) {
            if (is_array($value) && (isset($value['_insert_before']) || isset($value['_insert_after']))) {
                if (isset($value['_insert_before'])) {
                    $target = $value['_insert_before'];
                    unset($value['_insert_before']);
                    unset($reordered[$key]);
                    $reordered = $this->arrayInsertBeforeKey($reordered, $target, $key, $value);
                } elseif (isset($value['_insert_after'])) {
                    $target = $value['_insert_after'];
                    unset($value['_insert_after']);
                    unset($reordered[$key]);
                    $reordered = $this->arrayInsertAfterKey($reordered, $target, $key, $value);
                }
            }
        }

        return $reordered;
    }
}
