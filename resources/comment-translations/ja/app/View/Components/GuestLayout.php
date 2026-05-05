<?php

return [
    'Use default values before installation or in case of database connection error' => 'インストール前やデータベース接続エラーの場合はデフォルト値を使用',
    'Fall back to default if settings table does not exist (e.g. immediately after installation)' => '設定テーブル未作成（インストール直後など）はデフォルトにフォールバック',
    'Retrieve via SettingResolver (site_name is PerSite, admin_theme is Global)' => 'SettingResolver 経由で取得 (site_name は PerSite, admin_theme は Global)',
    'Use default values in case of database connection error, etc.' => 'データベース接続エラー等はデフォルト値を使用',
    'Set the theme class' => 'テーマクラスを設定する',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Use default values before installation or in case of database connection error' => 'machine',
        'Fall back to default if settings table does not exist (e.g. immediately after installation)' => 'machine',
        'Retrieve via SettingResolver (site_name is PerSite, admin_theme is Global)' => 'machine',
        'Use default values in case of database connection error, etc.' => 'machine',
        'Set the theme class' => 'machine',
    ],
];
