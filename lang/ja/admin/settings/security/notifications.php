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
    'heading' => 'エラー通知設定',
    'title' => 'システムエラー通知設定',
    'description' => 'システムエラーやアプリケーションの問題が発生した際の通知設定を行います。',
    'enabled' => 'エラー通知機能',
    'enabled_help' => 'システムエラーが発生した際にメール通知を送信するかどうかを設定します。',
    'log_levels' => '通知するログレベル',
    'log_levels_help' => '通知を送信するログレベルを選択してください。重要度の高いエラーのみを通知することで、必要な情報だけを受け取れます。',
    'system_admin_email_required' => 'エラー通知を受信するには、<a href=":url" class="text-blue-600 dark:text-blue-400 hover:underline">メール設定</a>でシステム管理者メールアドレスを設定してください。',
    'mail_test_required' => 'エラー通知機能を使用するには、<a href=":url" class="text-blue-600 dark:text-blue-400 hover:underline">メール設定</a>でメールサーバー設定とメールテストをすべて完了してください。',
    'log_level_options' => [
        'emergency' => 'Emergency（緊急）- システムが使用不可',
        'alert' => 'Alert（警告）- 即座に対応が必要',
        'critical' => 'Critical（重大）- 重大な状況',
        'error' => 'Error（エラー）- エラー状況だが動作継続',
        'warning' => 'Warning（注意）- 警告レベルの問題',
        'notice' => 'Notice（通知）- 正常だが注目すべき状況',
        'info' => 'Info（情報）- 一般的な情報メッセージ',
        'debug' => 'Debug（デバッグ）- デバッグ情報',
    ],
    'settings_updated' => 'エラー通知設定が更新されました。',
];
