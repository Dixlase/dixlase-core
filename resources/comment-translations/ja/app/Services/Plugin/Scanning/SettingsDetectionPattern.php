<?php

return [
    '@internal For Core use only. Do not reference from plugins/themes' => '@internal コア専用。プラグイン/テーマから参照しないこと',
    'Detection patterns for settings reading' => '設定読み取り関連の検出パターン',
    'Detects settings.read_core and settings.write_own' => 'settings.read_core, settings.write_own を検出します。',
    'Exclude use statements that only import' => 'use文のインポートのみは除外',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        '@internal For Core use only. Do not reference from plugins/themes' => 'machine',
        'Detection patterns for settings reading' => 'machine',
        'Detects settings.read_core and settings.write_own' => 'machine',
        'Exclude use statements that only import' => 'machine',
    ],
];
