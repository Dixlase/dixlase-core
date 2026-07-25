<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

namespace App\Services;

use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;

/**
 * @internal For Core use only. Do not reference from plugins/themes
 */
class ThemeMigrationRepository extends DatabaseMigrationRepository
{
    protected $theme;

    public function __construct(ConnectionResolverInterface $resolver, $table, $theme = null)
    {
        parent::__construct($resolver, $table);
        $this->theme = $theme;
    }

    /**
     * Get executed migrations based on theme name
     */
    public function getRan($theme = null)
    {
        $theme = $theme ?? $this->theme; // Use instance variable if null

        return $this->table()
            ->where('theme', $theme)
            ->pluck('migration')
            ->all();
    }

    /**
     * Record migration
     */
    public function log($file, $batch, $theme = null)
    {
        $theme = $theme ?? $this->theme; // Use instance variable if theme was not explicitly passed

        $this->table()->insert([
            'migration' => $file,
            'theme' => $theme, // Add theme identifier
            'batch' => $batch,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Highest batch number recorded for a theme, or 0 when this theme has
     * no migrations applied yet. See PluginMigrationRepository for the
     * rollback-side rationale — same reasoning for themes.
     */
    public function getLastBatchNumber($theme = null)
    {
        $theme = $theme ?? $this->theme;

        return (int) ($this->table()
            ->where('theme', $theme)
            ->max('batch') ?? 0);
    }
}
