<?php

return [
    'Get the display timezone' => '表示用タイムゾーンを取得する',
    'Storage and calculations always use UTC (config(\'app.timezone\')); this method is' => '保存・計算は常に UTC（config(\'app.timezone\')）で行い、本メソッドは',
    'Returns null before installation or on database connection error' => 'インストール前やデータベース接続エラーの場合はnullを返す',
    'After multisite consolidation, security settings are also integrated into global_settings' => 'multisite consolidation 後はセキュリティ系も global_settings に統合済み',
    'Used when converting to local time in Blade or notification emails. Value is site_settings.display_timezone' => 'Blade や通知メールで現地時刻に変換する際に使う。値は site_settings.display_timezone。',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Returns null before installation or on database connection error' => 'machine',
        'After multisite consolidation, security settings are also integrated into global_settings' => 'machine',
        'Used when converting to local time in Blade or notification emails. Value is site_settings.display_timezone' => 'machine',
    ],
];
