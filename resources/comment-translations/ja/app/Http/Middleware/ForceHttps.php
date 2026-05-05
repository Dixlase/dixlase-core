<?php

return [
    'Middleware to redirect HTTP requests to HTTPS when FORCE_SSL is enabled' => 'FORCE_SSL有効時にHTTPリクエストをHTTPSへリダイレクトするミドルウェア',
    'Paths excluded from redirect' => 'リダイレクト対象外のパス',
    'Skip during installation' => 'インストール中はスキップ',
    'Skip if already HTTPS or HTTPS via reverse proxy' => '既にHTTPS or リバースプロキシ経由のHTTPSならスキップ',
    'Redirect HTTP request to HTTPS (301 Permanent Redirect)' => 'HTTPリクエストをHTTPSにリダイレクト（301 Permanent Redirect）',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Middleware to redirect HTTP requests to HTTPS when FORCE_SSL is enabled' => 'machine',
        'Paths excluded from redirect' => 'machine',
        'Skip during installation' => 'machine',
        'Skip if already HTTPS or HTTPS via reverse proxy' => 'machine',
        'Redirect HTTP request to HTTPS (301 Permanent Redirect)' => 'machine',
    ],
];
