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
    'heading' => 'API管理',
    'general_settings' => '基本設定',
    'api_enabled' => 'APIを有効にする',
    'api_enabled_help' => '外部システムからのAPI経由でのアクセスを許可します。',
    'signature_required' => '署名検証',
    'signature_required_help' => 'APIリクエストに署名検証を必須とします。セキュリティ向上のため有効にすることを推奨します。',
    'default_rate_limit' => 'デフォルトレート制限',
    'rate_limit_help' => 'APIキーごとに個別設定がない場合に適用されるデフォルトのレート制限です。',
    'requests_per_minute' => 'リクエスト/分',
    'update_success' => 'API設定を更新しました。',
    
    // APIキー管理
    'api_keys' => 'APIキー管理',
    'create_key' => '新規APIキー作成',
    'no_keys' => 'APIキーがありません。「新規APIキー作成」ボタンからキーを作成してください。',
    'key_name' => 'キー名',
    'key_name_placeholder' => '例: 外部システム連携用',
    'key_prefix' => 'キープレフィックス',
    'environment' => '環境',
    'env_live_desc' => '本番環境用',
    'env_test_desc' => 'テスト・開発用',
    'status' => 'ステータス',
    'last_used' => '最終使用',
    'never_used' => '未使用',
    'usage_count' => '使用回数',
    'expired' => '期限切れ',
    'scopes' => '権限スコープ',
    'no_scopes' => 'スコープなし',
    'rate_limit' => 'レート制限',
    'unlimited' => '無制限',
    'allowed_ips' => '許可IPアドレス',
    'allowed_ips_placeholder' => '例: 192.168.1.1, 10.0.0.0',
    'allowed_ips_help' => 'カンマ区切りで入力。空欄の場合は全IP許可。',
    'all_ips_allowed' => '全IP許可',
    'expires_at' => '有効期限',
    'expires_at_help' => '空欄の場合は無期限。',
    'no_expiry' => '無期限',
    'description' => '説明',
    'description_placeholder' => 'このAPIキーの用途を記載',
    'created_at' => '作成日時',
    'generate' => 'キーを生成',
    'regenerate' => '再生成',
    'regenerate_confirm' => 'このAPIキーを再生成しますか？現在のキーは無効になります。',
    'revoke_confirm' => 'このAPIキーを削除しますか？この操作は取り消せません。',
    'key_details' => 'APIキー詳細',
    
    // 成功メッセージ
    'key_generated' => 'APIキーを生成しました。',
    'key_generated_warning' => '重要: このAPIキーは一度だけ表示されます',
    'key_generated_warning_detail' => 'このキーを安全な場所に保存してください。ページを離れると二度と表示できません。',
    'key_regenerated' => 'APIキーを再生成しました。',
    'key_revoked' => 'APIキーを削除しました。',
    'copied_to_clipboard' => 'クリップボードにコピーしました。',
];
