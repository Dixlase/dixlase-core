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
| 管理画面ナビゲーション設定
|--------------------------------------------------------------------------
|
| 管理画面のサイドバーに表示されるメニュー項目を定義します。
|
*/

return [
    'legal' => [
        '_insert_after' => 'settings',
        'text' => 'dixlase-legal::admin/navigation.legal.text',
        'icon' => 'fas fa-fw fa-balance-scale',
        'can' => 'admin',
        'children' => [
            'legal-pages' => [
                'text' => 'dixlase-legal::admin/navigation.legal.legal-pages',
                'route' => 'dixlase-legal::admin.legal-pages.index',
                'icon' => 'fas fa-fw fa-link',
                'can' => 'admin',
            ],
            'legal-contents' => [
                'text' => 'dixlase-legal::admin/navigation.legal.legal-contents',
                'route' => 'dixlase-legal::admin.legal-pages.contents.index',
                'icon' => 'fas fa-fw fa-file-alt',
                'can' => 'admin',
            ],
            'legal-settings' => [
                'text' => 'dixlase-legal::admin/navigation.legal.legal-settings',
                'route' => 'dixlase-legal::admin.legal-pages.settings.index',
                'icon' => 'fas fa-fw fa-cog',
                'can' => 'admin',
            ],
        ],
    ],
];
