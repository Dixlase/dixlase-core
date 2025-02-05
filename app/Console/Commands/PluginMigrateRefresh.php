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

namespace App\Console\Commands;

use App\Services\PluginMigrator;
use App\Console\Commands\Traits\HandlesOptions;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;

class PluginMigrateRefresh extends PluginMigrationCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plugin:migrate:refresh
                            {plugin : The name of the plugin (e.g. EventsPlugin)}
                            {--force : Force the operation to run when in production}
                            {--step= : Number of migrations to rollback}';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset and re-run all migrations for a specific plugin';

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
        $options = [
            'step' => $this->option('step'),
        ];

        // プロセスオプションの共通処理
        $options = $this->processOptions($options);

        // プラグインディレクトリの存在確認
        if (!$this->pluginExists($plugin)) {
            $this->error("Plugin [{$plugin}] does not exist.");
            return Command::FAILURE;
        }

        if (!$this->migrationPathExists($plugin)) {
            $this->error("Migration directory does not exist for plugin [{$plugin}].");
            return Command::FAILURE;
        }


        // 本番環境での実行確認
        if ($force || $this->confirmProduction('refresh')) {
            $success = $this->executeOperation(function () use ($plugin, $options) {
                // ロールバック
                $this->pluginMigrator->rollback($plugin, $options);

                // マイグレーション実行
                $migrated = $this->pluginMigrator->migrate($plugin, null, $options);

                if (empty($migrated)) {
                    $this->info("No migrations to run for plugin [{$plugin}].");
                } else {
                    foreach ($migrated as $migration => $note) {
                        $this->info($note);
                    }
                    $this->info("Migrations for plugin [{$plugin}] refreshed successfully.");
                }
            });

            if ($success) {
                return Command::SUCCESS;
            }

            return Command::FAILURE;
        }

        $this->info('Refresh cancelled.');
        return Command::FAILURE;
    }
}
