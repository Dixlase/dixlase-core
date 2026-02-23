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

namespace App\Contracts;

use App\DTO\RouteSlug\RegisteredSlug;

/**
 * @api プラグイン/テーマから使用可能な安定APIです
 *
 * Route Slug Provider Interface
 *
 * プラグイン・テーマが管理するURLスラッグを提供するためのインターフェース。
 * このインターフェースを実装することで、トップレベルURLスラッグの
 * 重複チェックに参加できる。
 *
 * @example
 * class MyPluginRouteSlugProvider implements RouteSlugProvider
 * {
 *     public function getRouteSlugs(): array
 *     {
 *         $slug = $this->settingRepo->get('url_slug', 'my-plugin');
 *         return [
 *             new RegisteredSlug(
 *                 slug: $slug,
 *                 owner: 'my-plugin:url_slug',
 *                 label: 'my-plugin::admin.settings.url_slug',
 *             ),
 *         ];
 *     }
 * }
 */
interface RouteSlugProvider
{
    /**
     * 管理対象のルートスラッグ一覧を取得
     *
     * @return array<RegisteredSlug> 登録済みスラッグの配列
     */
    public function getRouteSlugs(): array;
}
