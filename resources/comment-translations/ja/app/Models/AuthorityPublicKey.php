<?php

return [
    'Local cache of Authority public keys' => 'Authority 公開鍵のローカルキャッシュ',
    'Stores public keys retrieved from keys.dixlase.com for use in' => 'keys.dixlase.com から取得した公開鍵を保存し、プラグインインストール時の',
    'signature verification during plugin installation. The Resolver determines whether to re-fetch keys with old fetched_at values' => '署名検証で使用する。fetched_at が古いものは Resolver が再フェッチを判断する。',
    'Returns true if the specified time has elapsed since fetched_at (re-fetch recommended)' => 'fetched_at から指定された時間が経過していれば true（再フェッチ推奨）',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Local cache of Authority public keys' => 'machine',
        'Stores public keys retrieved from keys.dixlase.com for use in' => 'machine',
        'signature verification during plugin installation. The Resolver determines whether to re-fetch keys with old fetched_at values' => 'machine',
        'Returns true if the specified time has elapsed since fetched_at (re-fetch recommended)' => 'machine',
    ],
];
