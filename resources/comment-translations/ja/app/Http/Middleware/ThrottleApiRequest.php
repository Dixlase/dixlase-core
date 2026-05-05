<?php

return [
    'API rate limit middleware' => 'APIレートリミットミドルウェア',
    'Apply per-key rate limit for API key authenticated requests,' => 'APIキー認証済みリクエストにはキー別レートリミット、',
    'and per-IP rate limit for unauthenticated requests' => '未認証リクエストにはIP別レートリミットを適用します。',
    'Add X-RateLimit-* headers to responses' => 'レスポンスに X-RateLimit-* ヘッダーを付与します。',
    'Handle the request' => 'リクエストを処理',
    'Rate limit exceeded' => 'レートリミット超過',
    'Add rate limit headers' => 'レートリミットヘッダーを付与',
    'Generate 429 Too Many Requests response' => '429 Too Many Requests レスポンスを生成',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'API rate limit middleware' => 'machine',
        'Apply per-key rate limit for API key authenticated requests,' => 'machine',
        'and per-IP rate limit for unauthenticated requests' => 'machine',
        'Add X-RateLimit-* headers to responses' => 'machine',
        'Handle the request' => 'machine',
        'Rate limit exceeded' => 'machine',
        'Add rate limit headers' => 'machine',
        'Generate 429 Too Many Requests response' => 'machine',
    ],
];
