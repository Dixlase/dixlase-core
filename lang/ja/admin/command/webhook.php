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
    'dead_letters' => [
        'no_action' => 'アクションが指定されていません。以下のオプションを使用してください:',
        'option_notify' => '未通知のデッドレターの通知を送信',
        'option_cleanup' => '古いデッドレターレコードをクリーンアップ',
        'option_stats' => 'デッドレター統計を表示',
        'sending_notifications' => '未通知のデッドレターの通知を送信中...',
        'notifications_sent' => ':count 件の通知を送信しました。',
        'no_pending_notifications' => '保留中の通知はありません。',
        'cleaning_up' => ':days 日以上前のデッドレターをクリーンアップ中...',
        'cleanup_complete' => ':count 件のレコードを削除しました。',
        'stats_title' => 'Webhookデッドレター統計（過去30日間）',
        'stat_name' => '項目',
        'stat_value' => '値',
        'total' => '合計',
        'pending' => '保留中',
        'notified' => '通知済み',
        'manually_retried' => '手動リトライ済み',
        'by_event' => 'イベント別:',
        'event' => 'イベント',
        'count' => '件数',
        'option_list' => '保留中のデッドレターを一覧表示',
        'option_retry' => '指定IDのデッドレターをリトライ',
        'option_retry_all' => '全ての保留中デッドレターをリトライ',
        'no_pending' => '保留中のデッドレターはありません。',
        'pending_list_title' => '【保留中のデッドレター】',
        'col_id' => 'ID',
        'col_webhook' => 'Webhook',
        'col_attempts' => '試行回数',
        'col_error' => 'エラー',
        'col_created' => '作成日時',
        'retry_hint' => 'リトライするには: php artisan webhooks:dead-letters --retry=ID',
        'not_found' => 'デッドレター ID :id が見つかりません。',
        'cannot_retry' => 'リトライできません（Webhookが無効化されています）。',
        'retry_success' => '✅ デッドレター ID :id をリトライしました（配信ID: :delivery_id）',
        'retry_queued' => 'リトライがキューに追加されました。',
        'retry_failed' => 'リトライに失敗しました: :error',
        'confirm_retry_all' => ':count 件のデッドレターを全てリトライしますか？',
        'cancelled' => '操作がキャンセルされました。',
        'retry_all_complete' => '✅ リトライ完了: 成功 :success 件、失敗 :failed 件',
    ],
];
