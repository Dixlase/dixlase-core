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
    'member_required' => 'Name the member to record the uploads under with --as (a member ID or e-mail address).',
    'member_not_found' => 'No member matches ":identifier".',
    'member_inactive' => 'The member ":identifier" is not active.',
    'no_files' => 'Name at least one file to import.',
    'not_a_file' => 'Not a readable file: :path',
    'not_allowed' => 'The member :member may not upload media.',
    'status_imported' => 'imported',
    'status_reused' => 'already in the library',
    'status_failed' => 'failed',
    'column_file' => 'File',
    'column_result' => 'Result',
    'column_id' => 'ID',
    'column_path' => 'Path / error',
    'summary' => ':imported imported, :reused already in the library, :failed failed.',
];
