<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
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
