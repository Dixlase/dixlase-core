<?php

return [
    'Webhook delivery log table' => 'Webhook配信ログテーブル',
    'Purpose:' => '目的：',
    '- Webhook delivery history and status management' => '- Webhook配信の履歴・状態管理',
    '- Retry control' => '- リトライ制御',
    '- Idempotency guarantee (event_id)' => '- 冪等性保証（event_id）',
    '- Replay prevention (nonce)' => '- リプレイ防止（nonce）',
    'Event ID for idempotency (UUID)' => '冪等性のためのイベントID（UUID）',
    'Nonce for replay prevention' => 'リプレイ防止のためのnonce',
    'Dead letter related' => 'デッドレター関連',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Webhook delivery log table' => 'machine',
        'Purpose:' => 'machine',
        '- Webhook delivery history and status management' => 'machine',
        '- Retry control' => 'machine',
        '- Idempotency guarantee (event_id)' => 'machine',
        '- Replay prevention (nonce)' => 'machine',
        'Event ID for idempotency (UUID)' => 'machine',
        'Nonce for replay prevention' => 'machine',
        'Dead letter related' => 'machine',
    ],
];
