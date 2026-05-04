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

class PluginMigrateRefresh extends Command
{
    use PluginManagementTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:plugin:migrate:refresh
                            {plugin : The name of the plugin (e.g. EventsPlugin)}
                            {--step= : Number of migrations to rollback}
                            {--force : Force the operation to run when in production}';

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
            'force' => $force,
        ];

        // Common processing for process options
        $options = $this->processOptions($options);

        // Check plugin directory existence
        if (! $this->pluginExists($plugin)) {
            $this->error("Plugin [{$plugin}] does not exist.");

            return Command::FAILURE;
        }

        if (! $this->migrationPathExists($plugin)) {
            $this->error("Migration directory does not exist for plugin [{$plugin}].");

            return Command::FAILURE;
        }

        // Migration refresh processing
        $this->info("Rolling back all migrations for plugin [{$plugin}]...");

        try {
            $this->pluginMigrator->rollback($plugin, $options);

            $this->info("Re-running migrations for plugin [{$plugin}]...");
            $migrated = $this->pluginMigrator->migrate($plugin, null, $options);

            if (empty($migrated)) {
                $this->info("No migrations to run for plugin [{$plugin}].");
            } else {
                foreach ($migrated as $file) {
                    $this->info('Migrated: '.$file);
                }
                $this->info("Migrations for plugin [{$plugin}] refreshed successfully.");
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error during refresh: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
