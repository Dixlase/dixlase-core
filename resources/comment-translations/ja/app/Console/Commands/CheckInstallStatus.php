<?php

return [
    '1. INSTALLED status' => '1. INSTALLED状態',
    '2. Database connection' => '2. データベース接続',
    '3. migrations table' => '3. migrationsテーブル',
    'Display the 5 most recent migrations' => '最近のマイグレーション5件を表示',
    '4. Main tables' => '4. 主要テーブル',
    '5. Administrator user' => '5. 管理者ユーザー',
    '7. Overall determination' => '7. 総合判定',
    'Basic check' => '基本チェック',
    'Skip if migrations table does not exist (when SQL is executed directly)' => 'migrationsテーブルが存在しない場合はスキップ（直接SQL実行の場合）',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        '1. INSTALLED status' => 'machine',
        '2. Database connection' => 'machine',
        '3. migrations table' => 'machine',
        'Display the 5 most recent migrations' => 'machine',
        '4. Main tables' => 'machine',
        '5. Administrator user' => 'machine',
        '7. Overall determination' => 'machine',
        'Basic check' => 'machine',
        'Skip if migrations table does not exist (when SQL is executed directly)' => 'machine',
    ],
];
