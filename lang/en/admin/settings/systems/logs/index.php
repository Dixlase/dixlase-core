<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

return [
    'heading' => 'Log Management',
    'description' => 'View, search, and export system operation history. Track administrator actions and use for security audits.',
    'log_type_label' => 'Log Type',
    'audit_db' => 'Audit Log',
    'audit_file' => 'File Log',
    'show' => [
        'heading' => 'Audit Log Details',
    ],
    'detail_title' => 'Audit Log Details',
    'filters' => 'Filters',
    'search' => 'Search',
    'search_placeholder' => 'Search by actor, target, action, IP...',
    'category' => 'Category',
    'action' => 'Action',
    'severity' => 'Severity',
    'outcome' => 'Outcome',
    'actor' => 'Actor',
    'target' => 'Target',
    'ip_address' => 'IP Address',
    'user_agent' => 'User Agent',
    'request_id' => 'Request ID',
    'session_id' => 'Session ID',
    'plugin' => 'Plugin',
    'occurred_at' => 'Occurred At',
    'date_from' => 'From Date',
    'date_to' => 'To Date',
    'no_logs' => 'No audit logs found.',
    'table_not_exists' => 'Audit log table does not exist. Please run migrations.',
    'export_csv' => 'Export CSV',
    'basic_info' => 'Basic Information',
    'actor_target' => 'Actor & Target',
    'context' => 'Context',
    'request_info' => 'Request Information',
    'related_logs' => 'Related Logs',
    'same_request' => 'Logs in Same Request',
    'meta_info' => 'Meta Information',
    'system' => 'System',
    'impersonated_by' => 'Impersonated By',
    'changes' => 'Changes',
    'field' => 'Field',
    'before' => 'Before',
    'after' => 'After',
    'show_raw_json' => 'Show Raw JSON',
    'hide_raw_json' => 'Hide Raw JSON',
    'cleanup_title' => 'Log Cleanup',
    'cleanup_days' => 'Retention Days',
    'cleanup_button' => 'Delete Old Logs',
    'cleanup_description' => 'Delete audit logs older than the specified number of days. Setting 0 will delete all logs.',
    'cleanup_confirm' => 'Are you sure you want to delete audit logs older than the specified days? This action cannot be undone.',
    'cleanup_success' => 'Successfully deleted :count audit log(s).',
    'categories' => [
        'auth' => 'Authentication',
        'account' => 'Account',
        'device' => 'Device',
        'security' => 'Security',
        'session' => 'Session',
        'extension' => 'Extension',
        'content' => 'Content',
        'system' => 'System',
        'plugin' => 'Plugin',
    ],
    'severities' => [
        'debug' => 'Debug',
        'info' => 'Info',
        'notice' => 'Notice',
        'warning' => 'Warning',
        'error' => 'Error',
        'critical' => 'Critical',
        'alert' => 'Alert',
        'emergency' => 'Emergency',
    ],
    'outcomes' => [
        'success' => 'Success',
        'failure' => 'Failure',
        'denied' => 'Denied',
        'pending' => 'Pending',
        'unknown' => 'Unknown',
    ],
];
