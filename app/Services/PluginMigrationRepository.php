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

namespace App\Services;

use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;

class PluginMigrationRepository extends DatabaseMigrationRepository
{
    protected $plugin;

    public function __construct(ConnectionResolverInterface $resolver, $table, $plugin = null)
    {
        parent::__construct($resolver, $table);
        $this->plugin = $plugin;
    }

    /**
     * Bind this repository to a specific plugin slug.
     *
     * PluginMigrator uses this to point the repository at the plugin
     * whose migrations are about to be run / rolled back, so that
     * getRan() returns the correct set of already-applied migrations
     * for Laravel's Migrator to diff against.
     */
    public function setPlugin(?string $plugin): void
    {
        $this->plugin = $plugin;
    }

    /**
     * Get executed migrations based on plugin name
     */
    public function getRan($plugin = null)
    {
        $plugin = $plugin ?? $this->plugin; // Use instance variable if null

        return $this->table()
            ->where('plugin', $plugin)
            ->pluck('migration')
            ->all();
    }

    /**
     * Record migration
     */
    public function log($file, $batch, $plugin = null)
    {
        $plugin = $plugin ?? $this->plugin; // Use instance variable if plugin was not explicitly passed

        $this->table()->insert([
            'migration' => $file,
            'plugin' => $plugin, // Add plugin identifier
            'batch' => $batch,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Highest batch number recorded for a plugin, or 0 when this plugin has
     * no migrations applied yet.
     *
     * Overrides the parent's global max(batch) so that "the latest batch for
     * plugin X" cannot be conflated with "the latest batch for any plugin".
     * dls:{plugin,theme}:rollback uses this to record the pre-update batch
     * on the backup sidecar and to compute the exact step count to reverse
     * on rollback, so a schema-neutral update's rollback does not
     * over-reverse an unrelated plugin's prior batch.
     */
    public function getLastBatchNumber($plugin = null)
    {
        $plugin = $plugin ?? $this->plugin;

        return (int) ($this->table()
            ->where('plugin', $plugin)
            ->max('batch') ?? 0);
    }
}
