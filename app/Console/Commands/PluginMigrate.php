<?php

/**
 * This file is part of MySoftware.
 *
 * Copyright (C) 2025 exc-D inc.
 * https://exc-d.com
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

namespace App\Console\Commands;

use App\Services\PluginMigrator;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;
use App\Console\Traits\PluginManagementTrait;

class PluginMigrate extends Command
{

    use PluginManagementTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plugin:migrate
                            {plugin : The name of the plugin (e.g. EventsPlugin)}
                            {--pretend : Dump the SQL queries that would be run}
                            {--step= : Number of migrations to run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the migrations for a specific plugin';

    protected PluginMigrator $pluginMigrator;

    public function __construct(PluginMigrator $pluginMigrator)
    {
        parent::__construct();
        $this->pluginMigrator = $pluginMigrator;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $plugin = $this->argument('plugin');
        $force = $this->option('force');

        // オプションの処理
        $options = [
            'pretend' => $this->option('pretend'),
            'step' => $this->option('step') ? (int) $this->option('step') : 1, // デフォルト値を 1 に設定
        ];

        // プロセスオプションの共通処理
        $options = $this->processOptions($options);

        // プラグインディレクトリの存在確認
        if (!$this->pluginExists($plugin)) {
            $this->error("Plugin [{$plugin}] does not exist.");
            return Command::FAILURE;
        }

        // マイグレーションディレクトリの存在確認
        if (!$this->migrationPathExists($plugin)) {
            $this->error("Migration directory does not exist for plugin [{$plugin}].");
            return Command::FAILURE;
        }

        // マイグレーション実行
        $this->info("Running migrations for plugin [{$plugin}]...");

        try {
            $migrated = $this->pluginMigrator->migrate($plugin, null, $options);

            if (empty($migrated)) {
                $this->info("No migrations to run for plugin [{$plugin}].");
            } else {
                foreach ($migrated as $file) {
                    $this->info("Migrated: " . $file);
                }
                $this->info("Migrations for plugin [{$plugin}] completed successfully.");
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error during migration: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
