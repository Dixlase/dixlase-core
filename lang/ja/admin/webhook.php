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
    // デッドレター関連
    'dead_letter' => [
        'notification_subject' => 'Webhook配信失敗通知',
        'notification_message' => 'Webhook「:webhook_name」への「:event」イベントの配信が:attempts回の試行後に失敗しました。エラー: :error',
    ],

    // ヘッダー説明
    'headers' => [
        'event' => 'イベント名',
        'delivery' => '配信ID',
        'event_id' => 'イベントID（冪等性用）',
        'nonce' => 'ノンス（リプレイ防止用）',
        'timestamp' => 'タイムスタンプ',
        'signature' => '署名',
    ],

    // ステータス
    'status' => [
        'pending' => '配信待ち',
        'success' => '配信成功',
        'failed' => '配信失敗',
        'retrying' => 'リトライ待ち',
        'dead_letter' => 'デッドレター',
    ],

    // 管理画面（β版以降）
    'admin' => [
        'title' => 'Webhook管理',
        'list' => 'Webhook一覧',
        'create' => 'Webhook作成',
        'edit' => 'Webhook編集',
        'deliveries' => '配信ログ',
        'dead_letters' => 'デッドレター',
        'stats' => '統計',
    ],

    // エラーメッセージ
    'errors' => [
        'webhook_inactive' => 'Webhookが無効です',
        'delivery_not_found' => '配信が見つかりません',
        'cannot_retry' => 'リトライできません',
        'signature_invalid' => '署名が無効です',
        'timestamp_expired' => 'タイムスタンプが期限切れです',
        'nonce_reused' => 'ノンスが再利用されています',
    ],
];
