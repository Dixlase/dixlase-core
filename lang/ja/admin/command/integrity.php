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
    'generating_baseline' => 'ファイル整合性ベースラインを生成しています...',
    'baseline_exists' => '既存のベースラインが見つかりました（生成日: :date, バージョン: :version）',
    'overwrite_confirm' => '既存のベースラインを上書きしますか？',
    'cancelled' => '操作がキャンセルされました。',
    'scanning_files' => 'ファイルをスキャン中...',
    'saving_baseline' => 'ベースラインを保存中...',
    'baseline_success' => 'ベースラインが正常に生成されました。',
    'baseline_failed' => 'ベースラインの保存に失敗しました。',
    'baseline_generated' => 'ファイル整合性ベースラインを生成しました',
    'baseline_regenerated' => 'ファイル整合性ベースラインを再生成しました',
    'starting_scan' => 'ファイル整合性スキャンを開始します...',
    'scope_not_supported' => 'スコープ ":scope" は現在サポートされていません。',
    'using_core_scope' => 'コアスコープを使用します。',
    'scanning' => 'スキャン中...',
    'scan_error' => 'スキャンエラー: :error',
    'status' => 'ステータス',
    'status_ok' => '正常',
    'status_warning' => '警告',
    'status_critical' => '重大',
    'files_scanned' => 'スキャンしたファイル数',
    'duration' => '実行時間',
    'summary' => 'サマリー',
    'changed_files' => '変更されたファイル (:count 件)',
    'added_files' => '追加されたファイル (:count 件)',
    'removed_files' => '削除されたファイル (:count 件)',
    'suspicious_files' => '疑わしいファイル (:count 件)',
    'reason_php_in_uploads' => 'アップロードディレクトリ内のPHPファイル',
    'reason_unknown_php_in_public' => 'public直下の未知のPHPファイル',
    'critical_warning' => '⚠️ 重大なセキュリティ問題が検出されました！',
    'critical_action_1' => '1. 疑わしいファイルを直ちに確認してください。',
    'critical_action_2' => '2. 不正なファイルが見つかった場合は削除してください。',
    'critical_action_3' => '3. システムのセキュリティ監査を実施してください。',
    'summary_changed' => ':count 件のファイルが変更されました',
    'summary_added' => ':count 件のファイルが追加されました',
    'summary_removed' => ':count 件のファイルが削除されました',
    'summary_suspicious' => ':count 件の疑わしいファイルがあります',
    'summary_ok' => '問題は検出されませんでした',
    'item' => '項目',
    'value' => '値',
    'files_count' => 'ファイル数',
    'app_version' => 'アプリバージョン',
    'hash_algo' => 'ハッシュアルゴリズム',
    'generated_at' => '生成日時',
    'notification_disabled' => '通知機能が無効になっています。',
    'no_notification_email' => '通知先メールアドレスが設定されていません。',
    'notification_sent' => 'アラート通知を送信しました: :email',
    'notification_failed' => '通知の送信に失敗しました: :error',
];
