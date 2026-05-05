<?php

return [
    'Expired backup cleanup command' => '期限切れバックアップ削除コマンド',
    'Deletes backups whose retention_until has passed the current time' => 'retention_until が現在時刻を過ぎているバックアップを削除する。',
    'Intended to be run periodically via cron or similar' => 'cron 等で定期実行することを想定。',
    'Example:' => '例:',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Expired backup cleanup command' => 'machine',
        'Deletes backups whose retention_until has passed the current time' => 'machine',
        'Intended to be run periodically via cron or similar' => 'machine',
        'Example:' => 'machine',
    ],
];
