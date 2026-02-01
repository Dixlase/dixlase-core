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

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\Repositories\BaseSettingRepositoryInterface;
use App\Repositories\BaseSettingRepository;
use App\Contracts\Repositories\SecuritySettingRepositoryInterface;
use App\Repositories\SecuritySettingRepository;
use App\Contracts\Repositories\MediaSettingRepositoryInterface;
use App\Repositories\MediaSettingRepository;
use App\Contracts\Repositories\FrontSettingRepositoryInterface;
use App\Repositories\FrontSettingRepository;
use App\Contracts\Repositories\MediaRepositoryInterface;
use App\Repositories\MediaRepository;
use App\Contracts\Repositories\ApiSettingRepositoryInterface;
use App\Repositories\ApiSettingRepository;

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
        // BaseSetting リポジトリのバインディング
        $this->app->bind(
            BaseSettingRepositoryInterface::class,
            BaseSettingRepository::class
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

        // 今後、他のリポジトリもここに追加
        // 例:
        // $this->app->bind(
        //     MemberRepositoryInterface::class,
        //     MemberRepository::class
        // );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
