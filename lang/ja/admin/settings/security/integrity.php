<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
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
];
