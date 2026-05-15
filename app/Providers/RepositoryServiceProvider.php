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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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

use App\Contracts\Admin\AdminNavigationManagerInterface;
use App\Contracts\Repositories\ApiSettingRepositoryInterface;
use App\Contracts\Repositories\FrontSettingRepositoryInterface;
use App\Contracts\Repositories\MediaRepositoryInterface;
use App\Contracts\Repositories\MediaSettingRepositoryInterface;
use App\Contracts\Repositories\PluginRepositoryInterface;
use App\Contracts\Repositories\SecuritySettingRepositoryInterface;
use App\Contracts\Repositories\SiteSettingRepositoryInterface;
use App\Contracts\Repositories\ThemeRepositoryInterface;
use App\Repositories\ApiSettingRepository;
use App\Repositories\FrontSettingRepository;
use App\Repositories\MediaRepository;
use App\Repositories\MediaSettingRepository;
use App\Repositories\PluginRepository;
use App\Repositories\SecuritySettingRepository;
use App\Repositories\SiteSettingRepository;
use App\Repositories\ThemeRepository;
use App\Services\Admin\AdminNavigationManager;
use Illuminate\Support\ServiceProvider;

/**
 * Repository Service Provider
 *
 * Manages dependency injection for repository pattern
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Binding for SiteSetting repository
        $this->app->bind(
            SiteSettingRepositoryInterface::class,
            SiteSettingRepository::class
        );

        // Binding for SecuritySetting repository
        $this->app->bind(
            SecuritySettingRepositoryInterface::class,
            SecuritySettingRepository::class
        );

        // Binding for MediaSetting repository
        $this->app->bind(
            MediaSettingRepositoryInterface::class,
            MediaSettingRepository::class
        );

        // Binding for FrontSetting repository
        $this->app->bind(
            FrontSettingRepositoryInterface::class,
            FrontSettingRepository::class
        );

        // Binding for Media repository
        $this->app->bind(
            MediaRepositoryInterface::class,
            MediaRepository::class
        );

        // Binding for ApiSetting repository
        $this->app->bind(
            ApiSettingRepositoryInterface::class,
            ApiSettingRepository::class
        );

        // Binding for Plugin repository
        $this->app->bind(
            PluginRepositoryInterface::class,
            PluginRepository::class
        );

        // Binding for Theme repository
        $this->app->bind(
            ThemeRepositoryInterface::class,
            ThemeRepository::class
        );

        // Binding for AdminNavigationManager
        $this->app->bind(
            AdminNavigationManagerInterface::class,
            AdminNavigationManager::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
