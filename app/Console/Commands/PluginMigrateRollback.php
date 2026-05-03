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

namespace App\Console\Commands;

use App\Console\Traits\PluginManagementTrait;
use App\Services\PluginMigrator;
use Illuminate\Console\Command;

class PluginMigrateRollback extends Command
{
    use PluginManagementTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:plugin:migrate:rollback
                            {plugin : The name of the plugin (e.g. EventsPlugin)}
                            {--force : Force the operation to run when in production}
                            {--step= : Number of migrations to rollback}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback the last migration batch for a specific plugin';

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
            'step' => $this->option('step') ? (int) $this->option('step') : 1, // デフォルト値を設定
            'force' => $force,
        ];

        // プロセスオプションの共通処理
        $options = $this->processOptions($options);

        // プラグインディレクトリの存在確認
        if (! $this->pluginExists($plugin)) {
            $this->error("Plugin [{$plugin}] does not exist.");

            return Command::FAILURE;
        }

        if (! $this->migrationPathExists($plugin)) {
            $this->error("Migration directory does not exist for plugin [{$plugin}].");

            return Command::FAILURE;
        }

        // マイグレーションのロールバック処理
        $this->info("Rolling back migrations for plugin [{$plugin}]...");

        try {
            $rolledBack = $this->pluginMigrator->rollback($plugin, $options);

            if (empty($rolledBack)) {
                $this->info("No migrations to rollback for plugin [{$plugin}].");
            } else {
                foreach ($rolledBack as $file) {
                    $this->info('Rolled back: '.$file);
                }
                $this->info("Rollback for plugin [{$plugin}] completed successfully.");
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error during rollback: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
