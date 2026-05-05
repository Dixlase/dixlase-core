<?php

return [
    'Lockdown check middleware' => 'ロックダウンチェックミドルウェア',
    'Restrict access during lockdown' => 'ロックダウン中のアクセスを制限する',
    '->middleware(\'lockdown\')           // Check all types' => '->middleware(\'lockdown\')           // 全タイプをチェック',
    '->middleware(\'lockdown:admin\')     // Check admin panel lockdown' => '->middleware(\'lockdown:admin\')     // 管理画面ロックダウンをチェック',
    '->middleware(\'lockdown:api\')       // Check API lockdown' => '->middleware(\'lockdown:api\')       // APIロックダウンをチェック',
    '->middleware(\'lockdown:login\')     // Check login lockdown' => '->middleware(\'lockdown:login\')     // ログインロックダウンをチェック',
    'Lockdown type (null = all types)' => 'ロックダウンタイプ（null=全タイプ）',
    'Check auto-release' => '自動解除をチェック',
    'Get lockdown status' => 'ロックダウン状態を取得',
    'Skip if type is specified and that type is not locked' => 'タイプが指定されていて、そのタイプがロックされていない場合はスキップ',
    'Check access permission' => 'アクセス許可をチェック',
    'Response during lockdown' => 'ロックダウン中のレスポンス',
    'Generate response during lockdown' => 'ロックダウン中のレスポンスを生成',
    'Return JSON response for API requests' => 'APIリクエストの場合はJSONレスポンス',
    'Display lockdown page for normal requests' => '通常のリクエストの場合はロックダウンページを表示',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Lockdown check middleware' => 'machine',
        'Restrict access during lockdown' => 'machine',
        '->middleware(\'lockdown\')           // Check all types' => 'machine',
        '->middleware(\'lockdown:admin\')     // Check admin panel lockdown' => 'machine',
        '->middleware(\'lockdown:api\')       // Check API lockdown' => 'machine',
        '->middleware(\'lockdown:login\')     // Check login lockdown' => 'machine',
        'Lockdown type (null = all types)' => 'machine',
        'Check auto-release' => 'machine',
        'Get lockdown status' => 'machine',
        'Skip if type is specified and that type is not locked' => 'machine',
        'Check access permission' => 'machine',
        'Response during lockdown' => 'machine',
        'Generate response during lockdown' => 'machine',
        'Return JSON response for API requests' => 'machine',
        'Display lockdown page for normal requests' => 'machine',
    ],
];
