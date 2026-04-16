<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

return [
    'heading' => 'プラグイン詳細',
    'description' => 'プラグインの詳細情報とスキャン結果を確認します。',
    'back_to_list' => 'プラグイン一覧に戻る',
    'back_to_add' => 'プラグインを追加に戻る',
    'update_available' => 'が利用可能',
    'online_badge' => 'オンライン',
    'repository' => 'リポジトリ',
    'last_updated' => '最終更新',

    // メタデータラベル
    'author' => '作者',
    'license' => 'ライセンス',
    'slug' => 'スラッグ',
    'package_name' => 'パッケージ名',
    'namespace' => '名前空間',
    'directory' => 'ディレクトリ',
    'email' => 'メールアドレス',
    'url' => 'ウェブサイト',

    // セクション
    'sections' => [
        'description' => '説明',
        'details' => '詳細情報',
        'scan_result' => 'スキャン結果',
        'scan_details' => 'スキャン詳細',
        'csp_compatibility' => 'CSPモード互換性',
        'preset_compatibility' => 'セキュリティプリセット互換性',
    ],

    // スキャン
    'scan' => [
        'signature' => '署名',
        'permission' => '権限',
        'csp' => 'CSP',
        'operation' => '動作',
        'issues' => '検出された指摘事項',
        'scan' => 'スキャン',
        'rescan' => '再スキャン',
        'scanning' => 'スキャン中...',
        'not_scanned_message' => 'このプラグインはまだスキャンされていません。スキャンを実行してセキュリティと権限を確認してください。',
    ],

    'last_scanned_at' => '最終スキャン: :date',

    // アクション
    'actions' => [
        'install' => 'インストール',
        'uninstall' => 'アンインストール',
        'enable' => '有効化',
        'disable' => '無効化',
        'delete' => '削除',
        'settings' => '設定',
    ],
];
