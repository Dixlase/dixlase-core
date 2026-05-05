<?php

return [
    'Theme repository interface' => 'テーマリポジトリインターフェース',
    'Abstraction layer for retrieving activated theme information' => '有効化されたテーマの情報を取得するための抽象レイヤー。',
    'Eliminates direct dependencies on Theme Eloquent models and DB facades, enabling SDK separation' => 'Theme Eloquent モデルや DB ファサードへの直接依存を排除し、SDK分離を可能にする。',
    'Retrieve active theme ID' => '有効なテーマIDを取得',
    'Returns default value 1 if theme_settings table does not exist' => 'theme_settingsテーブルが存在しない場合はデフォルト値 1 を返す。',
    'Retrieve active theme directory name' => '有効なテーマのディレクトリ名を取得',
    'Falls back to config(\'themes.default_theme\') if theme is not found' => 'テーマが見つからない場合は config(\'themes.default_theme\') にフォールバックする。',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Theme repository interface' => 'machine',
        'Abstraction layer for retrieving activated theme information' => 'machine',
        'Eliminates direct dependencies on Theme Eloquent models and DB facades, enabling SDK separation' => 'machine',
        'Retrieve active theme ID' => 'machine',
        'Returns default value 1 if theme_settings table does not exist' => 'machine',
        'Retrieve active theme directory name' => 'machine',
        'Falls back to config(\'themes.default_theme\') if theme is not found' => 'machine',
    ],
];
