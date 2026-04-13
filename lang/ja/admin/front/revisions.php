<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
 */

return [
    // パンくず用
    'heading' => 'リビジョン',
    'description' => 'フロントページの編集履歴を確認し、過去のバージョンへ復元できます。',

    // 一覧ページ
    'index' => [
        'heading' => 'リビジョン履歴',
        'description' => 'フロントページの編集履歴を確認し、過去のバージョンへ復元できます。',
    ],

    // 詳細ページ
    'show' => [
        'heading' => 'リビジョン詳細',
        'description' => '選択したリビジョンと現在の内容の差分を表示します。',
    ],

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
    'type_manual' => '手動保存',
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
];
