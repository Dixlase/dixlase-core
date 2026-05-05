<?php

return [
    '@internal For Core use only. Do not reference from plugins/themes' => '@internal コア専用。プラグイン/テーマから参照しないこと',
    'Safe mode management service' => 'セーフモード管理サービス',
    'Manages enabling/disabling of session-based safe mode' => 'セッションベースのセーフモードの有効化/無効化を管理する。',
    'Operates on session only, no database writes' => 'DB書き込みなし、セッションのみで動作する。',
    'Enable the specified safe mode' => '指定したセーフモードを有効化',
    'Disable the specified safe mode' => '指定したセーフモードを無効化',
    'Disable all safe modes' => 'すべてのセーフモードを無効化',
    'Whether the specified safe mode is enabled' => '指定したセーフモードが有効かどうか',
    'Get list of enabled safe modes' => '有効なセーフモード一覧を取得',
    'Whether any safe mode is enabled' => 'いずれかのセーフモードが有効かどうか',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        '@internal For Core use only. Do not reference from plugins/themes' => 'machine',
        'Safe mode management service' => 'machine',
        'Manages enabling/disabling of session-based safe mode' => 'machine',
        'Operates on session only, no database writes' => 'machine',
        'Enable the specified safe mode' => 'machine',
        'Disable the specified safe mode' => 'machine',
        'Disable all safe modes' => 'machine',
        'Whether the specified safe mode is enabled' => 'machine',
        'Get list of enabled safe modes' => 'machine',
        'Whether any safe mode is enabled' => 'machine',
    ],
];
