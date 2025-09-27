<?php

/**
 * This file is part of Dixlase.
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

use Illuminate\Console\Command;
use App\Helpers\PluginGitignoreHelper;

class PluginGitignoreCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plugin:gitignore {action} {plugin?}
                            {--list : List all excluded plugins}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage plugin .gitignore exclusions';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');
        $plugin = $this->argument('plugin');

        switch ($action) {
            case 'add':
                if (!$plugin) {
                    $this->error('Plugin name is required for add action');
                    return 1;
                }
                
                if (PluginGitignoreHelper::addPlugin($plugin)) {
                    $this->info("Plugin '{$plugin}' added to .gitignore exclusions");
                } else {
                    $this->error("Failed to add plugin '{$plugin}' to .gitignore exclusions");
                    return 1;
                }
                break;

            case 'remove':
                if (!$plugin) {
                    $this->error('Plugin name is required for remove action');
                    return 1;
                }
                
                if (PluginGitignoreHelper::removePlugin($plugin)) {
                    $this->info("Plugin '{$plugin}' removed from .gitignore exclusions");
                } else {
                    $this->error("Failed to remove plugin '{$plugin}' from .gitignore exclusions");
                    return 1;
                }
                break;

            case 'list':
                $plugins = PluginGitignoreHelper::getExcludedPlugins();
                
                if (empty($plugins)) {
                    $this->info('No plugins are currently excluded from .gitignore');
                } else {
                    $this->info('Excluded plugins:');
                    foreach ($plugins as $plugin) {
                        $this->line("  - {$plugin}");
                    }
                }
                break;

            default:
                $this->error("Unknown action: {$action}");
                $this->info('Available actions: add, remove, list');
                return 1;
        }

        return 0;
    }
}
