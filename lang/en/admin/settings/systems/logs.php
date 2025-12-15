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
    'heading' => 'Log Information',
    'log_type_label' => 'Log Type:',
    'audit_db' => 'Audit Log',
    'audit_file' => 'System Log',
    
    // System Logs
    'system' => [
        'heading' => 'System Logs',
        'no_logs_found' => 'No logs found.',
        'activity' => 'Admin Activity',
        'dixlase' => 'Dixlase',
        'front_activity' => 'Front Activity',
        'front_error' => 'Front Error',
        'csp' => 'CSP Violations',
        'clear' => 'Clear Log',
        'clear_confirm' => 'Are you sure you want to clear the log file contents? This action cannot be undone.',
        'admin_logs_label' => 'Admin Logs:',
        'front_logs_label' => 'Front Page Logs:',
        'security_logs_label' => 'Security Logs:',
        'messages' => [
            'download_error' => 'Log file does not exist: :filename',
            'clear_success' => 'Log file cleared successfully: :filename',
            'clear_error' => 'Log file does not exist: :filename',
            'clear_failed' => 'Failed to clear log file: :error',
            'file_not_found' => 'Log file does not exist: :filename',
        ],
        'test_success' => 'Test log recorded: :results',
    ],
    
    // Audit Logs
    'audit' => [
        'heading' => 'Audit Logs',
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
    ],
];
