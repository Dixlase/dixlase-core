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

namespace App\Console\Commands;

use App\Console\Traits\PluginManagementTrait;
use App\Services\PluginMigrator;
use Illuminate\Console\Command;

class PluginMigrate extends Command
{
    use PluginManagementTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:plugin:migrate
                            {plugin : The name of the plugin (e.g. EventsPlugin)}
                            {--pretend : Dump the SQL queries that would be run}
                            {--step= : Number of migrations to run}
                            {--force : Force the operation to run when in production}';

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

        // Process options
        $options = [
            'pretend' => $this->option('pretend'),
            'step' => $this->option('step') ? (int) $this->option('step') : 1, // Set default value to 1
            'force' => $force,
        ];

        // Common processing for process options
        $options = $this->processOptions($options);

        // Check if plugin directory exists
        if (! $this->pluginExists($plugin)) {
            $this->error("Plugin [{$plugin}] does not exist.");

            return Command::FAILURE;
        }

        // Check if migration directory exists
        if (! $this->migrationPathExists($plugin)) {
            $this->error("Migration directory does not exist for plugin [{$plugin}].");

            return Command::FAILURE;
        }

        // Execute migration
        $this->info("Running migrations for plugin [{$plugin}]...");

        try {
            $migrated = $this->pluginMigrator->migrate($plugin, null, $options);

            if (empty($migrated)) {
                $this->info("No migrations to run for plugin [{$plugin}].");
            } else {
                foreach ($migrated as $file) {
                    $this->info('Migrated: '.$file);
                }
                $this->info("Migrations for plugin [{$plugin}] completed successfully.");
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error during migration: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
