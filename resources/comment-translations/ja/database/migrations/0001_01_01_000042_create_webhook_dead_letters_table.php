<?php

return [
    'Webhook dead letter queue table' => 'Webhookデッドレターキューテーブル',
    'Purpose:' => '目的：',
    '- Detailed records of webhooks that failed after exceeding max retry count' => '- 最大リトライ回数を超えて失敗したWebhookの詳細記録',
    '- Retain information for manual retry and investigation' => '- 手動リトライや調査のための情報保持',
    '- Failure analysis and notification' => '- 障害分析・通知',
    'Detailed log of each attempt' => '各試行の詳細ログ',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Webhook dead letter queue table' => 'machine',
        'Purpose:' => 'machine',
        '- Detailed records of webhooks that failed after exceeding max retry count' => 'machine',
        '- Retain information for manual retry and investigation' => 'machine',
        '- Failure analysis and notification' => 'machine',
        'Detailed log of each attempt' => 'machine',
    ],
];
