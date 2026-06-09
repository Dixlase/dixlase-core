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
    'description' => 'プラグインのファイルとディレクトリを削除します（アンインストール済みである必要があります）。',
    'not_found' => 'プラグインディレクトリ \':directory\' は見つかりません。',
    'still_installed' => 'プラグイン \':pluginName\' はまだインストールされています。',
    'uninstall_first' => '削除する前に、まず `plugin:uninstall` コマンドでプラグインをアンインストールしてください。',
    'confirm' => 'プラグインディレクトリ \':directory\' とその中のすべてのファイルを削除しますか？この操作は取り消せません。',
    'cancelled' => '削除がキャンセルされました。',
    'deleted' => 'プラグインディレクトリ \':path\' を削除しました。',
    'failed' => 'プラグインディレクトリの削除に失敗しました: :error',
    'completed' => 'プラグイン \':directory\' の削除が完了しました。',
];
