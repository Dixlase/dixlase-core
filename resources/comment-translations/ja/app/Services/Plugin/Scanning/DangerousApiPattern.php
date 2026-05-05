<?php

return [
    '@internal For Core use only. Do not reference from plugins/themes' => '@internal コア専用。プラグイン/テーマから参照しないこと',
    'Detection patterns for dangerous API calls' => '危険なAPI呼び出しの検出パターン',
    'Detects direct use of exec, shell_exec, eval, system, passthru, and env()' => 'exec, shell_exec, eval, system, passthru, env()直接使用を検出します。',
    'Excludes matches within comment lines and string literals' => 'コメント行、文字列リテラル内は除外します。',
    'Exclude matches within comment lines and string literals' => 'コメント行、文字列リテラル内のマッチを除外',
    'Exclude matches within PHPDoc' => 'PHPDoc内のマッチは除外',
    'For env(), usage within config files is allowed (Laravel convention)' => 'env()の場合、config ファイル内での使用は許容（Laravelの慣例）',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        '@internal For Core use only. Do not reference from plugins/themes' => 'machine',
        'Detection patterns for dangerous API calls' => 'machine',
        'Detects direct use of exec, shell_exec, eval, system, passthru, and env()' => 'machine',
        'Excludes matches within comment lines and string literals' => 'machine',
        'Exclude matches within comment lines and string literals' => 'machine',
        'Exclude matches within PHPDoc' => 'machine',
        'For env(), usage within config files is allowed (Laravel convention)' => 'machine',
    ],
];
