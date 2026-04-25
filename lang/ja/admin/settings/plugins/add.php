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
    'heading' => 'プラグインを追加',
    'description' => 'ZIPファイルをアップロードして新しいプラグインをインストールします。',
    'upload_title' => 'プラグインアップロード',
    'file_select_label' => 'ZIPファイルを選択:',
    'drag_drop_text' => 'ここにファイルをドラッグするか、クリックしてアップロード',
    'supported_format' => '対応形式:',
    'upload_limit' => 'アップロード可能ファイルサイズ上限:',
    'upload_button' => 'アップロードして追加',
    'uploading_title' => 'プラグインをアップロード中',
    'uploading_wait' => 'ファイルのアップロードと展開が完了するまでお待ちください。',
    'enable_plugin_text' => 'プラグインを有効化する場合は',
    'enable_from_here' => 'こちら',
    'enable_instruction' => 'から有効化してください。',
    'name' => 'プラグイン名',

    // タブ
    'tab_zip' => 'ZIPファイルから追加',
    'tab_online' => 'オンラインから追加',

    // オンラインインストール
    'online' => [
        'title' => 'オンラインからインストール',
        'description' => '設定された拡張機能ソースからプラグインを検索・ダウンロードします。',
        'loading' => '利用可能なプラグインを読み込み中...',
        'no_plugins' => 'このソースから利用可能なプラグインはありません。',
        'connection_error' => '拡張機能ソースへの接続に失敗しました。',
        'source_not_configured' => '拡張機能ソースが設定されていません。',
        'configure_link' => 'セキュリティ設定で設定する',
        'download' => 'ダウンロード',
        'downloading' => 'ダウンロード中...',
        'downloading_title' => 'プラグインをダウンロード中',
        'downloading_wait' => 'ダウンロードが完了するまでお待ちください。',
        'version' => 'v:version',
        'by_author' => ':author 作',
    ],

    // コントローラーメッセージ
    'messages' => [
        'upload_success' => 'プラグインのアップロードが完了しました。一覧からインストールしてください。',
        'upload_failed' => 'プラグインのアップロードに失敗しました: :error',
        'zip_extract_failed' => 'ZIPファイルの展開に失敗しました。',
        'no_valid_directory' => 'ZIP内に有効なプラグインディレクトリが見つかりません。',
        'directory_exists' => "プラグインディレクトリ ':directory' は既に存在します。",
        'composer_not_found' => 'composer.json が見つかりません。',
        'download_success' => 'プラグイン「:name」のダウンロードが完了しました。一覧からインストールしてください。',
        'download_failed' => 'プラグインのダウンロードに失敗しました: :error',
    ],
];
