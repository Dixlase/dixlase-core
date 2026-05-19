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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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
    'heading' => 'テーマ詳細',
    'description' => 'テーマの詳細情報とスキャン結果を確認します。',
    'back_to_list' => 'テーマ一覧に戻る',
    'update_available' => 'が利用可能',

    // メタデータラベル
    'author' => '作者',
    'license' => 'ライセンス',
    'email' => 'メール',
    'url' => 'URL',
    'namespace' => '名前空間',
    'slug' => 'スラッグ',
    'package_name' => 'パッケージ名',
    'directory' => 'ディレクトリ',

    'last_scanned_at' => '最終スキャン日時: :date',

    'sections' => [
        'scan_result' => 'スキャン結果',
    ],

    'scan' => [
        'scan' => 'スキャン',
        'rescan' => '再スキャン',
        'not_scanned_message' => 'まだスキャンされていません。「スキャン」ボタンを押すと、テーマの権限・署名・互換性を確認します。',
    ],
];
