<?php

/**
 * This file is part of Dixlase Legal.
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

/*
|--------------------------------------------------------------------------
| プラグインのデフォルト権限設定
|--------------------------------------------------------------------------
|
| 各メニュー/機能に対するデフォルトの権限を定義します。
|
*/

use App\Enums\MemberRole;

return [
    'permissions' => [
        // 法務ページURL設定
        'legal.legal-pages.index' => [
            'access_roles' => MemberRole::ADMIN->value,
            'view_roles' => MemberRole::ADMIN->value,
        ],
        // 法務ページコンテンツ管理
        'legal.legal-contents.index' => [
            'access_roles' => MemberRole::ADMIN->value,
            'view_roles' => MemberRole::ADMIN->value,
        ],
        // 法務設定
        'legal.legal-settings.index' => [
            'access_roles' => MemberRole::ADMIN->value,
            'view_roles' => MemberRole::ADMIN->value,
        ],
    ],
];
