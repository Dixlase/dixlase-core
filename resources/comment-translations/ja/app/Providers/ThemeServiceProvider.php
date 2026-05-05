<?php

return [
    'Skip if .env file does not exist or database connection fails' => '.envファイルが存在しない場合やデータベース接続ができない場合はスキップ',
    'Register theme ServiceProvider' => 'テーマのServiceProviderを登録',
    'Register if providers are defined in theme.json' => 'theme.jsonにprovidersが定義されていれば登録する',
    'Load theme settings file' => 'テーマの設定ファイルを読み込む',
    'Merge into navigation if config/admin/navigation.php exists' => 'config/admin/navigation.php が存在すればナビゲーションにマージ',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Skip if .env file does not exist or database connection fails' => 'machine',
        'Register theme ServiceProvider' => 'machine',
        'Register if providers are defined in theme.json' => 'machine',
        'Load theme settings file' => 'machine',
        'Merge into navigation if config/admin/navigation.php exists' => 'machine',
    ],
];
