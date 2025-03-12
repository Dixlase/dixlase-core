<?php

/**
 * This file is part of MySoftware.
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

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\PluginMigrator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Database\ConnectionResolverInterface;

class PluginMigrationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // PluginMigratorのシングルトンインスタンスをバインド

        $this->app->singleton(PluginMigrator::class, function ($app) {
            return new PluginMigrator(
                new Filesystem(),
                $app['db'], // ConnectionResolverInterface
                'plugin_migrations' // マイグレーションテーブル名
            );
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
