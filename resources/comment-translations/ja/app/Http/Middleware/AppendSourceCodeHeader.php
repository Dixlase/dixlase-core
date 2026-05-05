<?php

return [
    'Middleware to notify source code location in response for AGPL §13 compliance' => 'AGPL §13 準拠のためにレスポンスへソースコード取得先を通知するミドルウェア',
    'Adds a URL where the source code of the running Dixlase CMS instance can be obtained' => '運用中の Dixlase CMS インスタンスのソースコードを取得できる URL を',
    'to the response via the `X-Source-Code` header. When running a modified version,' => '`X-Source-Code` ヘッダーでレスポンスに付与する。改変版を運用する際は',
    'override the source location with `DIXLASE_SOURCE_URL`' => '`DIXLASE_SOURCE_URL` で取得先を上書きすること。',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Middleware to notify source code location in response for AGPL §13 compliance' => 'machine',
        'Adds a URL where the source code of the running Dixlase CMS instance can be obtained' => 'machine',
        'to the response via the `X-Source-Code` header. When running a modified version,' => 'machine',
        'override the source location with `DIXLASE_SOURCE_URL`' => 'machine',
    ],
];
