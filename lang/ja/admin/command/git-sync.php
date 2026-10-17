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
    'scanning' => ':file から !plugins/ と !themes/ の再追跡ルールを探しています...',
    'file_not_found' => ':file が見つかりません。片付けるものはありません。',
    'nothing_to_remove' => '✓ :file に !plugins/ と !themes/ のルールはありません。片付けるものはありません。',
    'rules_found' => ':file から削除する行:',
    'dry_run' => 'ドライラン: 何も書き込んでいません。これらの行を削除するには --confirm を付けて再実行してください。',
    'not_writable' => ':file に書き込めません。所有者のユーザーで再実行してください。',
    'cleaned' => '✓ :file から :count 行を削除しました。',
    'add_deprecated' => '--add-plugin と --add-theme は非推奨で、何もしません。core は拡張機能のディレクトリを git で追跡し直さなくなりました。',
    'failed' => '操作に失敗しました: :error',
];
