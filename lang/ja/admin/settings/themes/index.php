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
    'heading' => 'テーマ管理',
    'description' => 'インストール済みテーマの管理、新しいテーマの追加、テーマの切り替えを行います。',
    'installed_heading' => 'インストール済みテーマ',
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
    ],

    // バッジラベル（カード表示用）
    'badge_labels' => [
        'health' => '健全性',
        'signature' => '署名',
        'permission' => '権限',
        'csp' => 'CSP',
    ],

    // 検証状態
    'verification' => [
        // 署名
        'signature_valid' => '署名：OK',
        'signature_unsigned' => '署名：未署名',
        'signature_invalid' => '署名：不一致',
        'signature_pending' => '署名：検証待ち',
        // 権限
        'permission_ok' => '権限定義：OK',
        'permission_undefined' => '権限定義：未定義',
        'permission_mismatch' => '権限定義：不一致',
        // CSP
        'csp_ready' => 'CSP Ready',
        'csp_compatible' => 'CSP互換',
        'csp_inline_required' => 'インラインJS必須',
        'csp_not_checked' => 'CSP未検証',
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
        'unknown' => '未定義',
        'unknown_warning' => '権限情報が定義されていません。このテーマがどのような操作を行うか不明です。信頼できるソースから入手したことを確認してください。',
        'audit_mismatch_title' => '権限の不一致',
        'audit_mismatch_warning' => 'theme.json で宣言された権限と実際のコードが一致しません。',
        'audit_undeclared_usage' => '未宣言の機能使用',
        'audit_unused_declaration' => '未使用の権限宣言',
        'audit_button' => 'スキャン',
        'audit_button_rescan' => '再スキャン',
        'audit_scanning' => 'スキャン中...',
        'audit_scanning_description' => 'テーマのセキュリティスキャンを実行しています。完了するまでお待ちください。',
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
        'signature_invalid' => '署名無効',
        'signature_unsigned' => '未署名',
        'signature_invalid_warning' => '⚠️ このテーマの署名は無効です。改ざんされている可能性があります。',
        'signature_unsigned_info' => 'このテーマは署名されていません。信頼できるソースから入手したことを確認してください。',
        'signed_by' => '署名者',
        'permission_info' => '権限情報',
        'category_database' => 'データベース',
        'category_storage' => 'ストレージ',
        'category_settings' => '設定',
        'category_assets' => 'アセット',
        'category_system' => 'システム',
        'perm_own_tables' => '専用テーブル',
        'perm_core_tables' => 'コアテーブル',
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
        'attention_reason_database_core_tables' => 'コアテーブルへのアクセス権限を使用します',
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
        'warning_not_scanned' => 'コードスキャンが実行されていません',
        'risk_medium' => '中程度の健全性リスク',
        'risk_high' => '高い健全性リスク',
        'enable_warning_title' => '有効化前の確認',
        'enable_warning_message' => 'このテーマには以下の注意点があります：',
        'enable_warning_confirm' => '上記を理解した上で有効化しますか？',
        'enable_warning_invalid_signature' => '署名が無効です（改ざんの可能性）',
        'enable_warning_needs_attention' => '確認が必要な権限が含まれています',
    ],
];
