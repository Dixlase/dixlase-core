<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

use App\Session\GuardAwareDatabaseSessionHandler;
use Illuminate\Support\ServiceProvider;

class SessionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
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
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // セッションドライバーを拡張
        $this->app['session']->extend('guard-aware-database', function ($app) {
            return $app['session.guard-aware'];
        });
    }
}
