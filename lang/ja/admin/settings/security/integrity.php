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
    'heading' => 'ファイル整合性チェック',
    'title' => 'ファイル整合性チェック',
    'description' => 'Dixlaseコアファイルの整合性を検証し、改ざんを検出します。',
    'baseline_info' => 'ベースライン情報',
    'baseline_version' => 'バージョン',
    'baseline_generated_at' => '生成日時',
    'baseline_files_count' => 'ファイル数',
    'regenerate_baseline' => 'ベースラインを再生成',
    'regenerate_confirm' => 'ベースラインを再生成しますか？現在のベースラインは上書きされます。',
    'generate_baseline' => 'ベースラインを生成',
    'no_baseline' => 'ベースラインが存在しません',
    'no_baseline_help' => 'ファイル整合性チェックを使用するには、まずベースラインを生成してください。',
    'baseline_regenerated' => 'ベースラインが再生成されました。',
    'baseline_regeneration_failed' => 'ベースラインの再生成に失敗しました。',
    'latest_scan' => '最新スキャン結果',
    'run_scan' => 'スキャン実行',
    'scan_confirm' => 'ファイル整合性スキャンを実行しますか？すべてのコアファイルをチェックします。',
    'scan_date' => 'スキャン日時',
    'files_scanned' => 'スキャンファイル数',
    'status' => 'ステータス',
    'status_ok' => '正常',
    'status_warning' => '警告',
    'status_critical' => '重大',
    'trigger' => 'トリガー',
    'trigger_manual' => '手動',
    'trigger_schedule' => 'スケジュール',
    'trigger_install' => 'インストール',
    'trigger_update' => 'アップデート',
    'issues_found' => '検出された問題',
    'changed_files' => '変更されたファイル',
    'added_files' => '追加されたファイル',
    'removed_files' => '削除されたファイル',
    'suspicious_files' => '疑わしいファイル',
    'view_details' => '詳細を見る',
    'no_issues' => 'すべてのファイルが正常です。',
    'no_scan_yet' => 'まだスキャンが実行されていません。',
    'delete_audit' => '削除',
    'delete_audit_confirm' => 'このスキャン履歴を削除しますか？',
    'audit_deleted' => 'スキャン履歴を削除しました。',
    'bulk_delete_audits' => '古い履歴を一括削除',
    'bulk_delete_days' => '日数',
    'bulk_delete_days_help' => '指定した日数より古いスキャン履歴を削除します',
    'bulk_delete_confirm' => ':days日より古いスキャン履歴を削除しますか？',
    'audits_deleted' => ':count件のスキャン履歴を削除しました。',
    'scan_history' => 'スキャン履歴',
    'issues' => '問題数',
    'scan_completed_with_issues' => ':count ファイルをスキャンしました。問題が検出されました。',
    'scan_completed_ok' => ':count ファイルをスキャンしました。問題は検出されませんでした。',
    'back_to_list' => '一覧に戻る',
    'scan_details' => 'スキャン詳細',
    'scan_summary' => 'スキャン概要',
    'scope' => 'スコープ',
    'file_path' => 'ファイルパス',
    'expected_hash' => '期待されるハッシュ',
    'actual_hash' => '実際のハッシュ',
    'suspicious_files_help' => 'これらのファイルは通常存在しないはずの場所で検出されました。',
    'all_files_ok' => 'すべてのファイルが正常です',
    'all_files_ok_description' => 'スキャンされたすべてのファイルがベースラインと一致しています。',
    'baseline_exists' => 'ベースラインが存在します',
    'baseline_not_exists' => 'ベースラインが存在しません',
    'last_scan_result' => '最新スキャン結果',
    'scanned_at' => 'スキャン日時',
    'scanning' => 'スキャン中...',
    'regenerating' => '再生成中...',
    'regenerate_baseline_help' => 'コアファイルを更新した後は、ベースラインを再生成してください。',
    'scan_failed' => 'スキャンに失敗しました',
    'regenerate_failed' => 'ベースラインの再生成に失敗しました',

    // Scheduled security checks (daily rescan, audit log verification, core manifest)
    'scheduled' => [
        'view_details' => '詳細を見る',
        'acknowledge' => '現在の状態を承認',
        'acknowledge_help' => '毎日の再スキャンで、これらのプラグインまたはテーマの状態が悪くなりました。想定どおりの変化(自分で行った更新の後など)であれば、現在の状態を承認すると警告が消えます。そうでなければ、承認する前に原因を調べてください。',
        'acknowledged' => '警告が出ていたプラグインとテーマの、現在の状態を承認しました。',
        'alerts_heading' => '定期確認の警告',
        'core_manifest_heading' => 'コアの署名の照合',
        'core_manifest_help' => '署名付きのリリースのマニフェストとコアのファイルを照合した、最新の結果です。毎日実行します(dls:core:verify)。',
        'core_manifest_not_checked' => 'まだ照合していません。毎日実行されます。すぐに確かめるには php artisan dls:core:verify を実行してください。',
        'core_manifest_title' => 'コアの署名の照合に失敗しました',
        'core_manifest_message' => '署名付きのリリースのマニフェストでコアのファイルを照合できませんでした(:status)。セキュリティ → 整合性の画面を確認してください。',
        'audit_chain_title' => '監査ログの照合に失敗しました',
        'audit_chain_message' => '毎日の監査ログの照合で、改ざんされた記録(:tampered 件)または無効な日次の封印(:seals 件)が見つかりました。',
        'extensions_title' => '毎日の再スキャンで、プラグインまたはテーマの状態が悪くなりました',
        'extensions_message' => '確認してください: :names',
        'extension_mail_message' => '毎日の再スキャンで、:type「:name」の状態が悪くなりました: :reasons',
        'checked_at' => '照合日時',
        'changed_count' => '変更されたファイル',
        'extension' => 'プラグイン / テーマ',
        'reason' => '理由',
        'health' => '健全性',
        'signature' => '署名',
        'type_plugin' => 'プラグイン',
        'type_theme' => 'テーマ',
        'reason_health' => '健全性の状態が悪くなった',
        'reason_signature' => '署名が照合できなくなった',
        'core_status' => [
            'genuine' => '純正',
            'modified' => '改変あり',
            'unsigned' => '署名なし',
            'pending_verification' => '照合待ち',
            'invalid' => '署名が無効',
            'error' => '照合に失敗',
        ],
        'signature_status' => [
            'valid' => '有効',
            'invalid' => '無効',
            'unsigned' => '署名なし',
            'expired' => '期限切れ',
            'unknown_key' => '不明な鍵',
            'error' => 'エラー',
            'pending_verification' => '照合待ち',
        ],
    ],
];
