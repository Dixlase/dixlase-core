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

namespace App\Providers;

use App\Services\CoreMigrator;
use App\Services\PluginMigrator;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class PluginMigrationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Bind singleton instance of PluginMigrator

        $this->app->singleton(PluginMigrator::class, function ($app) {
            return new PluginMigrator(
                new Filesystem(),
                $app['db'], // ConnectionResolverInterface
                'plugin_migrations' // Migration table name
            );
        });

        $this->registerCoreMigrator();
    }

    /**
     * Swap Laravel's stock migrator for one that ignores plugin / theme
     * migration paths.
     *
     * Extension migrations belong to PluginMigrator / ThemeMigrator and
     * their own ledgers; handing them to the stock migrator makes a bare
     * `php artisan migrate` try to re-create tables the installer already
     * created (SQLSTATE 42S01). See CoreMigrator for the full rationale.
     *
     * This must be an extend() rather than a fresh singleton() binding:
     * `migrator` is registered by a deferred provider, so a plain
     * re-binding is clobbered when Laravel resolves and registers it.
     * Extenders survive that re-registration and are applied before the
     * afterResolving callbacks that loadMigrationsFrom() relies on, so
     * every extension path arrives at CoreMigrator regardless of the
     * order in which providers happen to boot.
     */
    protected function registerCoreMigrator(): void
    {
        $this->app->extend('migrator', function ($migrator, $app) {
            if ($migrator instanceof CoreMigrator) {
                return $migrator;
            }

            $coreMigrator = new CoreMigrator(
                $app['migration.repository'],
                $app['db'],
                $app['files'],
                $app['events'],
            );

            // Carry over anything registered before this extender ran, so
            // the swap cannot silently drop a legitimate core path.
            foreach ($migrator->paths() as $path) {
                $coreMigrator->path($path);
            }

            return $coreMigrator;
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
