<?php

return [

        'description' => 'テーマのファイルとディレクトリを削除します（アンインストール済みである必要があります）',
        'theme_directory_prompt' => '削除するテーマのディレクトリ名',
        'force_option' => '確認なしで強制的に削除します',
        'not_found' => 'テーマディレクトリ \':directory\' は見つかりません。',
        'still_installed' => 'テーマ \':themeName\' はまだインストールされています。',
        'still_enabled' => 'テーマ \':themeName\' はまだ有効化されています。',
        'uninstall_first' => '削除する前に、まず `dls:theme:uninstall` コマンドでテーマをアンインストールしてください。',
        'disable_first' => '削除する前に、まず別のテーマに切り替えてください。',
        'confirm' => 'テーマディレクトリ \':directory\' とその中のすべてのファイルを削除しますか?この操作は取り消せません。',
        'cancelled' => '削除がキャンセルされました。',
        'deleted' => 'テーマディレクトリ \':path\' を削除しました。',
        'failed' => 'テーマディレクトリの削除に失敗しました: :error',
        'database_removed' => 'テーマ \':themeName\' をデータベースから削除しました。',
        'completed' => 'テーマ \':directory\' の削除が完了しました。',
];
