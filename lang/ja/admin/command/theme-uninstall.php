<?php

return [

        'description' => 'テーマをアンインストールします（ファイルは保持されます）',
        'theme_name_prompt' => 'アンインストールするテーマ名',
        'theme_not_found' => 'テーマ \':themeName\' はデータベースに見つかりませんでした。',
        'not_installed' => 'テーマ \':themeName\' はインストールされていません。',
        'cannot_uninstall_enabled' => '有効なテーマ \':themeName\' をアンインストールできません。',
        'disable_first' => 'アンインストールする前に、まず `dls:theme:disable` コマンドでテーマを無効化してください。',
        'confirmation' => '本当にテーマ \':themeName\' をアンインストールしますか?',
        'cancelled' => 'アンインストールはキャンセルされました。',
        'uninstalled' => 'テーマをアンインストールしました: :themeName',
        'files_preserved' => 'テーマのファイルとディレクトリは保持されました。',
        'delete_hint' => 'ファイルを削除するには `php artisan theme:delete <directory>` コマンドを実行してください。',
];
