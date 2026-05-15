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

    'description' => 'テーマをアンインストールします（ファイルは保持されます）',
    'theme_name_prompt' => 'アンインストールするテーマ名',
    'theme_not_found' => 'テーマ \':themeName\' はデータベースに見つかりませんでした。',
    'not_installed' => 'テーマ \':themeName\' はインストールされていません。',
    'cannot_uninstall_enabled' => '有効なテーマ \':themeName\' をアンインストールできません。',
    'disable_first' => 'アンインストールする前に、まず `dls:theme:disable` コマンドでテーマを無効化してください。',
    'confirmation' => '本当にテーマ \':themeName\' をアンインストールしますか?',
    'cancelled' => 'アンインストールはキャンセルされました。',
    'uninstalled' => 'テーマをアンインストールしました: :themeName',
    'files_preserved' => 'テーマのファイルとディレクトリは保持されました。',
    'delete_hint' => 'ファイルを削除するには `php artisan theme:delete <directory>` コマンドを実行してください。',
];
