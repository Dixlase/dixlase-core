<?php

return [

        'description' => 'テーマを切り替えます（有効化するテーマを選択）',
        'theme_name_prompt' => '切り替えるテーマ名',
        'no_themes' => 'データベースにテーマが見つかりませんでした。',
        'no_installed_themes' => 'インストール済みのテーマがありません。',
        'theme_not_found' => 'テーマ \':themeName\' が見つかりません。',
        'not_installed' => 'テーマ \':themeName\' はインストールされていません。',
        'install_first' => 'テーマを有効化する前に、まず `dls:theme:install` コマンドでテーマをインストールしてください。',
        'disabled' => 'テーマを無効化しました: :themeName',
        'already_enabled' => 'テーマ \':themeName\' は既に有効化されています。',
        'enabled' => 'テーマを有効化しました: :themeName',
        'list_headers' => ['名前', 'スラッグ', 'インストール', 'ステータス'],
        'installed' => 'インストール済み',
        'not_installed_status' => '未インストール',
        'status_enabled' => '有効',
        'status_disabled' => '無効',
        'select_prompt' => '有効化するテーマを選択してください',
        'current_marker' => '(現在有効)',
        'selection_error' => 'テーマの選択に失敗しました。',
        'symlink_warning' => 'シンボリックリンクの更新に失敗しましたが、テーマの切り替えは完了しました。',
];
