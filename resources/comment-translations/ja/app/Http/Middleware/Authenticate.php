<?php

return [
    'In Laravel 10/11, the $guards parameter is passed in the form "[guard1, guard2, ...]"' => 'Laravel 10/11 では、$guards パラメータが "[guard1, guard2, ...]" の形で渡ってくる',
    'If nothing is specified, the default guard is used' => '何も指定しないとデフォルトガード',
    'First, delegate to the parent class handle() method' => 'まず "親クラス" の handle() に任せる',
    'The parent class calls "$this->authenticate($request, $guards)" and invokes unauthenticated() if not authenticated' => '親クラスでは「$this->authenticate($request, $guards)」をコールし、未認証なら unauthenticated() を呼ぶ',
    '→ unauthenticated() calls redirectTo($request)' => '→ unauthenticated() は redirectTo($request) を呼びだす',
    'Where to redirect when unauthenticated with the specified guard' => '指定ガードで未認証だった場合にどこへリダイレクトするか',
    'Called by the parent class\'s unauthenticated() method' => '親クラスの unauthenticated() が呼ぶ',
    'Return a 401 (Unauthorized) response for JSON requests' => 'JSONリクエストなら 401 (Unauthorized) レスポンスにする',
    'Redirect to "admin.login" when accessing admin panel URLs (including dynamically generated admin panel URLs)' => '管理画面URL（動的に生成された管理画面URLにも対応）へアクセス時は "admin.login" へ',
    'Otherwise redirect to "/login"' => 'それ以外は "/login" へ',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'In Laravel 10/11, the $guards parameter is passed in the form "[guard1, guard2, ...]"' => 'machine',
        'If nothing is specified, the default guard is used' => 'machine',
        'First, delegate to the parent class handle() method' => 'machine',
        'The parent class calls "$this->authenticate($request, $guards)" and invokes unauthenticated() if not authenticated' => 'machine',
        '→ unauthenticated() calls redirectTo($request)' => 'machine',
        'Where to redirect when unauthenticated with the specified guard' => 'machine',
        'Called by the parent class\'s unauthenticated() method' => 'machine',
        'Return a 401 (Unauthorized) response for JSON requests' => 'machine',
        'Redirect to "admin.login" when accessing admin panel URLs (including dynamically generated admin panel URLs)' => 'machine',
        'Otherwise redirect to "/login"' => 'machine',
    ],
];
