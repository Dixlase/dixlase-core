<?php

return [
    'Authority public key cache table' => 'Authority 公開鍵キャッシュテーブル',
    'Purpose:' => '目的：',
    '- Cache public keys retrieved from authority.dixlase.net locally' => '- authority.dixlase.net から取得した公開鍵をローカルにキャッシュ',
    '- Used for signature verification during plugin installation' => '- プラグインインストール時の署名検証で使用',
    '- Verification possible offline if cache exists' => '- オフライン時もキャッシュがあれば検証可能',
    'created_at from Authority side' => 'Authority 側の created_at',
    'Local cache retrieval time' => 'ローカルキャッシュ取得時刻',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Authority public key cache table' => 'machine',
        'Purpose:' => 'machine',
        '- Cache public keys retrieved from authority.dixlase.net locally' => 'machine',
        '- Used for signature verification during plugin installation' => 'machine',
        '- Verification possible offline if cache exists' => 'machine',
        'created_at from Authority side' => 'machine',
        'Local cache retrieval time' => 'machine',
    ],
];
