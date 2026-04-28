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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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

use App\Models\SecuritySetting;
use App\Policies\AdminPolicy;
use App\Services\AdminLoginLockoutService;
use App\Services\TwoFa\TwoFaService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

class AdminServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(TwoFactorAuthenticationProvider::class, function ($app) {
            return $app->make(TwoFaService::class, [
                'settingModelClass' => SecuritySetting::class,
                'context' => 'admin',
            ]);
        });

        $this->app->singleton(AdminLoginLockoutService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Super Admin権限

        Gate::define('super_admin', [AdminPolicy::class, 'superAdmin']);

        //  Admin権限
        Gate::define('admin', [AdminPolicy::class, 'admin']);

        // Editor権限
        Gate::define('editor', [AdminPolicy::class, 'editor']);

        // Author権限
        Gate::define('author', [AdminPolicy::class, 'author']);

        // Contributor権限
        Gate::define('contributor', [AdminPolicy::class, 'contributor']);
    }
}
