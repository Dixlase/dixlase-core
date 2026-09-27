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
    'size_exceeded' => ':categoryのファイルサイズが上限を超えています（:size / 最大:max）',
    'category' => [
        'image' => '画像',
        'video' => '動画',
        'document' => 'ドキュメント',
        'archive' => 'アーカイブ',
        'other' => 'その他',
    ],
    'svg' => [
        'read_error' => 'SVGファイルの読み込みに失敗しました',
        'will_sanitize' => 'SVGファイルに危険な要素が含まれているため、サニタイズされます',
        'unsafe' => 'SVGファイルに危険な要素が含まれています。サニタイズが無効のためアップロードできません。',
    ],
    'zip' => [
        'file_not_found' => 'ZIPファイルが見つかりません',
        'invalid_zip' => '無効なZIPファイルです',
        'too_many_files' => 'ZIP内のファイル数が多すぎます（:count / 最大:max）',
        'path_traversal' => 'ZIPファイルに危険なパスが含まれています: :file',
        'forbidden_extension' => 'ZIPファイルに禁止された拡張子のファイルが含まれています: :file (:extension)',
        'hidden_file' => 'ZIPファイルに隠しファイルが含まれています: :file',
        'size_exceeded' => 'ZIP展開後のサイズが上限を超えています（:size / 最大:max）',
        'compression_bomb' => 'ZIP爆弾の可能性があります。圧縮率が高すぎます（:ratio倍 / 最大:max倍）',
    ],
    'mime' => [
        'unknown_extension' => '不明な拡張子です: :extension',
        'mime_mismatch' => 'ファイルの実体が拡張子と一致しません（:extension: 期待値 :expected, 検出値 :detected）',
        'invalid_image' => '画像ファイルが破損しているか、無効な形式です',
        'invalid_svg' => 'SVGファイルが無効な形式です',
        'magic_bytes_mismatch' => 'ファイルのマジックバイトが一致しません',
    ],
];
