<?php

namespace App\Providers;

use Config;
use Illuminate\Support\ServiceProvider;
use URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    /*
    public function boot()
    {
        URL::forceScheme('https');
    }
    */

    public function boot()
    {
        //$this->app['request']->server->set('HTTPS', true);
        //URL::forceRootUrl(Config::get('app.url'));// ルートURLを設定
        //$url->forceScheme('https');
    }
}
