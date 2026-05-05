<?php

return [
    'API key authentication middleware' => 'APIキー認証ミドルウェア',
    'Retrieves key from Authorization: Bearer dxl_live_xxx header and' => 'Authorization: Bearer dxl_live_xxx ヘッダーからキーを取得し、',
    'validates API key validity, scope, and IP restrictions' => 'APIキーの有効性・スコープ・IP制限を検証します。',
    'Usage examples:' => '使用例:',
    '- Route::middleware(\'auth.api\') ... all scopes allowed' => '- Route::middleware(\'auth.api\') ... 全スコープ許可',
    '- Route::middleware(\'auth.api:read:content\') ... read:content scope required' => '- Route::middleware(\'auth.api:read:content\') ... read:content スコープ必須',
    'Handle the request' => 'リクエストを処理',
    'IP restriction check' => 'IP制限チェック',
    'Scope check' => 'スコープチェック',
    'Update usage record' => '使用記録を更新',
    'Set API key to request attribute' => 'リクエスト属性にAPIキーをセット',
    'Generate 401 Unauthorized response' => '401 Unauthorized レスポンスを生成',
    'Generate 403 Forbidden response' => '403 Forbidden レスポンスを生成',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'API key authentication middleware' => 'machine',
        'Retrieves key from Authorization: Bearer dxl_live_xxx header and' => 'machine',
        'validates API key validity, scope, and IP restrictions' => 'machine',
        'Usage examples:' => 'machine',
        '- Route::middleware(\'auth.api\') ... all scopes allowed' => 'machine',
        '- Route::middleware(\'auth.api:read:content\') ... read:content scope required' => 'machine',
        'Handle the request' => 'machine',
        'IP restriction check' => 'machine',
        'Scope check' => 'machine',
        'Update usage record' => 'machine',
        'Set API key to request attribute' => 'machine',
        'Generate 401 Unauthorized response' => 'machine',
        'Generate 403 Forbidden response' => 'machine',
    ],
];
