<?php

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
