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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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
use Illuminate\Support\Facades\Artisan;

class PluginSeed extends Command
{
    use PluginManagementTrait;

    /**
     * Artisan command signature
     *  - {plugin} : plugin name
     *  - --class : Seeder class name to execute (default: DatabaseSeeder)
     */
    protected $signature = 'dls:plugin:seed
                            {plugin : The name of the plugin (e.g. EventsPlugin)}
                            {--class=DatabaseSeeder : The seeder class name to run}
                            {--force : Force the operation to run in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the database seeds for a specific plugin';

    protected PluginMigrator $pluginMigrator;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $plugin = $this->argument('plugin');
        $force = $this->option('force');
        $seederClassName = $this->option('class'); // Default is 'DatabaseSeeder'

        // 1. Check if plugin directory exists
        if (! $this->pluginExists($plugin)) {
            $this->error("Plugin [{$plugin}] does not exist.");

            return Command::FAILURE;
        }

        // 2. Verify seeder class
        $fullSeederClass = "Plugins\\{$plugin}\\Database\\Seeders\\{$seederClassName}";
        if (! class_exists($fullSeederClass)) {
            $this->error("Seeder class [{$fullSeederClass}] not found.");

            return Command::FAILURE;
        }

        // 3. Execute seeder
        $this->info("Seeding [{$fullSeederClass}]...");

        try {
            Artisan::call('db:seed', [
                '--class' => $fullSeederClass,
                '--force' => true, // Run without confirmation even in production
            ]);

            $this->info(Artisan::output()); // Display seeder execution result
            $this->info("Seeding for plugin [{$plugin}] completed successfully.");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error executing seeder: '.$e->getMessage());

            return Command::FAILURE;
        }

        $this->info('Seeding cancelled.');

        return Command::FAILURE;
    }
}
