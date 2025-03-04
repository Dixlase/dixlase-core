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

        Session::extend('database', function ($app) {
            Log::info('セッション2 - Session拡張開始');
            Log::info('セッションテーブル' . config('session.table'),);
            $connection = $app->make('db')->connection(config('session.connection'));

            return new DatabaseSessionHandler(
                $connection,
                config('session.table'),
                config('session.lifetime'),
                $app
            );
        });
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

        // **セッションが開始されているかチェック**
        /*
        if (!session()->isStarted()) {
            return;
        }

        // **ログインしているかチェック**
        if (Auth::guard('member')->check()) {
            // **members_sessions を使用**
            config(['session.table' => 'members_sessions']);
            Log::info('Session switched to members_sessions');
        } else {
            // **未ログイン時は通常の sessions を使用**
            config(['session.table' => 'sessions']);
            Log::info('Session switched to sessions');
        }
        */
    }
}
