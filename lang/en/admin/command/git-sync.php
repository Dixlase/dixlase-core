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
    'scanning' => 'Looking for !plugins/ and !themes/ re-include rules in :file ...',
    'file_not_found' => ':file not found. Nothing to clean up.',
    'nothing_to_remove' => '✓ :file has no !plugins/ or !themes/ rules. Nothing to clean up.',
    'rules_found' => 'Lines to remove from :file:',
    'dry_run' => 'Dry run: nothing was written. Re-run with --confirm to remove these lines.',
    'not_writable' => ':file is not writable. Re-run as the user that owns it.',
    'cleaned' => '✓ Removed :count line(s) from :file.',
    'add_deprecated' => 'The --add-plugin and --add-theme options are deprecated and do nothing: core no longer re-includes extension directories in git.',
    'failed' => 'Operation failed: :error',
];
