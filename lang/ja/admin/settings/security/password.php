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
    'heading' => 'パスワード設定',
    'description' => 'パスワードセキュリティの設定を行います。',
    'session_management' => 'セッション管理設定',
    'session_management_description' => 'システム全体のセッション設定を管理します。Dixlaseはガード別セッション管理（Guard-Aware Database）を使用しています。',
    'session_encrypt' => 'セッション暗号化',
    'session_encrypt_help' => 'セッションデータを暗号化して保存します。',
    'session_lifetime' => 'デフォルトセッション有効時間',
    'session_lifetime_help' => 'セッションの有効時間を分単位で設定してください（1-43200分）。',
    'password_security_settings' => 'パスワードセキュリティ設定',
    'password_security_description' => 'パスワードに関するセキュリティ設定を管理します。',

    // パスワード漏洩チェック
    'pwned_password_check' => 'パスワード漏洩チェック',
    'pwned_password_check_description' => 'パスワード設定時に漏洩データベースとの照合を行います。',
    'pwned_password_api_info' => 'このチェックはHave I Been Pwned APIを使用します。パスワード自体は送信されず、SHA-1ハッシュの先頭5文字のみが使用されるため安全です。',
    'pwned_settings' => 'パスワード辞書攻撃対策設定',
    'pwned_password_settings' => 'パスワード辞書攻撃対策設定',
    'pwned_password_description' => 'パスワードが漏洩データベースに含まれていないかをチェックし、安全でないパスワードの使用を防ぎます。',
    'pwned_password_check_enabled' => '辞書攻撃対策',
    'pwned_password_help' => '有効にすると、メンバー作成・編集・パスワード変更時にHave I Been Pwned APIを使用してパスワードの安全性をチェックします。',

    // デフォルトパスワードポリシー
    'default_password_policy' => 'デフォルトパスワードポリシー',
    'default_password_policy_description' => 'メンバーおよびプラグインでカスタム設定が無効な場合に適用されるデフォルトのパスワード要件です。',
    'conditions' => 'パスワード条件',
    'password_requirements' => 'パスワード要件',
    'min_length' => '最小文字数',
    'require_uppercase' => '大文字を含める',
    'require_number' => '数字を含める',
    'require_symbol' => '記号を含める',
    'security_warning' => 'セキュリティ強化のため、より厳しい条件を設定することを推奨します。',
    'reset_enabled' => 'パスワードリセット機能を有効化',
    'plugin_custom_hint' => 'プラグイン（DixlaseUsersなど）で独自のパスワードポリシーを設定できます。プラグインでカスタム設定が有効な場合、そちらが優先されます。',

    // パスワードリセット機能
    'password_reset_feature' => 'パスワードリセット機能',
    'password_reset_feature_description' => 'メンバーがパスワードを忘れた場合のリセット機能の有効/無効を設定します。',

    'settings_updated' => 'パスワードセキュリティ設定が更新されました。',
];
