<?php

return [
    '@internal For Core use only. Do not reference from plugins/themes' => '@internal コア専用。プラグイン/テーマから参照しないこと',
    'Detection pattern for middleware registration' => 'ミドルウェア登録の検出パターン',
    'Detects system.register_middleware' => 'system.register_middleware を検出します。',
    'Distinguishes between "usage" via Route::middleware() and "registration" via pushMiddleware()' => 'Route::middleware() による「使用」と、pushMiddleware() による「登録」を区別します。',
    'Excludes middleware "usage" via Route::middleware(),' => 'Route::middleware() によるミドルウェアの「使用」は除外し、',
    'detects only "registration"' => '「登録」のみを検出する',
    'Middleware usage in route definitions is not "registration"' => 'ルート定義でのmiddleware使用は「登録」ではない',
    '->middleware(\'name\') in method chains is "usage"' => 'チェーンメソッドでの ->middleware(\'name\') は「使用」',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        '@internal For Core use only. Do not reference from plugins/themes' => 'machine',
        'Detection pattern for middleware registration' => 'machine',
        'Detects system.register_middleware' => 'machine',
        'Distinguishes between "usage" via Route::middleware() and "registration" via pushMiddleware()' => 'machine',
        'Excludes middleware "usage" via Route::middleware(),' => 'machine',
        'detects only "registration"' => 'machine',
        'Middleware usage in route definitions is not "registration"' => 'machine',
        '->middleware(\'name\') in method chains is "usage"' => 'machine',
    ],
];
