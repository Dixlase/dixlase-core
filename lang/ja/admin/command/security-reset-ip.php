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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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
    'table_not_found' => 'security_settingsテーブルが見つかりません。',
    'usage' => '使用方法:',
    'option_show' => '現在のIP制限設定を表示',
    'option_disable_all' => '全てのIP制限を無効化',
    'option_add_ip' => '許可リストにIPを追加',
    'option_remove_blocked' => 'ブロックリストからIPを削除',
    'option_force' => '確認なしで実行',
    'current_settings' => '【管理画面IP制限設定】',
    'front_settings' => '【フロントIP制限設定】',
    'setting' => '設定項目',
    'value' => '値',
    'admin_allow_enabled' => '許可リスト有効',
    'admin_allowed_ips' => '許可IPリスト',
    'admin_block_enabled' => 'ブロックリスト有効',
    'admin_blocked_ips' => 'ブロックIPリスト',
    'front_allow_enabled' => '許可リスト有効',
    'front_allowed_ips' => '許可IPリスト',
    'front_block_enabled' => 'ブロックリスト有効',
    'front_blocked_ips' => 'ブロックIPリスト',
    'none' => '（なし）',
    'confirm_disable_all' => '⚠️ 全てのIP制限を無効化しますか？これによりどのIPからでもアクセス可能になります。',
    'confirm_add_ip' => 'IP :ip を許可リストに追加しますか？',
    'confirm_remove_ip' => 'IP :ip をブロックリストから削除しますか？',
    'cancelled' => '操作がキャンセルされました。',
    'disabled_all' => '✅ 全てのIP制限が無効化されました。',
    'security_warning' => '⚠️ セキュリティ上の理由から、復旧後は適切なIP制限を再設定してください。',
    'invalid_ip' => '無効なIPアドレスまたはCIDR範囲: :ip',
    'ip_already_exists' => 'IP :ip は既に許可リストに存在します。',
    'ip_added' => '✅ IP :ip を許可リストに追加しました。',
    'ip_not_in_blocklist' => 'IP :ip はブロックリストに存在しません。',
    'ip_removed' => '✅ IP :ip をブロックリストから削除しました。',
];
