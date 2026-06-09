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
    'asset_declared_but_file_missing' => ':file is declared in declares.assets.:scope, but resources/src/:file does not exist.',
    'asset_files_exist_but_false' => 'Asset files exist in resources/src/ but declares.assets is false.',
    'asset_files_exist_but_not_declared' => 'Asset files exist in resources/src/ but declares.assets is not declared.',
    'command_files_exist_but_false' => 'Command files exist but declares.commands is false.',
    'commands_true_but_files_missing' => 'declares.commands is true but command files do not exist.',
    'declared_contract_file_not_found' => 'File for declared contract :contract not found.',
    'declares_config_true_but_path_missing' => 'declares.configs.:key is true, but :relativePath does not exist.',
    'middleware_files_exist_but_false' => 'Middleware files exist but declares.middleware is false.',
    'middleware_true_but_files_missing' => 'declares.middleware is true but middleware files do not exist.',
    'migration_files_exist_but_false' => 'Migration files exist but declares.migrations is false.',
    'migrations_true_but_files_missing' => 'declares.migrations is true but migration files do not exist.',
    'path_exists_but_declares_config_false' => ':relativePath exists, but declares.configs.:key is false.',
];
