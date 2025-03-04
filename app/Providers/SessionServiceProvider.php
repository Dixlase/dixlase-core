<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\ServiceProvider;
use App\Session\DatabaseSessionHandler;
use Illuminate\Support\Facades\Log;
use App\Models\SecuritySetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SessionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //Log::info('セッション1 - register開始');

        // Laravel のデフォルトの session handler を拡張

        /*

        Session::extend('database', function ($app) {
            //Log::info('セッション2 - Session拡張開始');
            $connection = $app->make('db')->connection(config('session.connection'));

            return new DatabaseSessionHandler(
                $connection,
                config('session.table'),
                config('session.lifetime'),
                $app
            );
        });
        */



        //Log::info('セッション3 - register完了');
    }

    /**
     * Bootstrap services.
     */
    public function boot()
    {
        //セッションIDを取得
        //$sessionId = session()->getId();

        // 管理画面のURLを取得
        //$adminUrl = SecuritySetting::get('admin_url', config('security.admin_url'));
    }
}
