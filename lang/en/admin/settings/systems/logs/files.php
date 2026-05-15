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
    'heading' => 'File Logs',
    'description' => 'View, download, and clear application log files by date.',
    'date_latest' => 'Latest',
    'date_select' => 'Select Date',
    'no_logs_found' => 'No logs found.',
    'activity' => 'Activity',
    'error' => 'Error',
    'dixlase' => 'Dixlase',
    'front_activity' => 'Activity',
    'front_error' => 'Error',
    'browser' => 'Browser',
    'csp' => 'CSP',
    'audit' => 'Audit',
    'clear' => 'Clear Log',
    'clear_confirm' => 'Are you sure you want to clear the log file contents? This action cannot be undone.',
    'clear_days_label' => 'Days to Delete',
    'clear_modal' => [
        'title' => 'Clear Log Files',
        'confirm_message' => 'Are you sure you want to clear the log files?<br>This action cannot be undone.',
    ],
    'clear_all_success' => 'All log files cleared successfully (:count files)',
    'clear_old_success' => 'Deleted log files older than :days days (:count files)',
    'clear_failed' => 'Failed to clear log files: :error',
    'cleanup_title' => 'Log File Cleanup',
    'cleanup_description' => 'Delete log files older than the specified number of days. Specify 0 days to clear all log files.',
    'admin_logs_label' => 'Admin',
    'front_logs_label' => 'Front',
    'security_logs_label' => 'Security',
    'browser_logs_label' => 'Browser',
    'messages' => [
        'download_error' => 'Log file does not exist: :filename',
        'clear_success' => 'Log file cleared successfully: :filename',
        'clear_error' => 'Log file does not exist: :filename',
        'clear_failed' => 'Failed to clear log file: :error',
        'file_not_found' => 'Log file does not exist: :filename',
    ],
    'test_success' => 'Test log recorded: :results',
    'level_filter' => [
        'label' => 'Log Level',
        'error' => 'Error',
        'warning' => 'Warning',
        'normal' => 'Normal',
        'debug' => 'Debug',
    ],
];
