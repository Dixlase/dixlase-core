<?php

return [
    'Permission settings override table' => '権限設定のオーバーライドテーブル',
    'Only stores settings that differ from default permissions (config/roles.php)' => 'デフォルト権限（config/roles.php）と異なる設定のみを保存',
    'Source type: core / plugin' => 'ソース種別: core / plugin',
    'Source ID: null for core, slug for plugin' => 'ソースID: coreはnull、pluginはslug',
    'Menu key (e.g., settings.base, pages.index)' => 'メニューキー（例：settings.base, pages.index）',
    'Edit permission (users with this permission level or higher can access)' => '編集権限（この値以上の権限を持つユーザーがアクセス可能）',
    'View permission (users with this permission level or higher can view)' => '閲覧権限（この値以上の権限を持つユーザーが閲覧可能）',
    'Updater (foreign key constraint added in add_foreign_key_constraints)' => '更新者（外部キー制約は add_foreign_key_constraints で追加）',
    'Unique on site + source_type + source_id + menu_key' => 'site + source_type + source_id + menu_key でユニーク',
    'Index' => 'インデックス',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Permission settings override table' => 'machine',
        'Only stores settings that differ from default permissions (config/roles.php)' => 'machine',
        'Source type: core / plugin' => 'machine',
        'Source ID: null for core, slug for plugin' => 'machine',
        'Menu key (e.g., settings.base, pages.index)' => 'machine',
        'Edit permission (users with this permission level or higher can access)' => 'machine',
        'View permission (users with this permission level or higher can view)' => 'machine',
        'Updater (foreign key constraint added in add_foreign_key_constraints)' => 'machine',
        'Unique on site + source_type + source_id + menu_key' => 'machine',
        'Index' => 'machine',
    ],
];
