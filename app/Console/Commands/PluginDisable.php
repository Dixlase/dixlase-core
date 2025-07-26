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

class PluginDisable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plugin:disable {pluginName : The name of the plugin to disable}';

   
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->description = __('command.plugin_disable.description');
    }

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
        $plugin->update(['status' => 0]);

        // Remove symlink for assets
        $this->removePluginSymlink($plugin->directory);

        $this->info(__('command.make_plugin.disabled', ['pluginName' => $pluginName]));
        return 0;
    }

    /**
     * Remove symlink for plugin assets
     *
     * @param string $pluginDirName
     * @return void
     */
    protected function removePluginSymlink(string $pluginDirName)
    {
        $this->call('plugin:symlink', [
            'action' => 'remove',
            'plugin' => $pluginDirName
        ]);
    }
}
