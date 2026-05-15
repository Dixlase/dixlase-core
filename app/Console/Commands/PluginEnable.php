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

use App\Models\Plugin;
use App\Providers\PluginServiceProvider;
use Illuminate\Console\Command;

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

        if (! $plugin) {
            $this->error(__('admin/command.make_plugin.not_found', ['pluginName' => $pluginName]));

            return 1;
        }

        // Update plugin status
        $plugin->update(['enabled_at' => now()]);

        // Create symlink for assets
        $this->createPluginSymlink($plugin->directory);

        // Clear enabled plugins cache
        PluginServiceProvider::clearEnabledPluginsCache();

        $this->info(__('admin/command.make_plugin.enabled', ['pluginName' => $pluginName]));

        return 0;
    }

    /**
     * Create symlink for plugin assets
     *
     * @return void
     */
    protected function createPluginSymlink(string $pluginDirName)
    {
        $this->call('dls:plugin:symlink', [
            'action' => 'create',
            'plugin' => $pluginDirName,
        ]);
    }
}
