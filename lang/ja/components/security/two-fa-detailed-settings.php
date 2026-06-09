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
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
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
    'expire_settings' => '認証の有効期限',
    'expire_minutes' => '認証の有効期限',
    'expire_minutes_help' => '二段階認証コードの有効期限を分単位で設定します。',
    'resend_interval_seconds' => '認証メール再送信間隔',
    'resend_interval_seconds_help' => '認証メールを再送信できるまでの待機時間を秒単位で設定します。',
    'attempt_limit_settings' => '2FA試行制限設定',
    'max_attempts' => '最大試行回数',
    'max_attempts_help' => '二段階認証の最大試行回数を設定します。',
    'attempt_window' => '試行制限時間枠',
    'attempt_window_help' => '試行回数をカウントする時間枠を分単位で設定します。',
    'lockout_duration' => 'ロックアウト時間',
    'lockout_duration_help' => '試行回数超過時のロックアウト時間を分単位で設定します。',
    'lockout_notification_enabled' => '2FAロックアウト通知',
    'lockout_notification_enabled_help' => '二段階認証のロックアウト時に管理者に通知します。',
    'recovery_code_settings' => '回復コード設定',
    'recovery_codes_count' => '回復コード生成個数',
    'recovery_codes_count_help' => '生成する回復コードの個数を設定します。',
    'recovery_code_regenerate_interval' => '回復コード再生成間隔',
    'recovery_code_regenerate_interval_help' => '回復コードを再生成できるまでの待機時間を時間単位で設定します。',
];
