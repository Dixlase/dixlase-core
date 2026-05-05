<?php

return [
    'Read plugin information (priority: plugin.json → composer.json → default values)' => 'プラグイン情報を読み取る（plugin.json → composer.json → デフォルト値の順）',
    '1. Read from plugin.json (highest priority)' => '1. plugin.jsonから読み取り（最優先）',
    '2. Fallback to composer.json' => '2. composer.jsonからフォールバック',
    'Get author information' => '作者情報の取得',
    'Register in database' => 'データベースに登録',
    'Get from composer.json' => 'composer.json から取得',
    'Run migrations' => 'マイグレーションを実行',
    'Run seeder (only if DatabaseSeeder exists)' => 'シーダーを実行（DatabaseSeederが存在する場合のみ）',
    'Note: Updating composer.local.json and .git/info/exclude' => '注意: composer.local.jsonと.git/info/excludeの更新は、',
    'is already done during plugin creation (make:plugin), so not needed here' => 'プラグイン作成時（make:plugin）に既に行われているため、ここでは不要',
    'Confirm plugin activation (only if --enable option is not specified)' => 'プラグインの有効化を確認（--enable オプションが指定されていない場合のみ確認）',
    'When running via web, interactive input is not possible, so judge only by presence of --enable option' => 'Web経由での実行時は対話的入力ができないため、--enableオプションの有無のみで判断',
    'Show confirmation prompt only when running from CLI' => 'CLIからの実行時のみ確認プロンプトを表示',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Read plugin information (priority: plugin.json → composer.json → default values)' => 'machine',
        '1. Read from plugin.json (highest priority)' => 'machine',
        '2. Fallback to composer.json' => 'machine',
        'Get author information' => 'machine',
        'Register in database' => 'machine',
        'Get from composer.json' => 'machine',
        'Run migrations' => 'machine',
        'Run seeder (only if DatabaseSeeder exists)' => 'machine',
        'Note: Updating composer.local.json and .git/info/exclude' => 'machine',
        'is already done during plugin creation (make:plugin), so not needed here' => 'machine',
        'Confirm plugin activation (only if --enable option is not specified)' => 'machine',
        'When running via web, interactive input is not possible, so judge only by presence of --enable option' => 'machine',
        'Show confirmation prompt only when running from CLI' => 'machine',
    ],
];
