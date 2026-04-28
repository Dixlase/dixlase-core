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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
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

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThemeMigration extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'dls_theme_migrations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'theme',
        'migration',
        'batch',
    ];

    /**
     * Get the next batch number.
     *
     * @return int
     */
    public static function getNextBatchNumber(): int
    {
        return (int) static::max('batch') + 1;
    }

    /**
     * Get migrations for a specific theme.
     *
     * @param string $themeName
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getThemeMigrations(string $themeName)
    {
        return static::where('theme_name', $themeName)
            ->orderBy('batch')
            ->orderBy('migration')
            ->get();
    }

    /**
     * Check if a migration has been run for a theme.
     *
     * @param string $themeName
     * @param string $migration
     * @return bool
     */
    public static function hasRun(string $themeName, string $migration): bool
    {
        return static::where('theme_name', $themeName)
            ->where('migration', $migration)
            ->exists();
    }

    /**
     * Delete all migrations for a specific theme.
     *
     * @param string $themeName
     * @return int
     */
    public static function deleteThemeMigrations(string $themeName): int
    {
        return static::where('theme_name', $themeName)->delete();
    }
}
