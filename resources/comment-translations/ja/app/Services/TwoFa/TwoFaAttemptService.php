<?php

return [
    '@internal Core use only. Do not reference from plugins/themes' => '@internal コア専用。プラグイン/テーマから参照しないこと',
    'Record 2FA attempt' => '2FA試行を記録',
    'Check if locked out' => 'ロックアウト状態かチェック',
    'Get remaining time until lockout release (minutes)' => 'ロックアウト解除までの残り時間（分）を取得',
    'Check if attempt limit has been reached' => '試行回数制限に達しているかチェック',
    'Get remaining number of attempts' => '残りの試行可能回数を取得',
    'Get last lockout time' => '最後のロックアウト時刻を取得',
    'Get failed attempts within time window' => '時間枠内の失敗試行を取得',
    'Return last failure time if maximum attempts reached' => '最大試行回数に達している場合、最後の失敗時刻を返す',
    'Process on success (clear failure records)' => '成功時の処理（失敗記録をクリア）',
    'Record success (attempt_type specified by caller)' => '成功を記録（attempt_typeは呼び出し元で指定）',
    'Get maximum number of attempts' => '最大試行回数を取得',
    'Get time window for attempt limit (minutes)' => '試行制限の時間枠（分）を取得',
    'Get lockout duration (minutes)' => 'ロックアウト時間（分）を取得',
    'Check if lockout notification is enabled' => 'ロックアウト通知が有効かチェック',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        '@internal Core use only. Do not reference from plugins/themes' => 'machine',
        'Record 2FA attempt' => 'machine',
        'Check if locked out' => 'machine',
        'Get remaining time until lockout release (minutes)' => 'machine',
        'Check if attempt limit has been reached' => 'machine',
        'Get remaining number of attempts' => 'machine',
        'Get last lockout time' => 'machine',
        'Get failed attempts within time window' => 'machine',
        'Return last failure time if maximum attempts reached' => 'machine',
        'Process on success (clear failure records)' => 'machine',
        'Record success (attempt_type specified by caller)' => 'machine',
        'Get maximum number of attempts' => 'machine',
        'Get time window for attempt limit (minutes)' => 'machine',
        'Get lockout duration (minutes)' => 'machine',
        'Check if lockout notification is enabled' => 'machine',
    ],
];
