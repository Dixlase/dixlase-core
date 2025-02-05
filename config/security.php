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

return [
    // 管理画面のURL
    'admin_url' => env('ADMIN_URL', 'admin'),
    // 管理画面へのアクセスを許可するIPアドレス
    'allowed_admin_ips' => [
        //'127.0.0.1', // 例: ローカルIP
        //'192.168.1.10',
        '10.5.1.148',
        '0.0.0.0'
    ],
    // 管理画面へのアクセスを拒否するIPアドレス
    'blocked_admin_ips' => [
        //'123.456.789.0', // 例: 拒否するIP
    ],

    // フロントエンドへのアクセスを許可するIPアドレス
    'allowed_frontend_ips' => [
        // 例: 許可するIP
    ],
    // フロントエンドへのアクセスを拒否するIPアドレス
    'blocked_frontend_ips' => [
        // 例: 拒否するIP
    ],

    // SSLを強制するかどうか
    'force_ssl' => env('FORCE_SSL', false),


];
