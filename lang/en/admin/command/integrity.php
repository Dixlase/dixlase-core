<?php

return [

        // Baseline generation
        'generating_baseline' => 'Generating file integrity baseline...',
        'baseline_exists' => 'Existing baseline found (generated: :date, version: :version)',
        'overwrite_confirm' => 'Do you want to overwrite the existing baseline?',
        'cancelled' => 'Operation cancelled.',
        'scanning_files' => 'Scanning files...',
        'saving_baseline' => 'Saving baseline...',
        'baseline_success' => 'Baseline generated successfully.',
        'baseline_failed' => 'Failed to save baseline.',
        'baseline_generated' => 'File integrity baseline generated',
        'baseline_regenerated' => 'File integrity baseline regenerated',

        // Scan
        'starting_scan' => 'Starting file integrity scan...',
        'scope_not_supported' => 'Scope ":scope" is not currently supported.',
        'using_core_scope' => 'Using core scope.',
        'scanning' => 'Scanning...',
        'scan_error' => 'Scan error: :error',

        // Result display
        'status' => 'Status',
        'status_ok' => 'OK',
        'status_warning' => 'Warning',
        'status_critical' => 'Critical',
        'files_scanned' => 'Files scanned',
        'duration' => 'Duration',
        'summary' => 'Summary',

        // Issue details
        'changed_files' => 'Changed files (:count)',
        'added_files' => 'Added files (:count)',
        'removed_files' => 'Removed files (:count)',
        'suspicious_files' => 'Suspicious files (:count)',

        // Suspicious file reasons
        'reason_php_in_uploads' => 'PHP file in uploads directory',
        'reason_unknown_php_in_public' => 'Unknown PHP file in public root',

        // Critical warning
        'critical_warning' => '⚠️ Critical security issues detected!',
        'critical_action_1' => '1. Review suspicious files immediately.',
        'critical_action_2' => '2. Remove any unauthorized files found.',
        'critical_action_3' => '3. Conduct a security audit of your system.',

        // Summary messages
        'summary_changed' => ':count file(s) changed',
        'summary_added' => ':count file(s) added',
        'summary_removed' => ':count file(s) removed',
        'summary_suspicious' => ':count suspicious file(s) found',
        'summary_ok' => 'No issues detected',

        // Table display
        'item' => 'Item',
        'value' => 'Value',
        'files_count' => 'Files count',
        'app_version' => 'App version',
        'hash_algo' => 'Hash algorithm',
        'generated_at' => 'Generated at',

        // Notification
        'notification_disabled' => 'Notification is disabled.',
        'no_notification_email' => 'Notification email address is not configured.',
        'notification_sent' => 'Alert notification sent to: :email',
        'notification_failed' => 'Failed to send notification: :error',
];
