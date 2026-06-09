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
    'description' => 'テーマを無効化します',
    'theme_name_prompt' => '無効化するテーマ名',
    'no_enabled_themes' => '有効なテーマが見つかりませんでした。',
    'theme_not_found' => 'テーマ \':themeName\' が見つかりません。',
    'not_installed' => 'テーマ \':themeName\' はインストールされていません。',
    'already_disabled' => 'テーマ \':themeName\' は既に無効化されています。',
    'disabled' => 'テーマを無効化しました: :themeName',
    'list_headers' => [
        '名前',
        'スラッグ',
    ],
    'disable_help' => 'テーマを無効化するには、次のコマンドを実行してください: php artisan dls:theme:disable <theme-name>',
];
