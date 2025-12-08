<?php

/**
 * This file is part of Dixlase.
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

use Illuminate\Console\Command;
use App\Models\Plugin;
use Illuminate\Support\Facades\File;
use App\Providers\PluginServiceProvider;

class PluginEnable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:plugin:enable {pluginName : The name of the plugin to enable}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enable a plugin';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $pluginName = $this->argument('pluginName');

        // Find the plugin by name
        $plugin = Plugin::where('name', $pluginName)->first();

        if (!$plugin) {
            $this->error(__('command.make_plugin.not_found', ['pluginName' => $pluginName]));
            return 1;
        }

        // Update plugin status
        $plugin->update(['enabled_at' => now()]);

        // Create symlink for assets
        $this->createPluginSymlink($plugin->directory);

        // Clear enabled plugins cache
        PluginServiceProvider::clearEnabledPluginsCache();

        $this->info(__('command.make_plugin.enabled', ['pluginName' => $pluginName]));
        return 0;
    }

    /**
     * Create symlink for plugin assets
     *
     * @param string $pluginDirName
     * @return void
     */
    protected function createPluginSymlink(string $pluginDirName)
    {
        $this->call('dls:plugin:symlink', [
            'action' => 'create',
            'plugin' => $pluginDirName
        ]);
    }
}
