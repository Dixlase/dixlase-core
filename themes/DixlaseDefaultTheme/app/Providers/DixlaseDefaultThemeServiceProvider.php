<?php

/**
 * This file is part of Dixlase.
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

namespace Themes\DixlaseDefaultTheme\App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Helpers\AdminHelper;

class DixlaseDefaultThemeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge admin navigation config
        AdminHelper::mergeAdminNavigation(
            'DixlaseDefaultTheme',
            __DIR__ . '/../../config/admin.php'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../../routes/admin.php');
        
        // Load views
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'themes');
        
        // Load translations
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'dixlase-default-theme');
    }
}
