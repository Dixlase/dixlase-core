<?php

return [

        'description' => 'プラグインをアンインストールし、データベースから削除します（ファイルは保持されます）。',
        'not_found' => 'プラグイン \':pluginName\' は見つかりません。',
        'still_enabled' => 'プラグイン \':pluginName\' は有効化されています。',
        'disable_first' => 'アンインストールする前に、まず `plugin:disable` コマンドでプラグインを無効化してください。',
        'force_disabling' => '--forceオプションが指定されたため、プラグイン \':pluginName\' を強制的に無効化します。',
        'confirm' => 'プラグイン \':pluginName\' をアンインストールしますか？この操作はデータベースからプラグイン情報を削除します。',
        'cancelled' => 'アンインストールがキャンセルされました。',
        'rollback_running' => 'マイグレーションのロールバックを実行中...',
        'rollback_confirm' => 'プラグイン \':pluginName\' に関連するデータベースのテーブルを削除しますか？',
        'rollback_skipped' => 'データベースのロールバックはスキップされました。',
        'files_preserved' => 'プラグインのファイルとディレクトリは保持されました。',
        'database_removed' => 'プラグイン \':pluginName\' をデータベースから削除しました。',
        'completed' => 'プラグイン \':pluginName\' のアンインストールが完了しました。',
        'delete_hint' => 'ファイルを削除するには `php artisan plugin:delete <directory>` コマンドを実行してください。',
];
