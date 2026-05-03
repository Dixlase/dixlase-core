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
 * リポジトリサービスプロバイダー
 *
 * リポジトリパターンの依存性注入を管理
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // SiteSetting リポジトリのバインディング
        $this->app->bind(
            SiteSettingRepositoryInterface::class,
            SiteSettingRepository::class
        );

        // SecuritySetting リポジトリのバインディング
        $this->app->bind(
            SecuritySettingRepositoryInterface::class,
            SecuritySettingRepository::class
        );

        // MediaSetting リポジトリのバインディング
        $this->app->bind(
            MediaSettingRepositoryInterface::class,
            MediaSettingRepository::class
        );

        // FrontSetting リポジトリのバインディング
        $this->app->bind(
            FrontSettingRepositoryInterface::class,
            FrontSettingRepository::class
        );

        // Media リポジトリのバインディング
        $this->app->bind(
            MediaRepositoryInterface::class,
            MediaRepository::class
        );

        // ApiSetting リポジトリのバインディング
        $this->app->bind(
            ApiSettingRepositoryInterface::class,
            ApiSettingRepository::class
        );

        // Plugin リポジトリのバインディング
        $this->app->bind(
            PluginRepositoryInterface::class,
            PluginRepository::class
        );

        // Theme リポジトリのバインディング
        $this->app->bind(
            ThemeRepositoryInterface::class,
            ThemeRepository::class
        );

        // AdminNavigationManager のバインディング
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
