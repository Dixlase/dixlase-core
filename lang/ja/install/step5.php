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
    'security_title' => 'セキュリティ設定',
    'security_header' => 'セキュリティ設定(任意)',
    'security_description' => '管理画面のURLやIP制限を設定します。<br>IP制限はインストール後に設定することも可能です。',

    'site_url' => 'フロントページURL',
    'admin_url' => '管理画面URL',
    'admin_url_security_note' => '本番環境では管理画面URLは「admin」以外の予想されにくいURLを設定することを推奨します。',
    'force_ssl' => 'SSL（HTTPS）を強制する',

    // IP制限
    'ip_restrictions' => 'IPアドレス制限',
    'enable_allowed_admin_ips' => '特定のIPアドレスのみ管理画面へのアクセスを許可',
    'enable_blocked_admin_ips' => '特定のIPアドレスを管理画面へのアクセス禁止',
    'enable_allowed_front_ips' => '特定のIPアドレスのみフロントへのアクセスを許可',
    'enable_blocked_front_ips' => '特定のIPアドレスをフロントへのアクセス禁止',
    'ip_note' => '複数のIPを入力する場合は改行で区切ってください。',
    'ip_address_format_instruction' => 'IPアドレスは1行に1つずつ入力してください。例:',
    'admin_panel_ip_restrictions' => '管理画面IP制限',
    'front_panel_ip_restrictions' => 'フロント画面IP制限',
];
