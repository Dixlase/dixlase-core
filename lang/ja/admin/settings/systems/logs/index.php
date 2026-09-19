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
    'heading' => 'ログ管理',
    'description' => 'システムの操作履歴を確認、検索、エクスポートできます。管理者の行動を追跡し、セキュリティ監査に活用できます。',
    'log_type_label' => 'ログ種別',
    'audit_db' => '監査ログ',
    'audit_file' => 'ファイルログ',
    'show' => [
        'heading' => '監査ログ詳細',
    ],
    'detail_title' => '監査ログ詳細',
    'filters' => 'フィルター',
    'search' => '検索',
    'search_placeholder' => '行為者、対象、アクション、IPで検索...',
    'category' => 'カテゴリ',
    'action' => 'アクション',
    'severity' => '重要度',
    'outcome' => '結果',
    'actor' => '行為者',
    'target' => '対象',
    'ip_address' => 'IPアドレス',
    'user_agent' => 'ユーザーエージェント',
    'request_id' => 'リクエストID',
    'session_id' => 'セッションID',
    'plugin' => 'プラグイン',
    'occurred_at' => '発生日時',
    'date_from' => '開始日',
    'date_to' => '終了日',
    'no_logs' => '監査ログがありません。',
    'table_not_exists' => '監査ログテーブルが存在しません。マイグレーションを実行してください。',
    'export_csv' => 'CSVエクスポート',
    'basic_info' => '基本情報',
    'actor_target' => '行為者・対象',
    'context' => 'コンテキスト',
    'request_info' => 'リクエスト情報',
    'related_logs' => '関連ログ',
    'same_request' => '同一リクエスト内のログ',
    'meta_info' => 'メタ情報',
    'chain_sequence' => 'チェーン連番',
    'record_hash' => 'レコードハッシュ',
    'verification_status' => '検証状態',
    'last_verified_at' => '最終検証日時',
    'verification_statuses' => [
        'valid' => '検証済み',
        'invalid' => '改ざん検知',
        'unverified' => '未検証',
        'unchained' => '未連結',
    ],
    'system' => 'システム',
    'impersonated_by' => 'なりすまし操作者',
    'changes' => '変更内容',
    'field' => 'フィールド',
    'before' => '変更前',
    'after' => '変更後',
    'show_raw_json' => '生のJSONを表示',
    'hide_raw_json' => '生のJSONを隠す',
    'cleanup_title' => 'ログクリーンアップ',
    'cleanup_days' => '保持日数',
    'cleanup_button' => '古いログを削除',
    'cleanup_description' => '指定した日数より古い監査ログを削除します。0を指定すると全てのログを削除します。',
    'cleanup_confirm' => '指定した日数より古い監査ログを削除しますか？この操作は取り消せません。',
    'cleanup_success' => ':count 件の監査ログを削除しました。',
    'cleanup_modal' => [
        'title' => '監査ログのクリーンアップ',
        'confirm_message' => '監査ログを削除してもよろしいですか？<br>この操作は元に戻せません。',
    ],
    'categories' => [
        'auth' => '認証',
        'account' => 'アカウント',
        'device' => 'デバイス',
        'security' => 'セキュリティ',
        'session' => 'セッション',
        'extension' => '拡張機能',
        'content' => 'コンテンツ',
        'system' => 'システム',
        'plugin' => 'プラグイン',
    ],
    'severities' => [
        'debug' => 'デバッグ',
        'info' => '情報',
        'notice' => '通知',
        'warning' => '警告',
        'error' => 'エラー',
        'critical' => '重大',
        'alert' => 'アラート',
        'emergency' => '緊急',
    ],
    'outcomes' => [
        'success' => '成功',
        'failure' => '失敗',
        'denied' => '拒否',
        'pending' => '保留',
        'unknown' => '不明',
    ],
];
