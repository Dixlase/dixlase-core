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
    'enabled' => 'ログイン試行制限機能',
    'enabled_help' => '一定回数ログインに失敗するとアカウントを一時的にロックします。',
    'max_attempts' => '最大試行回数（識別子ベース）',
    'max_attempts_help' => 'ロックアウトまでの最大ログイン試行回数を設定します。',
    'max_attempts_ip' => '最大試行回数（IPベース）',
    'max_attempts_ip_help' => 'IPアドレスベースのロックアウトまでの最大ログイン試行回数を設定します。',
    'time_window' => '時間窓（分）',
    'time_window_help' => '試行回数をカウントする時間窓を分単位で設定します。',
    'lockout_duration' => 'ロックアウト時間（分）',
    'lockout_duration_help' => 'アカウントがロックされる時間を分単位で設定します。',
    'notification_enabled' => 'ロックアウト通知',
    'notification_help' => 'アカウントがロックされた時に管理者に通知します。',
];
