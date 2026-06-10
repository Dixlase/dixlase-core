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
    'heading' => 'Restore History',
    'description' => 'View the history of restore operations and roll back to the previous state if needed.',

    'table' => [
        'caption' => 'Restore History',
        'restored_at' => 'Restored At',
        'backup' => 'From Backup',
        'targets' => 'Targets',
        'restored_by' => 'Restored By',
        'duration' => 'Duration',
        'status' => 'Status',
        'actions' => 'Actions',
        'no_records' => 'No restore operations have been performed yet.',
        'backup_deleted' => '(deleted)',
    ],

    'targets' => [
        'database' => 'Database',
        'media' => 'Media',
        'private' => 'Private',
        'custom' => 'Custom',
        'logs' => 'Logs',
        'core_source' => 'Core source',
        'plugins_all' => 'Plugins',
        'themes_all' => 'Themes',
    ],

    'statuses' => [
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'failed' => 'Failed',
        'rolled_back' => 'Rolled Back',
    ],

    'actions' => [
        'rollback' => 'Rollback',
    ],

    'rollback_modal' => [
        'title' => 'Rollback Restore',
        'message' => 'This will restore the state from before the original restore (using the safety snapshot). The current state will be overwritten. Continue?',
        'confirm_label' => 'Rollback',
        'cancel_label' => 'Cancel',
    ],

    'flash' => [
        'rollback_success' => 'Rollback completed successfully (:duration s).',
        'rollback_failed' => 'Rollback failed: :error',
        'rollback_unavailable' => 'This restore cannot be rolled back.',
    ],

    'placeholder' => 'This page is under construction. The full UI will be available in an upcoming release.',
];
