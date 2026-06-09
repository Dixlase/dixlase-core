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
    'integrity' => [
        'building_chains' => 'Building hash chains...',
        'build_complete' => 'Set hash chain for :processed log(s). Remaining: :remaining',
        'build_errors' => ':count error(s) occurred:',
        'verifying_chain' => 'Verifying hash chain...',
        'chain_valid' => '✓ Hash chain is valid.',
        'chain_invalid' => '✗ Hash chain integrity issues detected!',
        'tampered_records' => 'Tampered records detected:',
        'and_more' => 'and :count more...',
        'verifying_seal' => 'Verifying daily seal for :date...',
        'seal_not_found' => 'No seal found for the specified date.',
        'seal_valid' => '✓ Daily seal is valid.',
        'seal_invalid' => '✗ Daily seal integrity issues detected!',
        'creating_seals' => 'Creating daily seals for the past :days days...',
        'no_pending_seals' => 'No pending seals to create.',
        'seals_created' => 'Created :count daily seal(s).',
        'creating_seal' => 'Creating daily seal for :date...',
        'seal_created' => 'Created daily seal for :date (log count: :log_count)',
        'no_logs_for_date' => 'No logs found for the specified date.',
        'invalid_date' => 'Invalid date format. Please use YYYY-MM-DD format.',
        'stats_title' => 'Audit Log Integrity Statistics',
        'daily_seals_title' => 'Daily Seals Statistics (Last 30 Days)',
        'stat_name' => 'Metric',
        'stat_value' => 'Value',
        'total' => 'Total',
        'valid' => 'Valid',
        'invalid' => 'Invalid',
        'total_logs' => 'Total Logs',
        'with_hash' => 'With Hash Chain',
        'without_hash' => 'Without Hash Chain',
        'verified' => 'Verified',
        'tampered' => 'Tampered',
        'unverified' => 'Unverified',
        'total_seals' => 'Total Seals',
        'valid_seals' => 'Valid Seals',
        'invalid_seals' => 'Invalid Seals',
        'sealed_logs' => 'Sealed Logs',
        'check' => 'Check',
        'result' => 'Result',
        'signature' => 'Signature',
        'log_count' => 'Log Count',
        'final_hash' => 'Final Hash',
        'chain' => 'Chain',
        'date' => 'Date',
        'status' => 'Status',
        'unknown_action' => 'Unknown action: :action',
        'available_actions' => 'Available actions:',
        'action_build' => 'Build hash chains',
        'action_verify' => 'Verify hash chains',
        'action_seal' => 'Create daily seals',
        'action_stats' => 'Show statistics',
    ],
];
