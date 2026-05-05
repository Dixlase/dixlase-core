<?php

return [
    'If --force option is specified, only run migrations and seeders' => '--forceオプションが指定されている場合は、マイグレーションとシーダーのみ実行',
    'Run migrations' => 'マイグレーションを実行',
    'Run seeders' => 'シーダーを実行',
    'Read theme information (in order: theme.json → composer.json → default values)' => 'テーマ情報を読み取る（theme.json → composer.json → デフォルト値の順）',
    '1. Read from theme.json (highest priority)' => '1. theme.jsonから読み取り（最優先）',
    '2. Fallback to composer.json' => '2. composer.jsonからフォールバック',
    'Get display-name from extra.dixlase' => 'display-nameをextra.dixlaseから取得',
    'Get namespace from autoload in composer.json' => 'namespaceはcomposer.jsonのautoloadから取得',
    'Get information from authors array' => 'authors配列から情報を取得',
    'Get version from extra.dixlase.version, or use root version if not present' => 'versionはextra.dixlase.versionから取得、なければルートのもの',
    'Set default values' => 'デフォルト値の設定',
    'Check for theme settings page' => 'テーマ設定ページの有無をチェック',
    'Update composer.local.json' => 'composer.local.jsonを更新',
    'Run migrations (using ThemeMigrator)' => 'マイグレーションを実行（ThemeMigratorを使用）',
    'Set command instance' => 'コマンドインスタンスをセット',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'If --force option is specified, only run migrations and seeders' => 'machine',
        'Run migrations' => 'machine',
        'Run seeders' => 'machine',
        'Read theme information (in order: theme.json → composer.json → default values)' => 'machine',
        '1. Read from theme.json (highest priority)' => 'machine',
        '2. Fallback to composer.json' => 'machine',
        'Get display-name from extra.dixlase' => 'machine',
        'Get namespace from autoload in composer.json' => 'machine',
        'Get information from authors array' => 'machine',
        'Get version from extra.dixlase.version, or use root version if not present' => 'machine',
        'Set default values' => 'machine',
        'Check for theme settings page' => 'machine',
        'Update composer.local.json' => 'machine',
        'Run migrations (using ThemeMigrator)' => 'machine',
        'Set command instance' => 'machine',
    ],
];
