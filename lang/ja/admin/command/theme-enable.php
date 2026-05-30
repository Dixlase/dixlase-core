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
    'list_headers' => [
        '名前',
        'スラッグ',
        'インストール',
        'ステータス',
    ],
    'installed' => 'インストール済み',
    'not_installed_status' => '未インストール',
    'status_enabled' => '有効',
    'status_disabled' => '無効',
    'select_prompt' => '有効化するテーマを選択してください',
    'current_marker' => '(現在有効)',
    'selection_error' => 'テーマの選択に失敗しました。',
    'symlink_warning' => 'シンボリックリンクの更新に失敗しましたが、テーマの切り替えは完了しました。',
];
