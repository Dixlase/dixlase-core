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

namespace App\Contracts\Repositories;

use Illuminate\Database\Eloquent\Model;

/**
 * Settings repository base interface
 *
 * Defines common methods that all settings repositories should implement.
 */
interface SettingRepositoryInterface
{
    /**
     * Get all settings
     *
     * @return array<string, mixed>
     */
    public function all(): array;

    /**
     * Get value for a specific key
     *
     * @param  string  $name  Setting name
     * @param  mixed  $default  Default value
     */
    public function get(string $name, mixed $default = null): mixed;

    /**
     * Get values for multiple keys at once
     *
     * @param  array<string>  $names  Array of setting names
     * @param  mixed  $default  Default value
     * @return array<string, mixed>
     */
    public function getMultiple(array $names, mixed $default = null): array;

    /**
     * Save setting value
     *
     * @param  string  $name  Setting name
     * @param  mixed  $value  Setting value
     */
    public function set(string $name, mixed $value): Model;

    /**
     * Save multiple setting values at once
     *
     * @param  array<string, mixed>  $settings  Settings array
     */
    public function setMultiple(array $settings): bool;

    /**
     * Check if setting exists
     *
     * @param  string  $name  Setting name
     */
    public function has(string $name): bool;

    /**
     * Delete setting
     *
     * @param  string  $name  Setting name
     */
    public function delete(string $name): bool;

    /**
     * Clear cache
     *
     * @param  string|null  $name  Specify if clearing only a specific key
     */
    public function clearCache(?string $name = null): void;

    /**
     * Clear all cache
     */
    public function clearAllCache(): void;
}
