<?php

return [
    'Service for generating and managing a unique nonce per request' => 'リクエストごとに一意のnonceを生成・管理するサービス。',
    'The nonce is used to allow inline scripts/styles' => 'nonceはインラインスクリプト/スタイルの許可に使用される。',
    'Nonce value for the current request' => '現在のリクエストのnonce値',
    'Byte length when generating nonce' => 'nonce生成時のバイト長',
    'Get the nonce for the current request' => '現在のリクエスト用のnonceを取得',
    'Generate a new one if not yet generated' => 'まだ生成されていない場合は新規生成する。',
    'Always returns the same nonce within the same request' => '同一リクエスト内では常に同じnonceを返す。',
    'Generate a new nonce' => '新しいnonceを生成',
    'Generate a Base64 encoded string from cryptographically secure random bytes' => '暗号学的に安全なランダムバイトからBase64エンコードされた文字列を生成。',
    'Reset the nonce' => 'nonceをリセット',
    'Not normally used, but can be used when needed for testing, etc.' => '通常は使用しないが、テスト等で必要な場合に使用。',
    'Get the nonce string for CSP directive' => 'CSPディレクティブ用のnonce文字列を取得',
    'Example: \'nonce-abc123...\'' => '例: \'nonce-abc123...\'',
    'Get the nonce string for HTML attribute' => 'HTML属性用のnonce文字列を取得',
    'Example: nonce="abc123..."' => '例: nonce="abc123..."',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Service for generating and managing a unique nonce per request' => 'machine',
        'The nonce is used to allow inline scripts/styles' => 'machine',
        'Nonce value for the current request' => 'machine',
        'Byte length when generating nonce' => 'machine',
        'Get the nonce for the current request' => 'machine',
        'Generate a new one if not yet generated' => 'machine',
        'Always returns the same nonce within the same request' => 'machine',
        'Generate a new nonce' => 'machine',
        'Generate a Base64 encoded string from cryptographically secure random bytes' => 'machine',
        'Reset the nonce' => 'machine',
        'Not normally used, but can be used when needed for testing, etc.' => 'machine',
        'Get the nonce string for CSP directive' => 'machine',
        'Example: \'nonce-abc123...\'' => 'machine',
        'Get the nonce string for HTML attribute' => 'machine',
        'Example: nonce="abc123..."' => 'machine',
    ],
];
