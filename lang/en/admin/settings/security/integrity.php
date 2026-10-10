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
    'heading' => 'File Integrity Check',
    'title' => 'File Integrity Check',
    'description' => 'Verify the integrity of Dixlase core files and detect tampering.',
    'baseline_info' => 'Baseline Information',
    'baseline_version' => 'Version',
    'baseline_generated_at' => 'Generated At',
    'baseline_files_count' => 'File Count',
    'regenerate_baseline' => 'Regenerate Baseline',
    'regenerate_confirm' => 'Regenerate baseline? Current baseline will be overwritten.',
    'generate_baseline' => 'Generate Baseline',
    'no_baseline' => 'No Baseline Exists',
    'no_baseline_help' => 'To use file integrity check, please generate a baseline first.',
    'baseline_regenerated' => 'Baseline has been regenerated.',
    'baseline_regeneration_failed' => 'Failed to regenerate baseline.',
    'latest_scan' => 'Latest Scan Result',
    'run_scan' => 'Run Scan',
    'scan_confirm' => 'Do you want to run the file integrity scan? All core files will be checked.',
    'scan_date' => 'Scan Date',
    'files_scanned' => 'Files Scanned',
    'status' => 'Status',
    'status_ok' => 'OK',
    'status_warning' => 'Warning',
    'status_critical' => 'Critical',
    'trigger' => 'Trigger',
    'trigger_manual' => 'Manual',
    'trigger_schedule' => 'Schedule',
    'trigger_install' => 'Install',
    'trigger_update' => 'Update',
    'issues_found' => 'Issues Found',
    'changed_files' => 'Changed Files',
    'added_files' => 'Added Files',
    'removed_files' => 'Removed Files',
    'suspicious_files' => 'Suspicious Files',
    'view_details' => 'View Details',
    'no_issues' => 'All files are OK.',
    'no_scan_yet' => 'No scan has been performed yet.',
    'delete_audit' => 'Delete',
    'delete_audit_confirm' => 'Delete this scan history?',
    'audit_deleted' => 'Scan history has been deleted.',
    'bulk_delete_audits' => 'Bulk Delete Old History',
    'bulk_delete_days' => 'Days',
    'bulk_delete_days_help' => 'Delete scan history older than specified days',
    'bulk_delete_confirm' => 'Delete scan history older than :days days?',
    'audits_deleted' => ':count scan history records deleted.',
    'scan_history' => 'Scan History',
    'issues' => 'Issues',
    'scan_completed_with_issues' => 'Scanned :count files. Issues detected.',
    'scan_completed_ok' => 'Scanned :count files. No issues detected.',
    'back_to_list' => 'Back to List',
    'scan_details' => 'Scan Details',
    'scan_summary' => 'Scan Summary',
    'scope' => 'Scope',
    'file_path' => 'File Path',
    'expected_hash' => 'Expected Hash',
    'actual_hash' => 'Actual Hash',
    'suspicious_files_help' => 'These files were detected in locations where they should not normally exist.',
    'all_files_ok' => 'All Files OK',
    'all_files_ok_description' => 'All scanned files match the baseline.',
    'baseline_exists' => 'Baseline exists',
    'baseline_not_exists' => 'Baseline does not exist',
    'last_scan_result' => 'Last Scan Result',
    'scanned_at' => 'Scanned at',
    'scanning' => 'Scanning...',
    'regenerating' => 'Regenerating...',
    'regenerate_baseline_help' => 'After updating core files, regenerate the baseline.',
    'scan_failed' => 'Scan failed',
    'regenerate_failed' => 'Failed to regenerate baseline',

    // Scheduled security checks (daily rescan, audit log verification, core manifest)
    'scheduled' => [
        'view_details' => 'View details',
        'acknowledge' => 'Accept current status',
        'acknowledge_help' => 'These plugins or themes got worse in the daily rescan. If the change is expected (for example after an update you made), accept the current status to clear the alert. Otherwise, investigate before accepting.',
        'acknowledged' => 'The current status of the flagged plugins and themes has been accepted.',
        'alerts_heading' => 'Scheduled Check Alerts',
        'core_manifest_heading' => 'Core Signature Check',
        'core_manifest_help' => 'The latest check of the core files against the signed release manifest. It runs daily (dls:core:verify).',
        'core_manifest_not_checked' => 'Not checked yet. The check runs daily, or run php artisan dls:core:verify.',
        'core_manifest_title' => 'The core signature check failed',
        'core_manifest_message' => 'The core files could not be verified against the signed release manifest (:status). Check the Security → Integrity screen.',
        'audit_chain_title' => 'Audit log verification failed',
        'audit_chain_message' => 'The daily audit log verification found tampered entries (:tampered) or invalid daily seals (:seals).',
        'extensions_title' => 'A plugin or theme got worse in the daily rescan',
        'extensions_message' => 'Check: :names',
        'extension_mail_message' => 'The daily rescan found that :type ":name" got worse: :reasons.',
        'checked_at' => 'Checked At',
        'changed_count' => 'Changed Files',
        'extension' => 'Plugin / Theme',
        'reason' => 'Reason',
        'health' => 'Health',
        'signature' => 'Signature',
        'type_plugin' => 'plugin',
        'type_theme' => 'theme',
        'reason_health' => 'health status got worse',
        'reason_signature' => 'signature no longer verifies',
        'core_status' => [
            'genuine' => 'Genuine',
            'modified' => 'Modified',
            'unsigned' => 'Unsigned',
            'pending_verification' => 'Pending verification',
            'invalid' => 'Invalid signature',
            'error' => 'Check failed',
        ],
        'signature_status' => [
            'valid' => 'Valid',
            'invalid' => 'Invalid',
            'unsigned' => 'Unsigned',
            'expired' => 'Expired',
            'unknown_key' => 'Unknown key',
            'error' => 'Error',
            'pending_verification' => 'Pending verification',
        ],
    ],
];
