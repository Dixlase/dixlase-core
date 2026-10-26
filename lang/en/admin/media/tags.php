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
    'label' => 'Tags',
    'heading' => 'Tags',
    'description' => 'Add, rename and delete the tags of the media library.',
    'empty' => 'No tags yet. Add one here, or type a new name on a file\'s page or in selection mode.',
    'files_count' => ':count files',

    'filter' => [
        'all' => 'All',
        'manage' => 'Manage tags',
        'option' => ':name (:count)',
    ],

    'list' => [
        'name' => 'Tag',
        'files' => 'Files',
        'actions' => 'Actions',
        'show_files' => 'Show the files tagged :name',
    ],

    'create' => [
        'field' => 'New tag',
        'placeholder' => 'Tag name',
        'submit' => 'Add',
    ],

    'input' => [
        'placeholder' => 'Add a tag…',
        'create' => 'Create “:name” as a new tag',
        'remove' => 'Remove :name',
        'no_match' => 'No tag matches.',
    ],

    'rename' => [
        'action' => 'Rename :name',
        'title' => 'Rename tag',
        'field' => 'Tag name',
        'confirm' => 'Rename',
    ],

    'delete' => [
        'action' => 'Delete :name',
        'title' => 'Delete tag',
        'message' => 'Delete the tag “:name”?<br>It is removed from :count files. The files themselves stay.',
    ],

    'bulk' => [
        'add' => 'Add tags',
        'remove' => 'Remove tags',
        'add_title' => 'Add tags to the selected files',
        'remove_title' => 'Remove tags from the selected files',
        'add_message' => 'The tags are added to <strong>:count</strong> selected files.',
        'remove_message' => 'The tags are removed from <strong>:count</strong> selected files.',
        'apply' => 'Apply',
    ],

    'success' => [
        'bulk_updated' => 'Updated the tags of :count files.',
        'created' => 'The tag was added.',
        'renamed' => 'The tag was renamed.',
        'deleted' => 'The tag was deleted.',
    ],

    'error' => [
        'name_required' => 'Enter a tag name.',
        'name_taken' => 'A tag named “:name” already exists.',
        'nothing_to_change' => 'Choose at least one tag.',
        'bulk_partial' => 'The tags of :count files could not be updated.',
    ],
];
