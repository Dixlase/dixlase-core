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
    'heading' => 'IPアクセス制御',
    'title' => 'IPアクセス制御設定',
    'description' => 'システムへのIPアドレスベースのアクセス制御を設定します。',
    'admin_access_control' => '管理画面IPアクセス制御設定',
    'admin_url' => '管理画面URL',
    'enable_allowed_admin_ips' => '特定のIPアドレスのみアクセスを許可',
    'allowed_admin_ips' => '許可IPアドレス',
    'allowed_admin_ips_list' => '許可IPアドレスリスト',
    'enable_blocked_admin_ips' => '特定のIPアドレスをブロック',
    'blocked_admin_ips' => 'ブロックIPアドレス',
    'blocked_admin_ips_list' => 'ブロックIPアドレスリスト',
    'admin_ip_help' => 'IPアドレスまたはCIDR記法で入力してください。1行に1つずつ記入してください。例: 192.168.1.1 または 192.168.1.0/24',
    'front_access_control' => 'フロントIPアクセス制御設定',
    'enable_allowed_front_ips' => '特定のIPアドレスのみアクセスを許可',
    'allowed_front_ips' => '許可IPアドレス',
    'allowed_front_ips_list' => '許可IPアドレスリスト',
    'enable_blocked_front_ips' => '特定のIPアドレスをブロック',
    'blocked_front_ips' => 'ブロックIPアドレス',
    'blocked_front_ips_list' => 'ブロックIPアドレスリスト',
    'front_ip_help' => 'IPアドレスまたはCIDR記法で入力してください。1行に1つずつ記入してください。例: 192.168.1.1 または 192.168.1.0/24',
    'ip_list_placeholder' => '192.168.1.1
192.168.1.0/24
10.0.0.0/8',
    'settings_updated' => 'IPアクセス制御設定が更新されました。',
];
