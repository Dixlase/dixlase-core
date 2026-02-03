<?php

return [

        'description' => 'テーマを無効化します',
        'theme_name_prompt' => '無効化するテーマ名',
        'no_enabled_themes' => '有効なテーマが見つかりませんでした。',
        'theme_not_found' => 'テーマ \':themeName\' が見つかりません。',
        'not_installed' => 'テーマ \':themeName\' はインストールされていません。',
        'already_disabled' => 'テーマ \':themeName\' は既に無効化されています。',
        'disabled' => 'テーマを無効化しました: :themeName',
        'list_headers' => ['名前', 'スラッグ'],
        'disable_help' => 'テーマを無効化するには、次のコマンドを実行してください: php artisan dls:theme:disable <theme-name>',
];
