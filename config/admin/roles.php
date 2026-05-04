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

return [
    'roles' => [
        // Super Administrator
        'super_admin' => 'admin.roles.super_admin',
        // Administrator
        'admin' => 'admin.roles.admin',
        // Editor
        'editor' => 'admin.roles.editor',
        // Author
        'author' => 'admin.roles.author',
        // Contributor
        'contributor' => 'admin.roles.contributor',
        // Receptionist
        'receptionist' => 'admin.roles.receptionist',
        // Guest
        'guest' => 'admin.roles.guest',

    ],

    // Permission hierarchy
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
