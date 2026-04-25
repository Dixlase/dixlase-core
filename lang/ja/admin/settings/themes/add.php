<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

return [
    'heading' => 'テーマを追加',
    'description' => 'ZIPファイルをアップロードして新しいテーマをインストールします。',
    'upload_title' => 'テーマをアップロード',
    'file_select_label' => 'ファイルを選択',
    'drag_drop_text' => 'ここにファイルをドラッグするか、クリックしてアップロード',
    'supported_format' => '対応形式:',
    'upload_limit' => 'アップロード可能ファイルサイズ上限:',
    'upload_button' => 'アップロードして追加',
    'uploading_title' => 'テーマをアップロード中',
    'uploading_wait' => 'ファイルのアップロードと展開が完了するまでお待ちください。',
    'name' => 'テーマ名',

    // タブ
    'tab_zip' => 'ZIPファイルから追加',
    'tab_online' => 'オンラインから追加',

    // オンラインインストール
    'online' => [
        'title' => 'オンラインからインストール',
        'description' => '設定された拡張機能ソースからテーマを検索・ダウンロードします。',
        'loading' => '利用可能なテーマを読み込み中...',
        'no_themes' => 'このソースから利用可能なテーマはありません。',
        'connection_error' => '拡張機能ソースへの接続に失敗しました。',
        'download' => 'ダウンロード',
        'downloading' => 'ダウンロード中...',
        'downloading_title' => 'テーマをダウンロード中',
        'downloading_wait' => 'ダウンロードが完了するまでお待ちください。',
    ],

    // コントローラーメッセージ
    'messages' => [
        'download_success' => 'テーマ「:name」のダウンロードが完了しました。一覧からインストールしてください。',
        'download_failed' => 'テーマのダウンロードに失敗しました: :error',
        'zip_extract_failed' => 'ZIPファイルの展開に失敗しました。',
        'no_valid_directory' => 'ZIP内に有効なテーマディレクトリが見つかりません。',
        'directory_exists' => "テーマディレクトリ ':directory' は既に存在します。",
        'theme_json_not_found' => 'theme.json が見つかりません。',
    ],
];
