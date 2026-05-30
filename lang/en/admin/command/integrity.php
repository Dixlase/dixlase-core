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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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
    'starting_scan' => 'Starting file integrity scan...',
    'scope_not_supported' => 'Scope ":scope" is not currently supported.',
    'using_core_scope' => 'Using core scope.',
    'scanning' => 'Scanning...',
    'scan_error' => 'Scan error: :error',
    'status' => 'Status',
    'status_ok' => 'OK',
    'status_warning' => 'Warning',
    'status_critical' => 'Critical',
    'files_scanned' => 'Files scanned',
    'duration' => 'Duration',
    'summary' => 'Summary',
    'changed_files' => 'Changed files (:count)',
    'added_files' => 'Added files (:count)',
    'removed_files' => 'Removed files (:count)',
    'suspicious_files' => 'Suspicious files (:count)',
    'reason_php_in_uploads' => 'PHP file in uploads directory',
    'reason_unknown_php_in_public' => 'Unknown PHP file in public root',
    'critical_warning' => '⚠️ Critical security issues detected!',
    'critical_action_1' => '1. Review suspicious files immediately.',
    'critical_action_2' => '2. Remove any unauthorized files found.',
    'critical_action_3' => '3. Conduct a security audit of your system.',
    'summary_changed' => ':count file(s) changed',
    'summary_added' => ':count file(s) added',
    'summary_removed' => ':count file(s) removed',
    'summary_suspicious' => ':count suspicious file(s) found',
    'summary_ok' => 'No issues detected',
    'item' => 'Item',
    'value' => 'Value',
    'files_count' => 'Files count',
    'app_version' => 'App version',
    'hash_algo' => 'Hash algorithm',
    'generated_at' => 'Generated at',
    'notification_disabled' => 'Notification is disabled.',
    'no_notification_email' => 'Notification email address is not configured.',
    'notification_sent' => 'Alert notification sent to: :email',
    'notification_failed' => 'Failed to send notification: :error',
];
