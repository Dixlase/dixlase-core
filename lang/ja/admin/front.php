<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

return [
    'index' => [
        'heading' => 'フロントページマスター',
        'description' => 'フロントページのコンテンツを管理します。作成・編集・リセットが行えます。',

        'content_exists_title' => 'フロントページコンテンツ',
        'language' => '言語',
        'editor_type' => 'エディタータイプ',
        'storage_type' => '保存方法',
        'last_updated' => '最終更新',
        'status' => 'ステータス',

        'edit_button' => 'コンテンツを編集',
        'reset_button' => 'リセット',
        'reset_confirm' => 'フロントページのコンテンツをリセットしますか？この操作は元に戻せません。',

        'no_content_title' => 'コンテンツがありません',
        'no_content_description' => 'フロントページのコンテンツはまだ作成されていません。下のボタンから作成してください。',
        'create_button' => 'フロントページを作成',

        'reset_success' => 'フロントページのコンテンツをリセットしました。',
    ],

    'create' => [
        'heading' => 'フロントページ作成',
        'description' => '言語とエディタータイプを選んでフロントページのコンテンツを作成します。',

        'lang_label' => '言語',
        'editor_type_label' => 'エディタータイプ',
        'editor_html_description' => 'HTMLコードを直接記述します。マークアップを完全に制御できます。',
        'editor_markdown_description' => 'Markdown記法で記述します。シンプルで習得が容易です。',
        'editor_gui_description' => 'ドラッグ＆ドロップのビジュアルエディタ。近日公開予定。',
        'content_label' => 'コンテンツ',
        'content_placeholder' => 'フロントページのコンテンツを入力...',

        'storage_section' => '保存設定',
        'storage_type_label' => '保存方法',
        'storage_file_path' => 'ファイルパス:',

        'confirm_title' => 'フロントページを作成',
        'confirm_message' => '選択した設定でフロントページのコンテンツを作成しますか？',

        'create_success' => 'フロントページのコンテンツを作成しました。',

        'sidebar_open' => 'サイドバーを開く',
        'sidebar_close' => 'サイドバーを閉じる',

        'validation' => [
            'lang_required' => '言語を選択してください。',
            'lang_in' => '選択された言語はサポートされていません。',
            'editor_type_required' => 'エディタータイプを選択してください。',
            'editor_type_in' => '選択されたエディタータイプは無効です。',
            'storage_type_required' => '保存方法を選択してください。',
            'storage_type_in' => '選択された保存方法は無効です。',
            'content_max' => 'コンテンツは500,000文字以内で入力してください。',
        ],
    ],

    'edit' => [
        'heading' => 'フロントページ編集',
        'description' => 'フロントページのコンテンツを編集します。',

        'editor_type_label' => 'エディタータイプ',
        'editor_type_locked_help' => 'エディタータイプは作成後に変更できません。変更するにはリセットして再作成してください。',
        'lang_label' => '言語',
        'content_label' => 'コンテンツ',
        'content_placeholder' => 'フロントページのコンテンツを入力...',

        'storage_section' => '保存設定',
        'storage_type_label' => '保存方法',
        'storage_file_path' => 'ファイルパス:',

        'confirm_title' => '変更を保存',
        'confirm_message' => 'フロントページのコンテンツへの変更を保存しますか？',

        'save_success' => 'フロントページのコンテンツを更新しました。',

        'sidebar_open' => 'サイドバーを開く',
        'sidebar_close' => 'サイドバーを閉じる',

        'validation' => [
            'storage_type_required' => '保存方法を選択してください。',
            'storage_type_in' => '選択された保存方法は無効です。',
            'content_max' => 'コンテンツは500,000文字以内で入力してください。',
        ],
    ],

    'settings' => [
        'heading' => 'フロントページ設定',
        'description' => 'フロントページの設定を行います。',

        'no_settings' => '現在、追加の設定項目はありません。',

        'settings_updated' => 'フロントページの設定を更新しました。',

        'confirm_title' => '設定を保存',
        'confirm_message' => 'フロントページの設定を保存しますか？',
    ],
];
