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

            // Set table for member guard
            $handler->setGuardTable('member', 'members_sessions');

            // Set table for user guard (can be added from plugins)
            // Added from plugin ServiceProvider

            return $handler;
        });

        // Extend session driver in register()
        // Allow session access from other ServiceProviders' boot() methods without depending on boot order
        // (prevents "Driver [guard-aware-database] not supported" error
        // when accessing session in AppServiceProvider, etc.)
        $this->app->resolving('session', function ($manager) {
            $manager->extend('guard-aware-database', function ($app) {
                return $app['session.guard-aware'];
            });
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Do nothing (already extended in register())
    }
}
