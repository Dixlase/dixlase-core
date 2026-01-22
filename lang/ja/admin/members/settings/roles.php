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
    'heading' => '権限設定',
    'description' => 'メンバーの権限とアクセス制御を設定します。',
    
    'core_permissions' => 'コア機能の権限',
    'core_permissions_description' => 'Dixlaseのコア機能に対するアクセス権限を設定します。',
    'excluded_items_note' => '※ ダッシュボードとプロフィールは全員がアクセス可能なため、権限設定から除外されています。',
    
    'plugin_permissions' => 'プラグインの権限',
    'plugin_permissions_description' => 'インストール済みプラグインに対するアクセス権限を設定します。',
    
    'access_roles' => '編集権限',
    'access_roles_help' => 'この機能を編集できる最低権限を設定します。',
    
    'view_roles' => '閲覧権限',
    'view_roles_help' => 'この機能を閲覧できる最低権限を設定します。',
    
    'overridden_from_default' => 'デフォルト値から変更されています',
    'reset_to_default' => 'デフォルトに戻す',
    
    // 権限キーのラベル（ナビゲーションにないもの）
    'permission_labels' => [
        'create_edit' => '新規作成・編集',
    ],
    
    // バリデーション
    'validation' => [
        'access_must_be_greater_than_view' => '編集権限は閲覧権限以上である必要があります（:menu_key）',
    ],
];
