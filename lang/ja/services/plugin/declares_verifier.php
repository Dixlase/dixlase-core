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
    'asset_declared_but_file_missing' => 'declares.assets.:scope に :file が宣言されていますが、resources/src/:file が存在しません。',
    'asset_files_exist_but_false' => 'resources/src/ にアセットファイルが存在しますが、declares.assets が false です。',
    'asset_files_exist_but_not_declared' => 'resources/src/ にアセットファイルが存在しますが、declares.assets が未宣言です。',
    'command_files_exist_but_false' => 'コマンドファイルが存在しますが、declares.commands が false です。',
    'commands_true_but_files_missing' => 'declares.commands は true ですが、コマンドファイルが存在しません。',
    'declared_contract_file_not_found' => '宣言されたコントラクト :contract のファイルが見つかりません。',
    'declares_config_true_but_path_missing' => 'declares.configs.:key は true ですが、:relativePath が存在しません。',
    'middleware_files_exist_but_false' => 'ミドルウェアファイルが存在しますが、declares.middleware が false です。',
    'middleware_true_but_files_missing' => 'declares.middleware は true ですが、ミドルウェアファイルが存在しません。',
    'migration_files_exist_but_false' => 'マイグレーションファイルが存在しますが、declares.migrations が false です。',
    'migrations_true_but_files_missing' => 'declares.migrations は true ですが、マイグレーションファイルが存在しません。',
    'path_exists_but_declares_config_false' => ':relativePath が存在しますが、declares.configs.:key が false です。',
];
