<?php

return [
    'File integrity scan (runs daily at 3:00 AM)' => 'ファイル整合性スキャン（毎日午前3時に実行）',
    'Maintenance mode auto-release check (runs every minute)' => 'メンテナンスモード自動解除チェック（1分ごとに実行）',
    'Extension update check (cron ticks hourly, actually runs if the configured interval has elapsed)' => '拡張機能アップデートチェック（毎時 cron が tick、設定された間隔が経過していたら実際に走らせる）',
    'Interval controlled by `extension_update_check_interval` setting (86400 / 43200 / 21600 / 0=manual)' => '`extension_update_check_interval` 設定（86400 / 43200 / 21600 / 0=manual）で間隔を制御。',
    'Inside `when()`, determines whether to run by checking if the configured interval has elapsed since the last check' => '`when()` 内で「前回チェックから設定間隔以上経過したか」を判定して実行可否を決める。',
    'Designed so that cron does not need to be reconfigured when settings change (re-evaluated on hourly tick)' => '設定変更時に cron を組み直す必要がない設計（hourly ティックで再評価される）。',
    'Do not run before installation is complete' => 'インストール完了前は走らせない',
    '0 means manual check only (no automatic execution)' => '0 は手動チェックのみ（自動実行しない）',
    'Maximum last check time (across plugins and themes). If never run, execute immediately' => '前回チェック時刻の最大値（プラグイン・テーマ横断）。一度も走っていなければ即実行。',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'File integrity scan (runs daily at 3:00 AM)' => 'machine',
        'Maintenance mode auto-release check (runs every minute)' => 'machine',
        'Extension update check (cron ticks hourly, actually runs if the configured interval has elapsed)' => 'machine',
        'Interval controlled by `extension_update_check_interval` setting (86400 / 43200 / 21600 / 0=manual)' => 'machine',
        'Inside `when()`, determines whether to run by checking if the configured interval has elapsed since the last check' => 'machine',
        'Designed so that cron does not need to be reconfigured when settings change (re-evaluated on hourly tick)' => 'machine',
        'Do not run before installation is complete' => 'machine',
        '0 means manual check only (no automatic execution)' => 'machine',
        'Maximum last check time (across plugins and themes). If never run, execute immediately' => 'machine',
    ],
];
