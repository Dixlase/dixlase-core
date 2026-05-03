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
    'heading' => 'ファイルログ',
    'description' => 'アプリケーションログファイルを日付別に確認、ダウンロード、クリアできます。',
    'date_latest' => '最新',
    'date_select' => '日付を選択',
    'no_logs_found' => 'ログが見つかりません。',
    'activity' => 'アクティビティ',
    'error' => 'エラー',
    'dixlase' => 'Dixlase',
    'front_activity' => '操作',
    'front_error' => 'エラー',
    'browser' => 'ブラウザ',
    'csp' => 'CSP',
    'audit' => '監査',
    'clear' => 'ログ消去',
    'clear_confirm' => 'ログファイルの内容を消去してもよろしいですか？この操作は元に戻せません。',
    'clear_days_label' => '削除する日数',
    'clear_modal' => [
        'title' => 'ログファイルのクリア',
        'confirm_message' => 'ログファイルをクリアしてもよろしいですか？<br>この操作は元に戻せません。',
    ],
    'clear_all_success' => 'すべてのログファイルをクリアしました（:count件）',
    'clear_old_success' => ':days日以前のログファイルを削除しました（:count件）',
    'clear_failed' => 'ログファイルのクリアに失敗しました：:error',
    'cleanup_title' => 'ログファイルのクリーンアップ',
    'cleanup_description' => '指定した日数以前のログファイルを削除します。0日を指定するとすべてのログファイルをクリアします。',
    'admin_logs_label' => '管理画面',
    'front_logs_label' => 'フロント',
    'security_logs_label' => 'セキュリティ',
    'browser_logs_label' => 'ブラウザ',
    'messages' => [
        'download_error' => 'ログファイルが存在しません：:filename',
        'clear_success' => 'ログファイルを消去しました：:filename',
        'clear_error' => 'ログファイルが存在しません：:filename',
        'clear_failed' => 'ログファイルの消去に失敗しました：:error',
        'file_not_found' => 'ログファイルが存在しません：:filename',
    ],
    'test_success' => 'テストログを記録しました：:results',
    'level_filter' => [
        'label' => 'ログレベル',
        'error' => 'エラー',
        'warning' => '警告',
        'normal' => '通常',
        'debug' => 'デバッグ',
    ],
];
