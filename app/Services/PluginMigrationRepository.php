<?php

/**
 * This file is part of MySoftware.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
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

use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\ConnectionResolverInterface;

class PluginMigrationRepository extends DatabaseMigrationRepository
{
    protected $plugin;

    public function __construct(ConnectionResolverInterface $resolver, $table, $plugin = null)
    {
        parent::__construct($resolver, $table);
        $this->plugin = $plugin;
    }

    /**
     * プラグイン名に基づいて実行済みマイグレーションを取得
     */
    public function getRan($plugin = null)
    {
        if ($plugin) {
            return $this->table()
                ->where('plugin', $plugin)
                ->pluck('migration')
                ->all();
        }

        return parent::getRan();
    }

    /**
     * マイグレーションを記録
     */
    public function log($file, $batch, $plugin = null)
    {
        $this->table()->insert([
            'migration' => $file,
            'batch' => $batch,
            'plugin' => $plugin,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
