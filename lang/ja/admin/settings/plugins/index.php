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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
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
    'heading' => 'プラグイン管理',
    'description' => 'インストール済みプラグインの管理、新しいプラグインの追加、プラグインの有効化・無効化を行います。',
    'installed_heading' => 'インストール済みプラグイン',
    'update_available' => 'v:version が利用可能',
    'updates' => [
        'check' => 'アップデートを確認',
        'checking' => '確認中...',
        'all_up_to_date' => 'すべてのプラグインは最新です。',
        'updates_found' => ':count 件のアップデートがあります。',
        'no_update' => 'このプラグインのアップデートはありません。',
        'update_button' => 'アップデート',
        'update_success' => 'プラグイン「:name」を v:version にアップデートしました。',
        'update_failed' => 'プラグインのアップデートに失敗しました: :error',
    ],
    'uninstalled_heading' => 'アンインストール済みプラグイン',
    'systems' => [
        'text' => 'システム',
        'cache' => 'キャッシュ管理',
        'database' => 'データベース管理',
        'logs' => 'システムログ',
        'info' => 'システム情報',
    ],
    'table' => [
        'id' => 'ID',
        'name' => 'プラグイン名',
        'caption' => 'インストール済みプラグイン一覧',
    ],
    'no_plugins' => 'プラグインがインストールされていません。',
    'no_plugins_description' => 'プラグインを追加して、サイトの機能を拡張しましょう。',
    'add_plugin' => 'プラグインを追加',
    'view_details' => '詳細',
    'uninstalled_description' => 'これらのプラグインはファイルが存在しますが、まだインストールされていません。',
    'buttons' => [],
    'uninstall' => [
        'confirm_title' => 'アンインストールの確認',
        'confirm_message' => 'プラグイン [{name}] をアンインストールしますか？',
        'remove_data_checkbox' => 'プラグインのインストール時に作成されたデータベースのテーブルを削除する。<br><br><span class="text-red-600 font-semibold">注意！テーブル削除するとプラグインで作成したデータが失われます！</span>',
    ],
    'install' => [
        'confirm_title' => 'インストールの確認',
        'confirm_message' => 'プラグイン [{name}] をインストールしますか？',
    ],
    'enabled' => [
        'confirm_message' => 'プラグイン [{name}] を有効化しますか？',
        'success' => '{name}を有効化しました',
        'failed' => '{name}の有効化に失敗しました',
    ],
    'disabled' => [
        'confirm_title' => '無効化の確認',
        'confirm_message' => 'プラグイン [{name}] を無効化しますか？',
        'confirm_warning' => 'このプラグインが提供する機能は一時的に利用できなくなります。',
    ],
    'delete' => [
        'confirm_title' => '削除の確認',
        'confirm_message' => 'プラグイン [{name}] のファイルとフォルダを完全に削除しますか？この操作は取り消せません。',
    ],
    'audit' => [
        'invalid_slug' => 'プラグインスラッグが無効です。',
        'completed' => 'プラグインのスキャンが完了しました。',
        'failed' => 'プラグインのスキャンに失敗しました。',
    ],

    // 有効化アクション（PluginEnableAction Enum）
    'enable_action' => [
        'allowed' => '有効化可能',
        'warning' => '警告：軽微な問題が検出されました',
        'ack' => '確認必須：重要な問題が検出されました',
        'blocked' => '有効化ブロック：致命的な問題が検出されました',
        'blocked_message' => '致命的な健全性の問題があるため、このプラグインを有効化できません。問題を解消して再スキャンしてください。',
    ],

    // 再スキャン
    'rescan' => [
        'files_changed' => '前回のスキャン以降にプラグインファイルが変更されています。再スキャンします...',
        'auto_triggered' => '自動セキュリティスキャンを実行しました。',
    ],

    // 健全性指摘の説明
    'health_issue' => [
        'csp_inline_css_required' => 'インラインCSSが必要です。厳格モードでは動作しない可能性があります。',
        'csp_external_resources' => '外部リソースが検出されました。セキュリティ上の確認を推奨します。',
        'signature_unsigned' => '署名がありません。配布時は署名を推奨します。',
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

    // 健全性ステータス（PluginHealthStatus Enum）
    'health_status' => [
        'healthy' => '健全',
        'healthy_description' => '宣言された権限・署名・構成に不一致は見つかりませんでした。',
        'healthy_tooltip' => '宣言された権限・署名・構成に不一致は見つかりませんでした。',
        'advisory' => '注意',
        'advisory_description' => '軽微な指摘があります。動作に直ちに影響はありませんが、見直しを推奨します。',
        'advisory_tooltip' => '軽微な指摘があります。動作に直ちに影響はありませんが、見直しを推奨します。',
        'needs_attention' => '要確認',
        'needs_attention_description' => '重要な指摘があります。有効化・運用前に内容を確認してください。',
        'needs_attention_tooltip' => '重要な指摘があります。有効化・運用前に内容を確認してください。',
        'not_verified' => '未確認',
        'not_verified_description' => '検証情報が不足しています（未スキャン、権限定義なし、署名なし等）。',
        'not_verified_tooltip' => '検証情報が不足しています（未スキャン、権限定義なし、署名なし等）。',
    ],

    // 信頼度レベル（PluginTrustLevel Enum）
    'trust_level' => [
        'official' => '公式',
        'official_description' => 'Dixlase公式による配布です。',
        'verified' => '認証済み',
        'verified_description' => '認証済みパブリッシャーによる配布です。',
        'partner' => 'パートナー',
        'partner_description' => 'Dixlaseパートナーによる配布です。',
        'community' => 'コミュニティ',
        'community_description' => '未認証の配布者による配布です。',
        'local' => 'ローカル',
        'local_description' => '手動インストールまたはローカル開発です。',
    ],

    // 検証状態（PluginVerificationStatus Enum）
    'verification' => [
        // 署名
        'signature_valid' => '署名あり',
        'signature_unsigned' => '未署名',
        'signature_invalid' => '署名不一致',
        'signature_pending' => '検証待ち',
        'signature_pending_verification' => '検証待ち',
        'signature_not_scanned' => '未確認',
        // 権限
        'permission_ok' => 'OK',
        'permission_undefined' => '未定義',
        'permission_mismatch' => '不一致',
        'permission_not_scanned' => '未確認',
        // スキャン
        'scan_not_performed' => 'スキャン：未実行',
        'scan_outdated' => 'スキャン：期限切れ',
        'scan_completed' => 'スキャン：完了',
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

    // 簡単モード表示
    'simple' => [
        'health_safe' => '安全',
        'health_caution' => '注意',
        'health_problem' => '問題あり',
        'operation_usable' => '可能',
        'operation_unusable' => '不可',
        'unknown' => '不明',
    ],

    // モーダル文言
    'modal' => [
        'health_check_title' => '健全性チェックの詳細',
        'plugin_info' => 'プラグイン：:name（:slug）',
        'version_info' => 'バージョン：:version',
        'last_scan_info' => '最終スキャン：:date / スキャナ：v:version',

        // 健全（Healthy）
        'healthy_heading' => '健全（問題は検出されませんでした）',
        'healthy_body' => '宣言された権限と検出された利用状況に不一致はありません。',
        'healthy_note' => 'この結果は「現在のルールセット」に基づきます。',

        // 注意（Advisory）
        'advisory_heading' => '注意（見直し推奨）',
        'advisory_body' => '軽微な指摘が :count 件あります。動作を妨げるものではありませんが、透明性のため確認を推奨します。',
        'advisory_action_permission' => 'plugin.json の permission を実態に合わせて更新してください。',
        'advisory_action_signature' => '本番運用する場合は署名を付与してください。',

        // 要確認（NeedsAttention）
        'needs_attention_heading' => '要確認（有効化前に確認してください）',
        'needs_attention_body' => '重要な指摘が :count 件あります。現在のセキュリティ設定では、有効化が制限される場合があります。',
        'needs_attention_action_reinstall' => '配布元のZIPを再取得し、再インストール後に再スキャンしてください。',
        'needs_attention_action_document' => '意図した仕様であれば permission を明示し、設計意図をドキュメント化してください。',

        // 未確認（NotVerified）
        'not_verified_heading' => '未確認（検証情報が不足しています）',
        'not_verified_body' => 'このプラグインは検証に必要な情報が不足しています。',
        'not_verified_action_scan' => '再スキャンを実行してください。',
        'not_verified_action_permission' => 'plugin.json に permission を定義してください。',
        'not_verified_action_signature' => '本番配布時は署名を付与してください。',

        // 指摘例
        'issue_permission_undeclared' => '権限の宣言不足：:permission（検出：:file::line）',
        'issue_permission_unused' => '未使用の権限が宣言されています：:permission',
        'issue_signature_unsigned' => '未署名：開発モードでは許可されています（本番配布時は署名を推奨）',
        'issue_signature_invalid' => '署名不一致：改ざんの可能性があります',
        'issue_dangerous_api' => '推奨されないAPIの利用を検出：:api（:file::line）',

        // 推奨アクション
        'recommended_actions' => '推奨アクション',
    ],

    // ブロック時の文言
    'block' => [
        'title' => 'このプラグインは現在のセキュリティ設定では有効化できません',
        'body' => '健全性チェックで「:status」と判定されました。',
        'action' => 'セキュリティ設定で許可範囲を変更するか、指摘事項を解消して再スキャンしてください。',
        'button_details' => '詳細を確認',
        'button_security' => 'セキュリティ設定へ',
        'button_cancel' => 'キャンセル',

        // CSP厳格モード
        'csp_strict_title' => 'CSP厳格モードでは有効化できません',
        'csp_strict_body' => 'このプラグインはインラインJavaScriptを必要とするため、CSP厳格モードでは動作しません。',
        'csp_strict_action' => 'CSPモードを「標準」または「開発」に変更するか、プラグインをCSP Readyに更新してください。',
    ],

    // CSP適合性
    'csp' => [
        'status_label' => 'CSP適合性',
        'ready' => 'CSP対応済み',
        'ready_tooltip' => 'このプラグインはCSP完全対応です。すべてのCSPモードで動作します。',
        'inline_required_tooltip' => 'このプラグインはインラインJavaScriptを必要とします。CSP厳格モードでは動作しません。',
        'compatible' => 'CSP互換',
        'compatible_tooltip' => 'このプラグインはnonce付きで動作します。標準モード以上で動作します。',
        'inline_required' => 'CSP未対応',
        'inline_required_tooltip' => 'このプラグインはインラインJavaScriptを必要とします。CSP厳格モードでは動作しません。',
        'not_checked' => '未検証',
        'not_checked_tooltip' => 'CSP適合性は検証されていません。',

        // CSP違反警告（CSP無効時でも表示）
        'violation_detected' => 'CSP違反が検出されました',
        'violation_count' => ':count件の違反',
        'violation_note_disabled' => 'CSPは現在無効ですが、有効化した場合に問題が発生する可能性があります。',
        'violation_note_dev' => '開発モードでは違反はログに記録されますが、ブロックされません。',
        'violation_note_standard' => '標準モードでは一部の機能が動作しない可能性があります。',
        'violation_note_strict' => '厳格モードではこのプラグインは有効化できません。',
    ],

    // コントローラーメッセージ
    'messages' => [
        'install_success' => 'プラグインが正常にインストールされました。',
        'install_success_no_plugin' => 'プラグインが正常にインストールされました。',
        'enable_here' => 'こちら',
        'enable_prompt' => 'プラグイン「:name」を有効化できます。',
        'install_failed' => 'プラグインのインストールに失敗しました: :error',
        'install_directory_not_found' => 'プラグインディレクトリが見つかりません。',
        'uninstall_success' => 'プラグインをアンインストールしました',
        'uninstall_failed' => 'プラグインのアンインストールに失敗しました: :error',
        'uninstall_must_disable_first' => '有効化中のプラグインはアンインストールできません。先に無効化してください。',
        'disable_success' => 'プラグインを無効化しました',
        'disable_failed' => 'プラグイン無効化中にエラーが発生しました: :error',
        'delete_success' => 'プラグインが正常に削除されました。',
        'delete_failed' => 'プラグインの削除に失敗しました: :error',
        'delete_uninstall_failed' => 'プラグインのアンインストールに失敗しました。',
        'delete_file_failed' => 'プラグインの削除に失敗しました。',
        'no_plugin_name' => 'プラグイン名なし',
    ],

    'capabilities' => [
        'title' => '提供機能',
        'description' => 'このプラグインが提供する機能の宣言です。コアや他プラグインがこの宣言を参照して機能を検出します。',
    ],

    'permissions' => [
        'health_status' => '健全性',
        'health_healthy' => '良好',
        'health_warning' => '注意',
        'health_needs_attention' => '要確認',
        'health_not_verified' => '未確認',
        'unknown' => '未定義',
        'unknown_warning' => '権限情報が定義されていません。このプラグインがどのような操作を行うか不明です。信頼できるソースから入手したことを確認してください。',
        'audit_mismatch_title' => '権限の不一致',
        'audit_mismatch_warning' => 'plugin.json で宣言された権限と実際のコードが一致しません。',
        'audit_undeclared_usage' => '未宣言の機能使用',
        'audit_unused_declaration' => '未使用の権限宣言',
        'install_warning_title' => 'インストール前の確認',
        'install_warning_notice' => 'このプラグインには以下の確認事項があります：',
        'install_warning_undefined' => '権限情報が未定義です',
        'install_warning_unsigned' => '署名されていません',
        'install_warning_mismatch' => '権限宣言とコードが一致しません',
        'install_warning_confirm' => '上記を理解した上でインストールしますか？',
        'install_warning_risk' => 'このプラグインには以下の注意点があります：',
        'risk_medium' => '中程度の健全性リスク',
        'risk_high' => '高い健全性リスク',
        'enable_warning_title' => '有効化前の確認',
        'enable_warning_message' => 'プラグイン「:name」には以下の注意点があります：',
        'enable_warning_confirm' => '上記を理解した上で有効化しますか？',
        'enable_confirm_simple' => 'プラグイン「:name」を有効化しますか？',
        'enable_warning_invalid_signature' => '署名が無効です（改ざんの可能性）',
        'enable_warning_needs_attention' => '確認が必要な権限を使用しています',
        'enable_warning_high_risk' => '高い健全性リスクがあります',
        'warning_not_scanned' => 'スキャンが実行されていません',
        'scan_recommendation' => '実行前にスキャンを行うことを推奨します。',
        'audit_button' => 'スキャン',
        'audit_button_rescan' => '再スキャン',
        'audit_scanning' => 'スキャン中...',
        'audit_scanning_title' => 'プラグインをスキャン中',
        'audit_scanning_description' => 'セキュリティスキャンを実行しています。<br>しばらくお待ちください。',
        'audit_not_scanned' => '未スキャン',
        'audit_last_scanned' => '最終スキャン',
        'audit_result_title' => 'スキャン結果',
        'audit_no_issues' => '問題は検出されませんでした',
        'audit_stats' => 'チェック項目',
        'audit_matches' => '一致',
        'audit_mismatches' => '不一致',
        'audit_mismatch_badge' => '不一致',
        'permission_consistency_title' => '権限定義の整合性',
        'total_risk_score' => 'リスクスコア合計',
        'no_permissions' => '権限情報が定義されていません',
        'details_title' => 'プラグイン詳細',
        'no_special_permissions' => '特別な権限はありません',
        'no_permissions_defined' => '権限情報が定義されていません。このプラグインの権限は不明です。',
        'signature_status' => '署名ステータス',
        'signature_official' => '公式',
        'signature_verified' => '認証済み',
        'signature_partner' => 'パートナー',
        'signature_signed' => '署名済み',
        'signature_invalid' => '署名無効',
        'signature_unsigned' => '未署名',
        'signature_pending_verification' => '検証待ち',
        'signature_unknown_key' => '不明な署名鍵',
        'signature_expired' => '鍵失効',
        'signature_error' => '検証エラー',
        'signature_invalid_warning' => '⚠️ このプラグインの署名は無効です。改ざんされている可能性があります。',
        'signature_unsigned_info' => 'このプラグインは署名されていません。信頼できるソースから入手したことを確認してください。',
        'signature_pending_verification_info' => 'このプラグインには署名がありますが、公開鍵サーバーとの照合がまだ完了していません。',
        'signature_unknown_key_info' => 'このプラグインの署名鍵は信頼済み鍵として登録されていません。配布元を確認してください。',
        'signature_expired_info' => 'このプラグインの署名に使用された鍵は失効しています。',
        'signature_error_info' => '署名検証中にエラーが発生しました。',
        'signed_by' => '署名者',
        'permission_info' => '権限情報',
        'category_database' => 'データベース',
        'category_storage' => 'ストレージ',
        'category_settings' => '設定',
        'category_members' => 'メンバー',
        'category_mail' => 'メール',
        'category_content' => 'コンテンツ',
        'category_system' => 'システム',
        'category_dangerous_api' => '危険なAPI',
        'category_csp' => 'CSP',
        'perm_own_tables' => '専用テーブル',
        'perm_core_tables_read' => 'コアテーブル（読取）',
        'perm_core_tables_write' => 'コアテーブル（書込）',
        'perm_own_directory' => '専用ディレクトリ',
        'perm_public_uploads' => '公開アップロード',
        'perm_temp_files' => '一時ファイル',
        'perm_read_core' => 'コア設定読取',
        'perm_write_own' => '自己設定書込',
        'perm_read' => '読み取り',
        'perm_write' => '書き込み',
        'perm_create' => '作成',
        'perm_delete' => '削除',
        'perm_send' => '送信',
        'perm_bulk_send' => '一括送信',
        'perm_read_other_plugins' => '他プラグイン読取',
        'perm_write_other_plugins' => '他プラグイン書込',
        'perm_register_shortcodes' => 'ショートコード',
        'perm_register_middleware' => 'ミドルウェア',
        'perm_register_commands' => 'コマンド',
        'perm_register_blade_directives' => 'Blade指令',
        'perm_modify_routes' => 'ルート変更',
        'perm_exec' => 'コマンド実行',
        'perm_env_access' => '環境変数アクセス',
        'perm_file_write' => 'ファイル書き込み',
        'perm_network' => '外部通信',
        'perm_external_resources' => '外部リソース',
        'total_evaluation' => '総合評価',
        'health_score_display' => 'スコア: :score/100',
        'signature_deduction' => '（減点: -:points）',
        'health_issue_signature_unsigned' => '署名: 未署名',
        'health_issue_signature_invalid' => '署名: 無効',
        'health_issue_missing_author_id' => 'メタデータ: author_id 未定義',
        'health_issue_missing_authority_key_id' => 'メタデータ: authority_key_id 未定義',
        'health_issue_permission_undefined' => '権限: 未定義',
        'health_issue_permission_undeclared_minor' => '権限: 未宣言の使用（軽微）',
        'health_issue_permission_undeclared_major' => '権限: 未宣言の使用（重大）',
        'health_issue_permission_unused' => '権限: 未使用の宣言',
        'health_issue_csp_inline_css_required' => 'CSP: インラインCSS必須',
        'health_issue_csp_inline_js_required' => 'CSP: インラインJS必須',
        'health_issue_csp_violation_strict' => 'CSP: 違反（厳格モード）',
        'health_issue_csp_violation_standard' => 'CSP: 違反（標準モード）',
        'health_issue_dangerous_api_exec' => '危険なAPI検出',
        'health_issue_risk_public_uploads_own_dir' => 'ストレージ: 専用ディレクトリ内で公開アップロード',
        'health_issue_risk_public_uploads_no_own_dir' => 'ストレージ: 公開ディレクトリへ直接アップロード',
        'health_issue_risk_members_delete' => '権限: メンバー削除',
        'health_issue_risk_mail_bulk_send' => '権限: メール一括送信',
        'health_issue_scan_not_performed' => 'スキャン: 未実行',
        'health_issue_scan_outdated' => 'スキャン: 期限切れ',
        'health_status_healthy' => '良好',
        'health_status_advisory' => '注意',
        'health_status_needs_attention' => '要確認',
        'health_status_not_verified' => '未検証',
        'signature_section_label' => '署名',
        'attention_reasons_title' => '確認が必要な理由',
        'attention_reason_members_write' => 'メンバー情報の書き込み権限を使用します',
        'attention_reason_members_create' => '新規メンバーの作成権限を使用します',
        'attention_reason_members_delete' => 'メンバーの削除権限を使用します',
        'attention_reason_mail_bulk_send' => '一括メール送信権限を使用します',
        'attention_reason_storage_public_uploads' => '公開ディレクトリへのアップロード権限を使用します',
        'attention_reason_storage_public_uploads_own_dir' => '専用ディレクトリ内で公開アップロードを使用します',
        'attention_reason_storage_public_uploads_no_own_dir' => '公開ディレクトリへ直接アップロードします',
        'attention_reason_content_write_other_plugins' => '他プラグインへの書き込み権限を使用します',
        'attention_reason_mail_send' => 'メール送信権限を使用します',
        'attention_reason_settings_read_core' => 'コア設定の読み取り権限を使用します',
        'attention_reason_system_register_middleware' => 'ミドルウェアの登録権限を使用します',
        'attention_reason_database_core_tables_read' => 'コアテーブルの読み取り権限を使用します',
        'attention_reason_database_core_tables_write' => 'コアテーブルへの書き込み権限を使用します',
        'attention_reason_undeclared_usage' => '未宣言の権限を使用しています',
        'attention_reason_mismatch_undeclared_usage' => '未宣言の権限使用が検出されました（:count件）',
        'attention_reason_system_modify_routes' => 'ルートの変更権限を使用します',
        'csp_status' => 'CSP対応',
        'csp_compliant' => '対応済み',
        'csp_not_compliant' => '未対応',
        'csp_issues_found' => ':count件の問題',
        'csp_inline_scripts' => 'インラインスクリプト',
        'csp_inline_styles' => 'インラインスタイル',
        'csp_event_handlers' => 'イベントハンドラ',
        'csp_javascript_urls' => 'JavaScript URL',
        'csp_section_label' => 'CSP対応',
        'csp_violation_inline_script' => 'インラインスクリプト',
        'csp_violation_inline_style' => 'インラインスタイル',
        'csp_violation_event_handler' => 'イベントハンドラ',
        'csp_violation_javascript_url' => 'JavaScript URL',
        'csp_warning_title' => 'CSP非対応の警告',
        'csp_warning_message' => 'このプラグインにはCSP非対応のインラインスクリプト/スタイルが含まれています。CSPを強制モードで有効にすると、一部の機能が動作しない可能性があります。',
        'csp_fix_suggestion' => '修正方法: <script> を <script @cspNonce> に、<style> を <style @cspNonce> に変更してください。',
    ],

    // 2段階モーダルフロー
    'two_stage' => [
        'stage1_scan_required_title' => 'セキュリティスキャンが必要です',
        'stage1_scan_required_message' => 'このプラグインを:actionするにはスキャンが必要です。',
        'stage1_scan_optional_title' => 'スキャンしますか？',
        'stage1_scan_optional_message' => ':actionの前にこのプラグインをスキャンしますか？',
        'stage1_scanning' => 'プラグインをスキャン中',
        'stage1_scanning_description' => 'セキュリティスキャンを実行しています。<br>しばらくお待ちください。',
        'stage1_skip_scan' => 'スキップ',
        'stage1_start_scan' => 'スキャン開始',
        'stage2_confirm_install' => 'このプラグインをインストールしますか？',
        'stage2_confirm_enable' => 'このプラグインを有効化しますか？',
        'stage2_blocked_title' => ':actionできません',
        'stage2_blocked_message' => 'このプラグインはセキュリティ要件を満たしていないため、:actionできません。',
        'stage2_scan_result_heading' => ':action前の確認',
        'stage2_warning_message' => 'このプラグインには以下の警告があります:',
        'action_install' => 'インストール',
        'action_enable' => '有効化',
        'action_installed' => 'インストール',
        'action_enabled' => '有効化',
        'install_blocked' => 'このプラグインはセキュリティ要件を満たしていないため、インストールできません。',
        'stage2_confirm_action_message' => ':actionを実行しますか？',
        'processing_install' => 'プラグインをインストール中',
        'processing_enable' => 'プラグインを有効化中',
        'processing_install_description' => 'プラグインをインストールしています。<br>しばらくお待ちください。',
        'processing_enable_description' => 'プラグインを有効化しています。<br>しばらくお待ちください。',
    ],
];
