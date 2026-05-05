<?php

return [
    'Admin panel navigation manager interface' => '管理画面ナビゲーション管理インターフェース',
    'Abstraction layer for merging plugin navigation settings into Core navigation' => 'プラグインのナビゲーション設定をコアのナビゲーションにマージするための抽象レイヤー。',
    'Eliminates direct dependency on AdminHelper and enables SDK separation' => 'AdminHelper への直接依存を排除し、SDK分離を可能にする。',
    'Merge new-structure navigation file (config/admin/navigation.php)' => '新構造のナビゲーションファイル (config/admin/navigation.php) をマージ',
    'Does nothing if the file does not exist' => 'ファイルが存在しない場合は何もしない。',
    'Plugin navigation settings file path' => 'プラグインのナビゲーション設定ファイルパス',
    'Merge old-structure navigation settings (nav key in admin.php)' => '旧構造のナビゲーション設定 (admin.php の nav キー) をマージ',
    'Does nothing if the file does not exist or the nav key is not present' => 'ファイルが存在しない場合、または nav キーがない場合は何もしない。',
    'Plugin admin settings file path' => 'プラグインの admin 設定ファイルパス',
    'Supports ordering via _insert_before / _insert_after.' => '_insert_before / _insert_after による順序制御をサポートする。',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_insert_before / _insert_after による順序制御をサポートする。' => '',
    '_review_status' => [
        'Admin panel navigation manager interface' => 'machine',
        'Abstraction layer for merging plugin navigation settings into Core navigation' => 'machine',
        'Eliminates direct dependency on AdminHelper and enables SDK separation' => 'machine',
        'Merge new-structure navigation file (config/admin/navigation.php)' => 'machine',
        'Does nothing if the file does not exist' => 'machine',
        'Plugin navigation settings file path' => 'machine',
        'Merge old-structure navigation settings (nav key in admin.php)' => 'machine',
        'Does nothing if the file does not exist or the nav key is not present' => 'machine',
        'Plugin admin settings file path' => 'machine',
        'Supports ordering via _insert_before / _insert_after.' => 'human',
    ],
];
