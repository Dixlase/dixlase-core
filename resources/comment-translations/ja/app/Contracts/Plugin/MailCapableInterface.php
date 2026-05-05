<?php

return [
    'Interface declaring mail sending functionality' => 'メール送信機能を宣言するインターフェース',
    'Implemented by plugins with mail sending functionality.' => 'メール送信機能を持つプラグインが実装します。',
    'Assumes that permissions.mail.send in plugin.json is true.' => 'plugin.json の permissions.mail.send が true であることが前提です。',
    'When resolved via PluginServiceResolver,' => 'PluginServiceResolver 経由で解決する際に、',
    '\'mail.send\' permission is automatically checked.' => '\'mail.send\' 権限が自動的にチェックされます。',
    'Permission key required for this interface' => 'このインターフェースに必要な権限キー',
    'Whether mail sending is supported' => 'メール送信をサポートしているか',
    'Whether bulk sending is supported' => '一括送信をサポートしているか',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Interface declaring mail sending functionality' => 'machine',
        'Implemented by plugins with mail sending functionality.' => 'machine',
        'Assumes that permissions.mail.send in plugin.json is true.' => 'machine',
        'When resolved via PluginServiceResolver,' => 'machine',
        '\'mail.send\' permission is automatically checked.' => 'machine',
        'Permission key required for this interface' => 'machine',
        'Whether mail sending is supported' => 'machine',
        'Whether bulk sending is supported' => 'machine',
    ],
];
