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
    'invalid_slug' => 'Invalid slug ":slug". Use lowercase letters, digits and hyphens (up to 100 characters).',
    'slug_taken' => 'A custom role with the slug ":slug" already exists (it may be deleted).',
    'invalid_base' => 'Invalid base role ":base". Use admin, editor, contributor or guest. Super Admin cannot be a base role.',
    'invalid_actor_type' => 'Invalid actor type ":type". Use human, ai or service.',
    'invalid_max_members' => 'Invalid member limit ":value". Use a positive whole number.',
    'created' => 'Custom role ":slug" created, based on :base.',
    'role_not_found' => 'Custom role not found: :slug',
    'invalid_type' => 'Invalid type ":type". Use grant, deny or clear.',
    'unknown_permission' => 'Unknown core permission ":permission". Pass --plugin=<directory> for a plugin menu key.',
    'unknown_plugin_key' => 'The plugin ":plugin" does not declare the menu key ":key" in its config/admin/roles.php.',
    'site_not_found' => 'Site not found: :site',
    'override_set' => 'Custom role ":slug": :permission set to :type on site :site.',
    'member_not_found' => 'Member not found: :member',
    'super_admin_refused' => ':member is a Super Admin and cannot be given a custom role. Change their role first.',
    'role_full' => 'Custom role ":slug" already has its maximum of :max members.',
    'assigned' => 'Custom role ":slug" assigned to :member. Their role is now :base.',
    'assigned_inactive' => 'Custom role ":slug" is inactive: its overrides do not apply until it is activated.',
    'unassigned' => 'Custom role removed from :member. Their role is unchanged.',
    'no_roles' => 'No custom roles exist.',
    'no_overrides' => 'Custom role ":slug" has no overrides.',
    'col_slug' => 'Slug',
    'col_name' => 'Name',
    'col_base' => 'Base role',
    'col_actor_type' => 'Actor type',
    'col_active' => 'Active',
    'col_members' => 'Members',
    'col_site' => 'Site',
    'col_permission' => 'Permission',
    'col_type' => 'Type',
    'yes' => 'Yes',
    'no' => 'No',
];
