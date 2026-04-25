<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

return [
    'roles' => [
        // 特権管理者
        'super_admin' => 'admin.roles.super_admin',
        // 管理者
        'admin' => 'admin.roles.admin',
        // 編集者
        'editor' => 'admin.roles.editor',
        // 投稿者
        'author' => 'admin.roles.author',
        // 寄稿者
        'contributor' => 'admin.roles.contributor',
        // 受付
        'receptionist' => 'admin.roles.receptionist',
        // ゲスト
        'guest' => 'admin.roles.guest',

    ],

    // 権限の階層
    'roles_hierarchy' => [
        '1' => ['1'],                   // super_admin
        '2' => ['1', '2'],               // admin
        '3' => ['1', '2', '3'],          // editor
        '4' => ['1', '2', '3', '4'],     // author
        '5' => ['1', '2', '3', '4', '5'], // contributor
        '6' => ['1', '2', '3', '4', '5', '6'], // receptionist
        '7' => ['1', '2', '3', '4', '5', '6', '7'], // guest
    ],
];
