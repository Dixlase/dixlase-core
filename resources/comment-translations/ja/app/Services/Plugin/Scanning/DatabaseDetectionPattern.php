<?php

return [
    '@internal For Core use only. Do not reference from plugins/themes' => '@internal コア専用。プラグイン/テーマから参照しないこと',
    'Database-related detection patterns' => 'データベース関連の検出パターン',
    'Detects database.own_tables, database.core_tables_read, database.core_tables_write' => 'database.own_tables, database.core_tables_read, database.core_tables_write を検出します。',
    'core_tables_read: Detects references (reads) to Core tables' => 'core_tables_read: コアテーブルへの参照（読み取り）を検出',
    'core_tables_write: Detects write operations to Core tables' => 'core_tables_write: コアテーブルへの書き込み操作を検出',
    'Excludes cases where there are only use statement imports' => 'use文のインポートのみの場合は除外します。',
    'Regex pattern to detect write operations' => '書き込み操作を検出する正規表現パターン',
    'Exclude if only use statement imports' => 'use文のインポートのみの場合は除外',
    'For core_tables_write, also verify that write operations exist in the file' => 'core_tables_write の場合はファイル内に書き込み操作が存在するかも検証',
    'Common to core_tables_read / core_tables_write: exclude use statement imports only' => 'core_tables_read / core_tables_write 共通: use文のインポートのみは除外',
    'core_tables_write: detect only when write operations exist in the file' => 'core_tables_write: ファイル内に書き込み操作が存在する場合のみ検出',
    'Determine if write operations to Core tables exist in the file' => 'ファイル内にコアテーブルへの書き込み操作が存在するか判定',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        '@internal For Core use only. Do not reference from plugins/themes' => 'machine',
        'Database-related detection patterns' => 'machine',
        'Detects database.own_tables, database.core_tables_read, database.core_tables_write' => 'machine',
        'core_tables_read: Detects references (reads) to Core tables' => 'machine',
        'core_tables_write: Detects write operations to Core tables' => 'machine',
        'Excludes cases where there are only use statement imports' => 'machine',
        'Regex pattern to detect write operations' => 'machine',
        'Exclude if only use statement imports' => 'machine',
        'For core_tables_write, also verify that write operations exist in the file' => 'machine',
        'Common to core_tables_read / core_tables_write: exclude use statement imports only' => 'machine',
        'core_tables_write: detect only when write operations exist in the file' => 'machine',
        'Determine if write operations to Core tables exist in the file' => 'machine',
    ],
];
