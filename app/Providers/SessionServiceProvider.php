<?php

namespace App\Providers;

use App\Session\GuardAwareDatabaseSessionHandler;
use Illuminate\Support\ServiceProvider;
use Illuminate\Session\SessionManager;

class SessionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->app->singleton('session.guard-aware', function ($app) {
            $connection = $app['db']->connection(config('session.connection'));
            $table = config('session.table');
            $lifetime = config('session.lifetime');

            $handler = new GuardAwareDatabaseSessionHandler(
                $connection,
                $table,
                $lifetime,
                $app
            );

            // メンバーガードのテーブルを設定
            $handler->setGuardTable('member', 'members_sessions');

            // ユーザーガードのテーブルを設定（プラグインから追加可能）
            // プラグインのServiceProviderから追加される

            return $handler;
        });

        // セッションドライバーを拡張
        $this->app['session']->extend('guard-aware-database', function ($app) {
            return $app['session.guard-aware'];
        });
    }
}
