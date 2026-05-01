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
    'heading' => 'Backup Settings',
    'description' => 'Configure default backup targets, retention periods, and whether to include application logs.',

    'form' => [
        'default_targets_label' => 'Default Backup Targets',
        'default_targets_help' => 'Targets selected here will be pre-checked when creating a new backup. The "Logs" target is optional because logs change frequently and can grow large.',
        'default_retention_label' => 'Default Retention Period (days)',
        'default_retention_help' => 'After this period, backups become eligible for cleanup via the database management page. Leave blank to keep backups indefinitely by default.',
        'save_button' => 'Save Settings',
    ],

    'targets' => [
        'database' => 'Database',
        'media' => 'Media',
        'private' => 'Private',
        'custom' => 'Custom',
        'logs' => 'Logs',
    ],

    'flash' => [
        'update_success' => 'Backup settings saved successfully.',
    ],

    'validation' => [
        'targets_required' => 'Please select at least one default backup target.',
        'target_invalid' => 'The selected backup target is invalid.',
        'retention_invalid' => 'Default retention period must be a positive integer between 1 and 3650 days.',
    ],

    'placeholder' => 'This page is under construction. The full UI will be available in an upcoming release.',
];
