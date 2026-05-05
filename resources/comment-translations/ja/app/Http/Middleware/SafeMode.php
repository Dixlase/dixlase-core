<?php

return [
    'Safe mode detection middleware' => 'セーフモード検出ミドルウェア',
    'Detects safe mode from ?safe= parameter and stores it in the session' => '?safe= パラメータからセーフモードを検出し、セッションに保存する。',
    '?safe=1 is treated as ?safe=csp for backward compatibility' => '?safe=1 は後方互換性のため ?safe=csp として処理する。',
    'Multiple modes can be specified comma-separated (e.g. ?safe=csp,plugins)' => 'カンマ区切りで複数モード同時指定可能（例: ?safe=csp,plugins）。',
    'When theme safe mode is active, override the view namespace on the frontend' => 'テーマセーフモード時はフロント側のビュー名前空間をオーバーライドする。',
    'Detect ?safe= parameter' => '?safe= パラメータを検出',
    'Override view namespace if theme safe mode is enabled and on frontend' => 'テーマセーフモードが有効かつフロント側の場合、ビュー名前空間をオーバーライド',
    'Determine if this is an admin panel route' => '管理画面ルートかどうかを判定',
    'Note: `config/admin/url.php` is expanded by Laravel to the `admin.url` key, so' => '注意: `config/admin/url.php` は Laravel により `admin.url` キーに展開されるため、',
    'to get the actual admin URL, you need to reference `admin.url.admin_url`' => '実際の管理 URL を取るには `admin.url.admin_url` を参照する必要がある。',
    '`config(\'admin.url\')` alone returns the entire file array and str_starts_with throws a TypeError' => '`config(\'admin.url\')` 単独だとファイルの配列全体が返り str_starts_with が TypeError で落ちる。',
    'Override theme view namespace to safe theme' => 'テーマのビュー名前空間をセーフテーマにオーバーライド',
    'Override themes:: namespace to safe theme' => 'themes:: 名前空間をセーフテーマに上書き',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Safe mode detection middleware' => 'machine',
        'Detects safe mode from ?safe= parameter and stores it in the session' => 'machine',
        '?safe=1 is treated as ?safe=csp for backward compatibility' => 'machine',
        'Multiple modes can be specified comma-separated (e.g. ?safe=csp,plugins)' => 'machine',
        'When theme safe mode is active, override the view namespace on the frontend' => 'machine',
        'Detect ?safe= parameter' => 'machine',
        'Override view namespace if theme safe mode is enabled and on frontend' => 'machine',
        'Determine if this is an admin panel route' => 'machine',
        'Note: `config/admin/url.php` is expanded by Laravel to the `admin.url` key, so' => 'machine',
        'to get the actual admin URL, you need to reference `admin.url.admin_url`' => 'machine',
        '`config(\'admin.url\')` alone returns the entire file array and str_starts_with throws a TypeError' => 'machine',
        'Override theme view namespace to safe theme' => 'machine',
        'Override themes:: namespace to safe theme' => 'machine',
    ],
];
