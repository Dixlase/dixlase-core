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
use App\Providers\PluginServiceProvider;
use App\Services\PluginMigrator;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PluginUninstall extends Command
{
    use PluginManagementTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:plugin:uninstall {pluginName}
                            {--rollback : Rollback database migrations}
                            {--force : Force uninstall even if plugin is enabled}';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->description = __('admin/command.plugin_uninstall.description');
    }

    /**
     * Initialize before command execution
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        // Get the plugin name to uninstall
        $pluginName = $input->getArgument('pluginName');
        if ($pluginName) {
            // Set flag in both environment variable and config
            putenv("PLUGIN_UNINSTALLING={$pluginName}");
            config(['app.plugin_uninstalling' => $pluginName]);
            $this->info("DEBUG: Early flag set for plugin: {$pluginName}");
            $this->info('DEBUG: ENV variable: '.getenv('PLUGIN_UNINSTALLING'));
        }
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pluginName = $this->argument('pluginName');
        $plugin = DB::table('plugins')->where('name', $pluginName)->first();

        if (! $plugin) {
            $this->error(__('admin/command.plugin_uninstall.not_found', ['pluginName' => $pluginName]));

            return 1;
        }

        // Check enabled state
        if (! is_null($plugin->enabled_at) && ! $this->option('force')) {
            $this->error(__('admin/command.plugin_uninstall.still_enabled', ['pluginName' => $pluginName]));
            $this->warn(__('admin/command.plugin_uninstall.disable_first'));

            return 1;
        }

        $pluginPath = base_path('plugins/'.$plugin->directory);

        // Confirm uninstallation (only when --no-interaction option is not present)
        if (! $this->option('no-interaction')) {
            $this->warn(__('admin/command.plugin_uninstall.confirm', ['pluginName' => $pluginName]));
            $answer = $this->ask(__('console/commands/plugin_uninstall.please_enter_yes_no'));

            if (! in_array(strtolower($answer), ['yes', 'y'])) {
                $this->info(__('admin/command.plugin_uninstall.cancelled'));

                return 0;
            }
        }

        // Set uninstalling flag (prevents ServiceProvider from loading)
        config(['app.plugin_uninstalling' => $pluginName]);

        // Execute disable only when --force option is specified
        if (! is_null($plugin->enabled_at) && $this->option('force')) {
            $this->warn(__('admin/command.plugin_uninstall.force_disabling', ['pluginName' => $pluginName]));
            $this->disablePlugin($pluginName);
            $plugin = DB::table('plugins')->where('name', $pluginName)->first();
        }
        // Rollback migrations
        if ($this->option('rollback')) {
            $this->info(__('admin/command.plugin_uninstall.rollback_running'));
            $migrator = new PluginMigrator(app(Filesystem::class), app(ConnectionResolverInterface::class), 'plugin_migrations', $plugin->slug);
            // Set step to a large value to rollback all migrations
            $migrator->rollback($plugin->directory, ['step' => 999]);
        } elseif (! $this->option('no-interaction') && $this->confirm(__('admin/command.plugin_uninstall.rollback_confirm', ['pluginName' => $pluginName]), false)) {
            $this->info(__('admin/command.plugin_uninstall.rollback_running'));
            $migrator = new PluginMigrator(app(Filesystem::class), app(ConnectionResolverInterface::class), 'plugin_migrations', $plugin->slug);
            // Set step to a large value to rollback all migrations
            $migrator->rollback($plugin->directory, ['step' => 999]);
        } else {
            $this->info(__('admin/command.plugin_uninstall.rollback_skipped'));
        }

        // Do not delete directory (use plugin:delete command)
        $this->info(__('admin/command.plugin_uninstall.files_preserved'));

        // Delete plugin from database
        DB::table('plugins')->where('name', $pluginName)->delete();
        $this->info(__('admin/command.plugin_uninstall.database_removed', ['pluginName' => $pluginName]));

        // Note: Updating composer.local.json and .git/info/exclude
        // is done during plugin deletion (plugin:delete), so not needed here

        // Clear uninstalling flag
        putenv('PLUGIN_UNINSTALLING');
        config(['app.plugin_uninstalling' => null]);

        // Clear enabled plugins cache
        PluginServiceProvider::clearEnabledPluginsCache();

        $this->info(__('admin/command.plugin_uninstall.completed', ['pluginName' => $pluginName]));
        $this->info(__('admin/command.plugin_uninstall.delete_hint'));

        return 0;
    }
}
