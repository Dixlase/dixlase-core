<?php

/**
 * This file is part of Dixlase.
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
    'subject' => '【セキュリティ警告】管理画面ログインロックアウト発生',
    'title' => '管理画面ログインロックアウト通知',
    'message' => '管理画面でログインロックアウトが発生しました。不正なログイン試行の可能性があります。',
    'details' => 'ロックアウト詳細',
    'identifier' => 'メールアドレス',
    'ip_address' => 'IPアドレス',
    'user_agent' => 'ユーザーエージェント',
    'timestamp' => '発生日時',
    'settings' => 'ロックアウト設定',
    'max_attempts' => '最大試行回数',
    'time_window' => '時間枠',
    'lockout_duration' => 'ロックアウト期間',
    'times' => '回',
    'minutes' => '分',
    'action_required' => 'セキュリティ上の理由により、このログインロックアウトを確認し、必要に応じて適切な対応を行ってください。',
    'thanks' => 'よろしくお願いいたします',
];
