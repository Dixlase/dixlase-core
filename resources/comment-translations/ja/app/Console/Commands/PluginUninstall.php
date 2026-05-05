<?php

return [
    'Initialize before command execution' => 'コマンド実行前の初期化',
    'Get the plugin name to uninstall' => 'アンインストール対象のプラグイン名を取得',
    'Set flag in both environment variable and config' => '環境変数とconfigの両方でフラグを設定',
    'Check enabled state' => '有効化状態チェック',
    'Confirm uninstallation (only when --no-interaction option is not present)' => 'アンインストール確認（--no-interactionオプションがない場合のみ）',
    'Set uninstalling flag (prevents ServiceProvider from loading)' => 'アンインストール処理中フラグを設定（ServiceProvider読み込みを防ぐ）',
    'Execute disable only when --force option is specified' => '--forceオプションが指定されている場合のみ無効化を実行',
    'Rollback migrations' => 'マイグレーションのロールバック',
    'Set step to a large value to rollback all migrations' => '全てのマイグレーションをロールバックするため、stepを大きな値に設定',
    'Do not delete directory (use plugin:delete command)' => 'ディレクトリは削除しない（plugin:deleteコマンドを使用）',
    'Delete plugin from database' => 'データベースからプラグインを削除',
    'Note: Updating composer.local.json and .git/info/exclude' => '注意: composer.local.jsonと.git/info/excludeの更新は、',
    'is done during plugin deletion (plugin:delete), so not needed here' => 'プラグイン削除時（plugin:delete）に行うため、ここでは不要',
    'Clear uninstalling flag' => 'アンインストール処理中フラグをクリア',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Initialize before command execution' => 'machine',
        'Get the plugin name to uninstall' => 'machine',
        'Set flag in both environment variable and config' => 'machine',
        'Check enabled state' => 'machine',
        'Confirm uninstallation (only when --no-interaction option is not present)' => 'machine',
        'Set uninstalling flag (prevents ServiceProvider from loading)' => 'machine',
        'Execute disable only when --force option is specified' => 'machine',
        'Rollback migrations' => 'machine',
        'Set step to a large value to rollback all migrations' => 'machine',
        'Do not delete directory (use plugin:delete command)' => 'machine',
        'Delete plugin from database' => 'machine',
        'Note: Updating composer.local.json and .git/info/exclude' => 'machine',
        'is done during plugin deletion (plugin:delete), so not needed here' => 'machine',
        'Clear uninstalling flag' => 'machine',
    ],
];
