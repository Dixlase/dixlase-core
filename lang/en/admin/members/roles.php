<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

return [
    'heading' => 'Permission Settings',
    'description' => 'Configure member permissions and access control.',

    'core_permissions' => 'Core Permissions',
    'core_permissions_description' => 'Set access permissions for Dixlase core features.',
    'excluded_items_note' => '* Dashboard and Profile are accessible to all users and are excluded from permission settings.',

    'plugin_permissions' => 'Plugin Permissions',
    'plugin_permissions_description' => 'Set access permissions for installed plugins.',

    'access_roles' => 'Edit Permission',
    'access_roles_help' => 'Set the minimum role required to edit this feature.',

    'view_roles' => 'View Permission',
    'view_roles_help' => 'Set the minimum role required to view this feature.',

    'overridden_from_default' => 'Modified from default value',
    'reset_to_default' => 'Reset to default',

    // Permission key labels (not in navigation)
    'permission_labels' => [
        'create_edit' => 'Member Create & Edit',
    ],

    // Validation
    'validation' => [
        'access_must_be_greater_than_view' => 'Edit permission must be equal to or greater than view permission (:menu_key)',
    ],
];
