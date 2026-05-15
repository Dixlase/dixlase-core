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

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * @internal For Core use only. Do not reference from plugins/themes
 *
 * Abstract base class for settings repositories
 *
 * Provides common implementation for all settings repositories.
 */
abstract class AbstractSettingRepository
{
    /**
     * Cache key prefix
     */
    protected string $cachePrefix;

    /**
     * Cache key for all settings
     */
    protected string $cacheAllKey;

    /**
     * Cache expiration time (minutes)
     */
    protected int $cacheTtl = 10;

    /**
     * Settings key column name ('key' or 'name')
     */
    protected string $keyColumn = 'name';

    /**
     * Get the Eloquent model class name
     */
    abstract protected function getModelClass(): string;

    /**
     * Transform value before saving (can be overridden)
     */
    protected function transformValueForStorage(mixed $value): mixed
    {
        return $value;
    }

    /**
     * Transform value after retrieval (can be overridden)
     */
    protected function transformValueFromStorage(mixed $value): mixed
    {
        return $value;
    }

    /**
     * Get all settings
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return Cache::remember(
            $this->cacheAllKey,
            now()->addMinutes($this->cacheTtl),
            function () {
                $modelClass = $this->getModelClass();
                $settings = $modelClass::pluck('value', $this->keyColumn)->toArray();

                // Transform value
                return array_map(
                    fn ($value) => $this->transformValueFromStorage($value),
                    $settings
                );
            }
        );
    }

    /**
     * Get value for a specific key
     *
     * @param  string  $name  Setting name
     * @param  mixed  $default  Default value
     */
    public function get(string $name, mixed $default = null): mixed
    {
        return Cache::remember(
            $this->cachePrefix.$name,
            now()->addMinutes($this->cacheTtl),
            function () use ($name, $default) {
                $modelClass = $this->getModelClass();
                $value = $modelClass::where($this->keyColumn, $name)->value('value');

                if ($value === null) {
                    return $default;
                }

                return $this->transformValueFromStorage($value);
            }
        );
    }

    /**
     * Get multiple key values in bulk
     *
     * @param  array<string>  $names  Array of setting names
     * @param  mixed  $default  Default value
     * @return array<string, mixed>
     */
    public function getMultiple(array $names, mixed $default = null): array
    {
        $settings = [];

        foreach ($names as $name) {
            $settings[$name] = $this->get($name, $default);
        }

        return $settings;
    }

    /**
     * Save setting value
     *
     * @param  string  $name  Setting name
     * @param  mixed  $value  Setting value
     */
    public function set(string $name, mixed $value): Model
    {
        $modelClass = $this->getModelClass();

        $transformedValue = $this->transformValueForStorage($value);

        $record = $modelClass::updateOrCreate(
            [$this->keyColumn => $name],
            ['value' => $transformedValue]
        );

        $this->clearCache($name);

        return $record;
    }

    /**
     * Save multiple setting values in bulk
     *
     * @param  array<string, mixed>  $settings  Settings array
     */
    public function setMultiple(array $settings): bool
    {
        try {
            foreach ($settings as $name => $value) {
                $this->set($name, $value);
            }

            $this->clearAllCache();

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to set multiple settings', [
                'repository' => static::class,
                'error' => $e->getMessage(),
                'settings' => array_keys($settings),
            ]);

            return false;
        }
    }

    /**
     * Check if settings exist
     *
     * @param  string  $name  Setting name
     */
    public function has(string $name): bool
    {
        $modelClass = $this->getModelClass();

        return $modelClass::where($this->keyColumn, $name)->exists();
    }

    /**
     * Delete settings
     *
     * @param  string  $name  Setting name
     */
    public function delete(string $name): bool
    {
        $modelClass = $this->getModelClass();
        $result = $modelClass::where($this->keyColumn, $name)->delete();

        if ($result) {
            $this->clearCache($name);
        }

        return (bool) $result;
    }

    /**
     * Clear cache
     *
     * @param  string|null  $name  Specify if clearing only specific keys
     */
    public function clearCache(?string $name = null): void
    {
        if ($name !== null) {
            Cache::forget($this->cachePrefix.$name);
        }

        Cache::forget($this->cacheAllKey);
    }

    /**
     * Clear all cache
     */
    public function clearAllCache(): void
    {
        $this->clearCache();
    }
}
