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
        'reset_confirm_title' => 'フロントページのリセット',
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
        'editor_type_help' => 'エディタータイプは新規作成時のみ選択でき、保存後は変更できません。',
        'content_label' => 'コンテンツ',
        'content_placeholder' => 'フロントページのコンテンツを入力...',

        'custom_css_placeholder' => 'カスタムCSSスタイルを入力...',
        'custom_js_placeholder' => 'カスタムJavaScriptを入力...',

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
            'custom_js_max' => 'JavaScriptは500,000文字以内で入力してください。',
            'custom_css_max' => 'CSSは500,000文字以内で入力してください。',
        ],
    ],

    'edit' => [
        'heading' => 'フロントページ編集',
        'description' => 'フロントページのコンテンツを編集します。',

        'editor_type_label' => 'エディタータイプ',
        'lang_label' => '言語',
        'content_label' => 'コンテンツ',
        'content_placeholder' => 'フロントページのコンテンツを入力...',

        'custom_css_placeholder' => 'カスタムCSSスタイルを入力...',
        'custom_js_placeholder' => 'カスタムJavaScriptを入力...',

        'confirm_title' => '変更を保存',
        'confirm_message' => 'フロントページのコンテンツへの変更を保存しますか？',

        'save_success' => 'フロントページのコンテンツを更新しました。',

        'sidebar_open' => 'サイドバーを開く',
        'sidebar_close' => 'サイドバーを閉じる',

        'preview_title' => 'プレビュー',
        'preview_show' => 'プレビューを表示',
        'preview_hide' => 'プレビューを非表示',
        'scroll_to_editor' => 'エディタに移動',
        'scroll_to_preview' => 'プレビューに移動',
        'device_mobile' => 'モバイル',
        'device_tablet' => 'タブレット',
        'device_desktop' => 'デスクトップ',
        'device_free' => 'フリーサイズ',
        'preview_width' => '幅',
        'preview_height' => '高さ',

        'reset_section_title' => '危険な操作',
        'reset_description' => 'フロントページのコンテンツをリセットします。この操作は元に戻せません。',
        'reset_button' => 'リセット',
        'reset_confirm_title' => 'フロントページのリセット',
        'reset_confirm' => 'フロントページのコンテンツをリセットしますか？すべてのコンテンツ、CSS、JavaScriptが完全に削除されます。',

        'validation' => [
            'storage_type_required' => '保存方法を選択してください。',
            'storage_type_in' => '選択された保存方法は無効です。',
            'content_max' => 'コンテンツは500,000文字以内で入力してください。',
            'custom_js_max' => 'JavaScriptは500,000文字以内で入力してください。',
            'custom_css_max' => 'CSSは500,000文字以内で入力してください。',
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

    'revisions' => [
        'heading' => 'リビジョン履歴',
        'description' => 'フロントページの編集履歴を確認し、過去のバージョンへ復元できます。',
        'back_to_edit' => '編集画面に戻る',
        'no_revisions' => 'リビジョンはまだ記録されていません。',
        'created_at' => '作成日時',
        'type' => '種別',
        'creator' => '作成者',
        'note' => 'メモ',
        'actions' => '操作',
        'view_diff' => '差分を見る',
        'restore' => 'このバージョンに戻す',
        'restore_confirm_title' => 'リビジョンから復元',
        'restore_confirm_message' => '選択したリビジョンの内容でフロントページを上書きします。現在の内容は自動的にバックアップされます。続行しますか？',
        'restore_success' => 'リビジョンから復元しました。',

        'type_auto' => '自動保存',
        'type_manual' => '手動作成',
        'type_restore_backup' => '復元前バックアップ',

        'diff_heading' => 'このリビジョンと現在の内容の差分',
        'diff_field_title' => 'タイトル',
        'diff_field_content' => '本文',
        'diff_field_custom_js' => 'カスタム JavaScript',
        'diff_field_custom_css' => 'カスタム CSS',
        'diff_no_changes' => 'このリビジョンと現在の内容に差分はありません。',
        'diff_meta_heading' => 'メタデータの変更',
        'diff_field_storage_type' => '保存形式',
        'diff_field_editor_type' => 'エディタータイプ',
        'diff_field_status' => '公開状態',
        'diff_left_label' => 'このリビジョン',
        'diff_right_label' => '現在',
        'unknown_user' => '不明',
    ],
];
