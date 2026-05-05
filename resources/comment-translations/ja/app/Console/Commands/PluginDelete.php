<?php

return [
    'Check if plugin directory exists' => 'プラグインディレクトリの存在チェック',
    'Check if registered in database (whether uninstalled or not)' => 'データベースに登録されているかチェック（アンインストール済みかどうか）',
    'Confirmation prompt' => '確認プロンプト',
    'Delete directory' => 'ディレクトリを削除',
    'Remove plugin exclusion rule from .git/info/exclude' => '.git/info/excludeからプラグインの除外ルールを削除',
    'Remove plugin exclusion rule from .gitignore' => '.gitignoreからプラグインの除外ルールを削除',
    'Update composer.local.json' => 'composer.local.jsonを更新',
    'Note: Only update composer.local.json, keep composer.json in pristine state' => '注意: composer.local.jsonのみ更新し、composer.jsonは素の状態を保持',
    'Reflect autoload changes by manually running `composer dump-autoload`' => 'オートロードの反映は `composer dump-autoload` で手動実行',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Check if plugin directory exists' => 'machine',
        'Check if registered in database (whether uninstalled or not)' => 'machine',
        'Confirmation prompt' => 'machine',
        'Delete directory' => 'machine',
        'Remove plugin exclusion rule from .git/info/exclude' => 'machine',
        'Remove plugin exclusion rule from .gitignore' => 'machine',
        'Update composer.local.json' => 'machine',
        'Note: Only update composer.local.json, keep composer.json in pristine state' => 'machine',
        'Reflect autoload changes by manually running `composer dump-autoload`' => 'machine',
    ],
];
