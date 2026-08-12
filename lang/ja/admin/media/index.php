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
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
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
    'heading' => 'メディア管理',
    'description' => 'アップロードされた画像、動画、ドキュメントなどのメディアファイルを管理します。',
    'upload_new_file' => '新しいファイルをアップロード',
    'no_files' => 'ファイルがありません',
    'upload_first_file' => '最初のファイルをアップロードしてください',
    'preview' => 'プレビュー',
    'download' => 'ダウンロード',
    'delete' => '削除',
    'delete_message' => '「{fileName}」を削除しますか？<br>この操作は取り消せません。',

    'success' => [
        'uploaded' => 'ファイルがアップロードされました。',
        'uploaded_count' => ':count件のファイルがアップロードされました。',
        'deleted' => 'ファイルが削除されました。',
        'updated' => 'メディア情報が更新されました。',
    ],

    'error' => [
        'save_failed' => 'ファイルの保存に失敗しました。',
        'file_not_exists' => 'ファイルが存在しません。',
    ],

    'search' => [
        'file_name_placeholder' => 'ファイル名で検索',
        'date_from' => '開始日',
        'date_to' => '終了日',
    ],
];
