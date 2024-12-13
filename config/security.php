<?php
return [
    // 管理画面のURL
    'admin_url' => env('ADMIN_URL', 'admin'),
    // 管理画面へのアクセスを許可するIPアドレス
    'allowed_admin_ips' => [
        //'127.0.0.1', // 例: ローカルIP
        //'192.168.1.10',
        '10.5.1.148',
        '0.0.0.0'
    ],
    // 管理画面へのアクセスを拒否するIPアドレス
    'blocked_admin_ips' => [
        //'123.456.789.0', // 例: 拒否するIP
    ],

    // フロントエンドへのアクセスを許可するIPアドレス
    'allowed_frontend_ips' => [
        // 例: 許可するIP
    ],
    // フロントエンドへのアクセスを拒否するIPアドレス
    'blocked_frontend_ips' => [
        // 例: 拒否するIP
    ],

    // SSLを強制するかどうか
    'force_ssl' => env('FORCE_SSL', false),


];
