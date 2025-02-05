<?php

/**
 * This file is part of MySoftware.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
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
use App\Policies\AdminPolicy;
use Illuminate\Support\Facades\Gate;

class AdminServiceProvider extends ServiceProvider
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

        // Super Manager権限
        Gate::define('super_manager', [AdminPolicy::class, 'superManager']);

        //  Manager権限
        Gate::define('manager', [AdminPolicy::class, 'manager']);

        // Editor権限
        Gate::define('editor', [AdminPolicy::class, 'editor']);

        // Receptionist権限
        Gate::define('receptionist', [AdminPolicy::class, 'receptionist']);

        // Viewer権限
        Gate::define('viewer', [AdminPolicy::class, 'viewer']);
    }
}
