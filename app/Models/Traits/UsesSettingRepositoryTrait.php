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

namespace App\Models\Traits;

/**
 * Trait for settings models
 *
 * Provides common static methods for settings models
 * These methods are kept for backward compatibility, but
 * it is recommended to use the Repository directly in new code
 *
 * @deprecated 静的メソッドは非推奨です。対応するRepositoryを使用してください。
 */
trait UsesSettingRepositoryTrait
{
    /**
     * Get the repository interface class name
     */
    abstract protected static function getRepositoryInterface(): string;

    /**
     * Get all settings
     *
     * @deprecated Repository::all() を使用してください
     *
     * @return array<string, mixed>
     */
    public static function getAllSettings(): array
    {
        return app(static::getRepositoryInterface())->all();
    }

    /**
     * Get settings value
     *
     * @deprecated Repository::get() を使用してください
     *
     * @param  string  $name  Setting name
     * @param  mixed  $default  Default value
     */
    public static function getValue(string $name, mixed $default = null): mixed
    {
        return app(static::getRepositoryInterface())->get($name, $default);
    }

    /**
     * Save settings value
     *
     * @deprecated Repository::set() を使用してください
     *
     * @param  string  $name  Setting name
     * @param  mixed  $value  Setting value
     */
    public static function setValue(string $name, mixed $value): \Illuminate\Database\Eloquent\Model
    {
        return app(static::getRepositoryInterface())->set($name, $value);
    }

    /**
     * Bulk save multiple settings
     *
     * @deprecated Repository::setMultiple() を使用してください
     *
     * @param  array<string, mixed>  $settings  設定の配列
     */
    public static function setMany(array $settings): void
    {
        app(static::getRepositoryInterface())->setMultiple($settings);
    }

    /**
     * SecuritySetting compatible: get() method
     *
     * @deprecated Repository::get() を使用してください
     *
     * @param  string  $key  Setting key
     * @param  mixed  $default  Default value
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return static::getValue($key, $default);
    }

    /**
     * SecuritySetting compatible: set() method
     *
     * @deprecated Repository::set() を使用してください
     *
     * @param  string  $key  Setting key
     * @param  mixed  $value  Setting value
     */
    public static function set(string $key, mixed $value): \Illuminate\Database\Eloquent\Model
    {
        return static::setValue($key, $value);
    }
}
