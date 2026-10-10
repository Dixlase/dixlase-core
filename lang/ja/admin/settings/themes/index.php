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
    'heading' => 'テーマ管理',
    'description' => 'インストール済みテーマの管理、新しいテーマの追加、テーマの切り替えを行います。',
    'installed_heading' => 'インストール済みテーマ',
    'update_available' => 'v:version が利用可能',
    'stale_download' => 'v:latest が公開されています(ダウンロード済みのものは v:version)。インストールする前に、削除してから追加し直してください。',
    'update_failed_at' => ':date にアップデートに失敗しました',
    'updates' => [
        'check' => 'アップデートを確認',
        'checking' => '確認中...',
        'all_up_to_date' => 'すべてのテーマは最新です。',
        'updates_found' => ':count 件のアップデートがあります。',
        'no_update' => 'このテーマのアップデートはありません。',
        'update_button' => 'アップデート',
        'update_to_version' => 'v:version へアップデート',
        'update_success' => 'テーマ「:name」を v:version にアップデートしました。',
        'update_failed' => 'テーマのアップデートに失敗しました: :error',
        'update_all_button' => 'すべて更新',
        'update_all_confirm_title' => 'すべてのテーマを更新',
        'update_all_confirm_message' => '更新可能な :count 件のテーマを一括で最新バージョンに更新します。よろしいですか？',
        'update_all_running' => 'テーマを順次更新中です。完了までしばらくお待ちください。',
        'update_all_summary' => ':total 件中 :succeeded 件成功 / :failed 件失敗',
    ],
    'uninstalled_heading' => 'アンインストール済みテーマ',
    'title' => 'テーマ',
    'available_themes' => '利用可能なテーマ',
    'currently_active' => '現在使用中',
    'activate_confirm' => 'このテーマを有効化しますか？',
    'delete_confirm' => '本当に削除しますか？',
    'activate_button' => '有効化',
    'delete_button' => '削除',
    'settings_button' => '設定',
    'table' => [
        'caption' => 'インストール済みテーマ一覧',
        'name' => 'テーマ名',
    ],
    'uninstalled_table' => [
        'caption' => 'アンインストール済みテーマ一覧',
    ],
    'no_themes' => 'テーマがインストールされていません。',
    'no_themes_description' => 'テーマを追加して、サイトの外観をカスタマイズしましょう。',
    'add_theme' => 'テーマを追加',
    'view_details' => '詳細',
    'uninstalled_description' => 'これらのテーマはファイルが存在しますが、まだインストールされていません。',
    'uninstall' => [
        'confirm_title' => 'アンインストールの確認',
        'confirm_message' => 'テーマ [{name}] をアンインストールしますか？',
        'remove_data_checkbox' => 'テーマのインストール時に作成されたデータベースのテーブルを削除する。<br><br><span class="text-red-600 font-semibold">注意！テーブル削除するとテーマで作成したデータが失われます！</span>',
    ],
    'install' => [
        'confirm_title' => 'インストールの確認',
        'confirm_message' => 'テーマ [{name}] をインストールしますか？',
    ],
    'switch' => [
        'confirm_title' => '有効化の確認',
        'confirm_message' => 'テーマ [{name}] を有効化しますか？',
    ],
    'delete' => [
        'confirm_title' => '削除の確認',
        'confirm_message' => 'テーマ [{name}] のファイルとフォルダを完全に削除しますか？この操作は取り消せません。',
    ],
    'audit' => [
        'invalid_slug' => 'テーマスラッグが無効です。',
        'completed' => 'テーマのスキャンが完了しました。',
        'failed' => 'テーマのスキャンに失敗しました。',
        'audit_all_button' => '全テーマを再スキャン',
        'audit_all_confirm_title' => '全テーマの再スキャン',
        'audit_all_confirm_message' => 'インストール済みおよびアンインストール済みのすべてのテーマを順次スキャンします。完了までしばらくお待ちください。',
        'audit_all_summary' => ':total 件中 :succeeded 件のスキャンが完了しました（失敗 :failed 件）。',
    ],

    // スキャン鮮度バッジ
    'scan_status' => [
        'unscanned' => '未スキャン',
        'expired' => 'スキャン期限切れ（前回 :age 日前 / 期限 :max 日）',
        'files_changed' => 'ファイル変更検知 — 再スキャン推奨',
    ],

    // バッジラベル（カード表示用）
    'badge_labels' => [
        'health' => '健全',
        'health_full' => '健全性',
        'signature' => '署名',
        'permission' => '権限',
        'csp' => 'CSP',
        'preset' => '拡張',
        'operation' => '動作',
    ],

    // CSPモードバッジラベル
    'csp_mode' => [
        'development' => '開発',
        'standard' => '標準',
        'strict' => '厳格',
        'not_checked' => '未検証',
    ],

    // セキュリティプリセット互換バッジラベル
    'preset_badge' => [
        'development' => '開発',
        'balanced' => 'バランス',
        'strict' => '厳格',
        'custom' => '現在の設定',
        'not_verified' => '未確認',
    ],

    // 健全性ステータス
    'health_status' => [
        'healthy' => '健全',
        'healthy_description' => '宣言された権限・署名・構成に不一致は見つかりませんでした。',
        'advisory' => '注意',
        'advisory_description' => '軽微な指摘があります。動作に直ちに影響はありませんが、見直しを推奨します。',
        'needs_attention' => '要確認',
        'needs_attention_description' => '重要な指摘があります。有効化・運用前に内容を確認してください。',
        'not_verified' => '未確認',
        'not_verified_description' => '検証情報が不足しています（未スキャン、権限定義なし、署名なし等）。',
    ],

    // 検証状態
    'verification' => [
        // 署名
        'signature_valid' => '署名あり',
        'signature_unsigned' => '未署名',
        'signature_invalid' => '署名不一致',
        'signature_waived' => '免除済み',
        'signature_pending' => '検証待ち',
        'signature_not_scanned' => '未確認',
        // 権限
        'permission_ok' => 'OK',
        'permission_undefined' => '未定義',
        'permission_mismatch' => '不一致',
        'permission_not_scanned' => '未確認',
        // CSP
        'csp_ready' => 'CSP対応済み',
        'csp_compatible' => 'CSP互換',
        'csp_inline_required' => 'CSP未対応',
        'csp_not_checked' => 'CSP未検証',
    ],

    // 動作判定（信号機）
    'operation_status' => [
        'ok' => '完全動作',
        'caution' => '注意あり',
        'blocked' => '動作不可',
        'unknown' => '未確認',
    ],

    // 動作ステータス — 詳細ページのセクション
    'operation_status_heading' => '動作ステータス',
    'operation_status_description' => [
        'ok' => '現在のセキュリティ設定で、このテーマは制限なく動作します。',
        'caution' => 'このテーマは動作しますが、確認すべき問題（未確認の権限、署名なしなど）があります。',
        'blocked' => '現在のセキュリティ設定では、このテーマを動作させられません。セキュリティプリセットや CSP モードを下げるか、報告された問題を解消してください。',
        'unknown' => '動作ステータスはまだ判定されていません。スキャンを実行して評価してください。',
    ],

    // CSP適合性
    'csp' => [
        'status_label' => 'CSP適合性',
        'ready_tooltip' => 'このテーマはCSP完全対応です。すべてのCSPモードで動作します。',
        'inline_required_tooltip' => 'このテーマはインラインJavaScriptを必要とします。CSP厳格モードでは動作しません。',
    ],

    'permissions' => [
        'health_status' => '健全性',
        'health_healthy' => '良好',
        'health_warning' => '注意',
        'health_needs_attention' => '要確認',
        'health_not_verified' => '未確認',
        'health_status_healthy' => '良好',
        'health_status_advisory' => '注意',
        'health_status_needs_attention' => '要確認',
        'health_status_not_verified' => '未検証',
        'unknown' => '未定義',
        'unknown_warning' => '権限情報が定義されていません。このテーマがどのような操作を行うか不明です。信頼できるソースから入手したことを確認してください。',
        'audit_mismatch_title' => '権限の不一致',
        'audit_mismatch_warning' => 'theme.json で宣言された権限と実際のコードが一致しません。',
        'audit_undeclared_usage' => '未宣言の機能使用',
        'audit_unused_declaration' => '未使用の権限宣言',
        'audit_button' => 'スキャン',
        'audit_button_rescan' => '再スキャン',
        'audit_scanning' => 'スキャン中...',
        'audit_scanning_title' => 'テーマをスキャン中',
        'audit_scanning_description' => 'テーマのセキュリティスキャンを実行しています。<br>完了するまでお待ちください。',
        'audit_not_scanned' => '未スキャン',
        'audit_last_scanned' => '最終スキャン',
        'audit_result_title' => 'スキャン結果',
        'audit_mismatch_found' => '権限の不一致が検出されました',
        'audit_no_issues' => '問題は検出されませんでした',
        'audit_stats' => 'チェック項目',
        'audit_matches' => '一致',
        'audit_mismatches' => '不一致',
        'audit_mismatch_badge' => '不一致',
        'no_permissions' => '権限情報が定義されていません',
        'details_title' => 'テーマ詳細',
        'no_special_permissions' => '特別な権限はありません',
        'no_permissions_defined' => '権限情報が定義されていません。このテーマの権限は不明です。',
        'signature_status' => '署名ステータス',
        'signature_official' => '公式',
        'signature_verified' => '認証済み',
        'signature_partner' => 'パートナー',
        'signature_signed' => '署名済み',
        'signature_valid' => '署名あり',
        'signature_invalid' => '署名無効',
        'signature_unsigned' => '未署名',
        'signature_unknown_key' => '不明な署名鍵',
        'signature_expired' => '鍵失効',
        'signature_error' => '検証エラー',
        'signature_invalid_warning' => '⚠️ このテーマの署名は無効です。改ざんされている可能性があります。',
        'signature_unsigned_info' => 'このテーマは署名されていません。信頼できるソースから入手したことを確認してください。',
        'signature_unknown_key_info' => 'このテーマの署名鍵は信頼済み鍵として登録されていません。配布元を確認してください。',
        'signature_expired_info' => 'このテーマの署名に使用された鍵は失効しています。',
        'signature_error_info' => '署名検証中にエラーが発生しました。',
        'signed_by' => '署名者',
        'permission_info' => '権限情報',
        'category_database' => 'データベース',
        'category_storage' => 'ストレージ',
        'category_settings' => '設定',
        'category_assets' => 'アセット',
        'category_system' => 'システム',
        'category_migrations' => 'マイグレーション',
        'perm_stock_migrator' => '標準 migrator への登録',
        'perm_own_tables' => '専用テーブル',
        'perm_core_tables_read' => 'コアテーブル（読取）',
        'perm_core_tables_write' => 'コアテーブル（書込）',
        'perm_own_directory' => '専用ディレクトリ',
        'perm_public_uploads' => '公開アップロード',
        'perm_temp_files' => '一時ファイル',
        'perm_read_core' => 'コア設定読取',
        'perm_write_own' => '自己設定書込',
        'perm_custom_css' => 'カスタムCSS',
        'perm_custom_js' => 'カスタムJS',
        'perm_external_resources' => '外部リソース',
        'perm_register_shortcodes' => 'ショートコード',
        'perm_register_middleware' => 'ミドルウェア',
        'perm_register_commands' => 'コマンド',
        'perm_register_blade_directives' => 'Blade指令',
        'perm_modify_routes' => 'ルート変更',
        'attention_reasons_title' => '確認が必要な理由',
        'attention_reason_storage_public_uploads' => '公開ディレクトリへのアップロード権限を使用します',
        'attention_reason_assets_external_resources' => '外部リソースの読み込み権限を使用します',
        'attention_reason_assets_external_resources_trusted' => '信頼できる外部リソースを読み込んでいます (:domains)',
        'attention_reason_database_core_tables_read' => 'コアテーブルの読み取り権限を使用します',
        'attention_reason_database_core_tables_write' => 'コアテーブルへの書き込み権限を使用します',
        'attention_reason_settings_read_core' => 'コア設定の読み取り権限を使用します',
        'attention_reason_system_register_middleware' => 'ミドルウェアの登録権限を使用します',
        'attention_reason_system_register_commands' => 'コマンドの登録権限を使用します',
        'attention_reason_system_register_blade_directives' => 'Blade指令の登録権限を使用します',
        'attention_reason_system_modify_routes' => 'ルートの変更権限を使用します',
        'attention_reason_mismatch_undeclared_usage' => '未宣言の権限使用が検出されました（:count件）',
        'permission_consistency_title' => '権限定義の整合性',
        'total_risk_score' => 'リスクスコア合計',
        'install_warning_title' => 'インストール前の確認',
        'install_warning_notice' => 'このテーマには以下の確認事項があります：',
        'install_warning_undefined' => '権限情報が未定義です',
        'install_warning_unsigned' => '署名されていません',
        'install_warning_mismatch' => '権限宣言とコードが一致しません',
        'install_warning_confirm' => '上記を理解した上でインストールしますか？',
        'install_warning_risk' => 'このテーマには以下の注意点があります：',
        'warning_not_scanned' => 'スキャンが実行されていません',
        'risk_medium' => '中程度の健全性リスク',
        'risk_high' => '高い健全性リスク',
        'enable_warning_title' => '有効化前の確認',
        'enable_warning_message' => 'このテーマには以下の注意点があります：',
        'enable_warning_confirm' => '上記を理解した上で有効化しますか？',
        'enable_warning_invalid_signature' => '署名が無効です（改ざんの可能性）',
        'enable_warning_signature_waived' => '署名チェックは運用者により免除されています',
        'enable_warning_needs_attention' => '確認が必要な権限が含まれています',
        'total_evaluation' => '総合評価',
        'health_score_display' => 'スコア: :score/100',
        'signature_section_label' => '署名',
        'health_issue_signature_unsigned' => '署名: 未署名',
        'health_issue_signature_invalid' => '署名: 無効',
        'health_issue_permission_undefined' => '権限: 未定義',
        'health_issue_permission_undeclared_minor' => '権限: 未宣言の使用（軽微）',
        'health_issue_permission_undeclared_major' => '権限: 未宣言の使用（重大）',
        'health_issue_permission_unused' => '権限: 未使用の宣言',
        'health_issue_csp_inline_css_required' => 'CSP: インラインCSS必須',
        'health_issue_csp_inline_js_required' => 'CSP: インラインJS必須',
        'health_issue_csp_violation_strict' => 'CSP: 違反（厳格モード）',
        'health_issue_csp_violation_standard' => 'CSP: 違反（標準モード）',
        'health_issue_dangerous_api_exec' => '危険なAPI検出',
        'health_issue_dangerous_api_declared' => '危険なAPIを使用（宣言済み）',
        'health_issue_scan_not_performed' => 'スキャン: 未実行',
        'health_issue_scan_outdated' => 'スキャン: 期限切れ',
        'csp_status' => 'CSP対応',
        'csp_section_label' => 'CSP対応',
        'csp_compliant' => '対応済み',
        'csp_not_compliant' => '未対応',
        'csp_inline_scripts' => 'インラインスクリプト',
        'csp_inline_styles' => 'インラインスタイル',
        'csp_event_handlers' => 'イベントハンドラ',
        'csp_javascript_urls' => 'JavaScript URL',
        'csp_violation_inline_script' => 'インラインスクリプト',
        'csp_violation_inline_style' => 'インラインスタイル',
        'csp_violation_event_handler' => 'イベントハンドラ',
        'csp_violation_javascript_url' => 'JavaScript URL',
        // Theme detail page scan-details section
        'csp_warning_title' => 'CSP非対応の警告',
        'csp_warning_message' => 'このテーマにはCSP非対応のインラインスクリプト/スタイルが含まれています。CSPを強制モードで有効にすると、一部の機能が動作しない可能性があります。',
        'csp_fix_suggestion' => '修正方法: <script> を <script @cspNonce> に、<style> を <style @cspNonce> に変更してください。',
        'signature_pending_verification' => '検証待ち',
        'signature_pending_verification_info' => 'このテーマには署名がありますが、公開鍵サーバーとの照合がまだ完了していません。',
        'scan_recommendation' => '実行前にスキャンを行うことを推奨します。',
        'database_owned_tables_label' => '作成するテーブル',
        'database_owned_tables_description' => 'このテーマがマイグレーションで作成するテーブルです。',
        'database_owned_tables_empty' => 'このテーマはテーブルを作成しません。',
        'database_owned_tables_source_declared' => 'theme.json に明示宣言',
        'database_owned_tables_source_detected' => 'マイグレーションから自動検出',
        'database_writes_to_other_label' => '他プラグインのテーブルへの書き込み',
        'database_writes_to_other_target' => '対象プラグイン: :slug',
        'healthy_body' => '宣言された権限と検出された利用状況に不一致はありません。',
    ],
    'capabilities' => [
        'title' => '提供機能',
        'description' => 'このテーマが提供する機能の宣言です。コアやプラグインがこの宣言を参照して機能を検出します。',
    ],
];
