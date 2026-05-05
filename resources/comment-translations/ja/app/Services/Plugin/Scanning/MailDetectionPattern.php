<?php

return [
    '@internal Core use only. Do not reference from plugins/themes' => '@internal コア専用。プラグイン/テーマから参照しないこと',
    'Mail-related detection patterns' => 'メール関連の検出パターン',
    'Detects mail.send and mail.bulk_send' => 'mail.send と mail.bulk_send を検出します。',
    'Excludes cases with only use statement imports or only Mailable class definitions' => 'use文のインポートのみ、Mailable クラス定義のみの場合は除外します。',
    'Mail usage in closure within each (same line to within a few lines)' => 'each内のクロージャでMail使用（同一行〜数行以内）',
    'Excludes only use statement imports, Mailable within class definitions is valid' => 'use文のインポートのみは除外、クラス定義内のMailableは有効',
    'Excludes only use statement imports' => 'use文のインポートのみは除外',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        '@internal Core use only. Do not reference from plugins/themes' => 'machine',
        'Mail-related detection patterns' => 'machine',
        'Detects mail.send and mail.bulk_send' => 'machine',
        'Excludes cases with only use statement imports or only Mailable class definitions' => 'machine',
        'Mail usage in closure within each (same line to within a few lines)' => 'machine',
        'Excludes only use statement imports, Mailable within class definitions is valid' => 'machine',
        'Excludes only use statement imports' => 'machine',
    ],
];
