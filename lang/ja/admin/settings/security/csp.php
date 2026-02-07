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
    'heading' => 'コンテンツセキュリティポリシー',
    'title' => 'コンテンツセキュリティポリシー（CSP）',
    'description' => 'CSPはブラウザに対してどのリソースを読み込み・実行してよいかを指示するセキュリティ機能です。XSS攻撃や不正なスクリプト実行を防ぎます。',
    'enabled' => 'CSPを有効にする',
    'enabled_help' => 'Content-Security-Policyヘッダーをレスポンスに付与します。',
    'mode' => 'CSPモード',
    'mode_help' => 'サイトのセキュリティレベルと開発のしやすさのバランスを選択してください。',
    'recommended' => '推奨',
    'mode_development' => '開発モード',
    'mode_development_desc' => 'プラグイン・テーマ開発時に最適。すべてのスクリプトが動作し、違反はログに記録されます。',
    'mode_development_feature1' => 'インラインJS・CSS、onclick等すべて許可',
    'mode_development_feature2' => 'Report-Onlyモードで違反を記録',
    'mode_development_feature3' => '開発完了後は標準モードへの移行を推奨',
    'mode_standard' => '標準モード',
    'mode_standard_desc' => '本番環境に推奨。nonce付きインラインのみ許可し、セキュリティと互換性のバランスを取ります。',
    'mode_standard_feature1' => '素の<script>タグはブロック',
    'mode_standard_feature2' => '@dixScript等のヘルパー経由ならOK',
    'mode_standard_feature3' => 'ほとんどのプラグイン・テーマが動作',
    // 'mode_strict' => '厳格モード', // 初期バージョンでは未実装
    // 'mode_strict_desc' => '最高レベルのセキュリティ。インラインスクリプトを一切許可しません。',
    // 'mode_strict_feature1' => 'インラインJS・CSS完全禁止',
    // 'mode_strict_feature2' => 'CSP Ready プラグイン・テーマのみ動作',
    // 'mode_strict_feature3' => 'requires_inline_js: true のプラグインは有効化不可',
    'log_violations' => '違反をログに記録',
    'log_violations_help' => 'CSP違反をログファイル（csp_violations.log）に記録します。',
    'exclude_dev_tools' => '開発ツールの違反を除外',
    'exclude_dev_tools_help' => 'Vite開発サーバー、Windsurf/MCPブラウザプレビュー等の開発ツールによるCSP違反をログから除外します。',
    'trusted_domains' => '信頼済みドメイン',
    'trusted_domains_help' => '外部リソースの読み込みを許可するドメインを1行に1つずつ入力してください。プラグインやテーマが必要とする外部CDN等を追加できます。',
    'trusted_domains_placeholder' => 'https://cdn.example.com
https://fonts.googleapis.com
https://api.example.com',
    'denied_domains' => '拒否ドメイン',
    'denied_domains_help' => '外部リソースの読み込みを<strong>常にブロック</strong>するドメインを1行に1つずつ入力してください。<br>プラグインやテーマがこれらのドメインを使用しようとしても、CSPによりブロックされます。<br>ワイルドカード（例: <code>*.example.com</code>）も使用できます。',
    'denied_domains_placeholder' => 'google-analytics.com
*.doubleclick.net
tracking.example.com',
    'blocklist_check_title' => 'ブロックリスト照合',
    'blocklist_check_description' => 'プラグイン・テーマのインストール・有効化時に、外部ドメインが既知の危険なドメインリストに含まれていないかチェックします。',
    'blocklist_check_enabled' => 'ブロックリスト照合を有効にする',
    'blocklist_action_label' => '検出時のアクション:',
    'blocklist_action_warn' => '警告のみ',
    'blocklist_action_warn_desc' => '（警告を表示するが、インストール・有効化は許可）',
    'blocklist_action_block' => 'ブロック',
    'blocklist_action_block_desc' => '（インストール・有効化を拒否）',
    'blocklist_check_categories' => '照合するカテゴリ:',
    'blocklist_sources_show' => '取得先URLを表示',
    'blocklist_warning_title' => '危険なドメインが検出されました',
    'blocklist_warning_message' => 'この拡張機能は以下の危険なドメインを使用しています:',
    'blocklist_blocked_title' => 'インストールがブロックされました',
    'blocklist_blocked_message' => 'この拡張機能は危険なドメインを使用しているため、インストールできません:',
    'custom_directives' => 'カスタムディレクティブ',
    'custom_directives_help' => '高度な設定が必要な場合、JSON形式でカスタムディレクティブを指定できます。',
    'custom_directives_placeholder' => '{"script-src": ["https://example.com"], "connect-src": ["https://api.example.com"]}',
    'what_is_csp' => 'CSPとは？',
    'what_is_csp_description' => 'Content Security Policy（CSP）は、Webページで実行できるスクリプトや読み込めるリソースを制限するセキュリティ機能です。これにより、XSS（クロスサイトスクリプティング）攻撃やデータ漏洩のリスクを大幅に軽減できます。',
    'nonce_explanation' => 'Dixlaseはnonce（使い捨てトークン）方式を採用しており、許可されたインラインスクリプトのみが実行されます。',
    'badge_csp_ready' => 'CSP Ready',
    'badge_inline_required' => 'Inline JS Required',
    'badge_csp_ready_tooltip' => 'このプラグインはCSPに完全対応しています',
    'badge_inline_required_tooltip' => 'このプラグインはインラインJSを必要とします。厳格モードでは使用できません。',
    // 'strict_mode_blocked' => '厳格モードでは有効化できません', // 初期バージョンでは未実装
    // 'strict_mode_blocked_reason' => 'このプラグインはインラインJSを必要とするため、CSP厳格モードでは有効化できません。',
    'development_mode_warning' => 'CSP開発モードはすべてのスクリプトを許可するため、セキュリティリスクがあります。本番環境では標準モードの使用を推奨します。',
    'settings_updated' => 'CSP設定が更新されました。',
    
    // 確認モーダル
    'confirmation_modal_title' => 'CSP設定の確認',
    'confirmation_modal_message' => 'CSP設定を変更しました。<br><br>画面が正常に表示されていれば<strong>「この設定を使う」</strong>ボタンを押してください。<br><br><strong class="text-red-600 dark:text-red-400">:seconds秒後に自動的に元の設定に戻ります。</strong>',
    'confirmation_modal_confirm' => 'この設定を使う',
    'confirmation_modal_cancel' => '元に戻す',
    'settings_confirmed' => 'CSP設定が確定されました。',
    'settings_rolled_back' => 'CSP設定を元に戻しました。',
    'no_previous_settings' => '前回の設定が見つかりません。',
    'rollback_warning' => '設定を確認しないと:seconds秒後に自動的に元の設定に戻ります。',
    
    // セーフモード
    'safe_mode_banner_title' => '⚠️ CSPセーフモードが有効です',
    'safe_mode_banner_message' => 'CSPが無効化されています。セキュリティリスクがあるため、設定完了後は必ずセーフモードを解除してください。',
    'safe_mode_go_to_settings' => 'CSP設定へ',
    'safe_mode_disable' => 'セーフモード解除',
    'safe_mode_disabled' => 'CSPセーフモードを解除しました。',
    'safe_mode_admin_only' => 'CSPセーフモードは管理者のみ使用できます。',
];
