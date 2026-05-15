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
    // 基本
    'updated' => '認証設定が更新されました。',

    // 二段階認証基本設定
    'two_fa_basic_settings' => '二段階認証基本設定',
    'two_fa_basic_settings_description' => '二段階認証の基本的な動作を設定します。',
    'mail_server_test_warning' => '二段階認証を使用するには、<a href=":url" class="text-blue-600 dark:text-blue-400 hover:underline">基本設定</a>でメールサーバー設定とメールテストをすべて完了してください。',

    // パスキーデバイス管理設定
    'passkey_device_management' => 'パスキーデバイス管理設定',
    'passkey_device_management_description' => 'パスキーデバイスの登録数などを設定します。',
    'passkey_max_devices' => '最大登録デバイス数',
    'passkey_max_devices_help' => '1人のユーザーが登録できるパスキーデバイスの最大数。1〜10台の範囲で設定できます。',
    'devices_unit' => '台',

    // 二段階認証詳細設定
    'two_fa_detailed_settings' => '二段階認証詳細設定',
    'two_fa_detailed_settings_description' => '二段階認証の詳細な動作を設定します。',

    'two_fa_expire_minutes' => 'コード有効期限',
    'two_fa_expire_minutes_help' => '認証コードの有効期限（分）。1〜60分の範囲で設定できます。',

    'two_fa_resend_interval_seconds' => '再送信間隔',
    'two_fa_resend_interval_seconds_help' => '認証コードの再送信が可能になるまでの時間（秒）。30〜300秒の範囲で設定できます。',

    'two_fa_max_attempts' => '最大試行回数',
    'two_fa_max_attempts_help' => '認証コード入力の最大試行回数。3〜10回の範囲で設定できます。',

    'two_fa_attempt_window' => '試行回数ウィンドウ',
    'two_fa_attempt_window_help' => '試行回数をカウントする時間（分）。5〜60分の範囲で設定できます。',

    'two_fa_lockout_duration' => 'ロックアウト期間',
    'two_fa_lockout_duration_help' => '試行回数超過時のロックアウト期間（分）。5〜1440分（24時間）の範囲で設定できます。',

    'two_fa_lockout_notification_enabled' => 'ロックアウト通知',
    'two_fa_lockout_notification_enabled_help' => '有効にすると、ロックアウト発生時に管理者にメール通知を送信します。',

    'two_fa_recovery_codes_count' => '回復コード数',
    'two_fa_recovery_codes_count_help' => '生成する回復コードの数。5〜20個の範囲で設定できます。',

    'two_fa_recovery_code_regenerate_interval' => '回復コード再生成間隔',
    'two_fa_recovery_code_regenerate_interval_help' => '回復コードの再生成が可能になるまでの日数。30〜365日の範囲で設定できます。',
];
