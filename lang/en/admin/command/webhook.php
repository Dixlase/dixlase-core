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
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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

    'dead_letters' => [
        'no_action' => 'No action specified. Use one of the following options:',
        'option_notify' => 'Send notifications for unnotified dead letters',
        'option_cleanup' => 'Clean up old dead letter records',
        'option_stats' => 'Show dead letter statistics',
        'sending_notifications' => 'Sending notifications for unnotified dead letters...',
        'notifications_sent' => ':count notification(s) sent.',
        'no_pending_notifications' => 'No pending notifications.',
        'cleaning_up' => 'Cleaning up dead letters older than :days days...',
        'cleanup_complete' => ':count record(s) deleted.',
        'stats_title' => 'Webhook Dead Letter Statistics (Last 30 Days)',
        'stat_name' => 'Metric',
        'stat_value' => 'Value',
        'total' => 'Total',
        'pending' => 'Pending',
        'notified' => 'Notified',
        'manually_retried' => 'Manually Retried',
        'by_event' => 'By Event:',
        'event' => 'Event',
        'count' => 'Count',
        'option_list' => 'List pending dead letters',
        'option_retry' => 'Retry a specific dead letter by ID',
        'option_retry_all' => 'Retry all pending dead letters',
        'no_pending' => 'No pending dead letters.',
        'pending_list_title' => '[Pending Dead Letters]',
        'col_id' => 'ID',
        'col_webhook' => 'Webhook',
        'col_attempts' => 'Attempts',
        'col_error' => 'Error',
        'col_created' => 'Created',
        'retry_hint' => 'To retry: php artisan webhooks:dead-letters --retry=ID',
        'not_found' => 'Dead letter ID :id not found.',
        'cannot_retry' => 'Cannot retry (webhook is inactive).',
        'retry_success' => '✅ Retried dead letter ID :id (delivery ID: :delivery_id)',
        'retry_queued' => 'Retry has been queued.',
        'retry_failed' => 'Retry failed: :error',
        'confirm_retry_all' => 'Retry all :count dead letter(s)?',
        'cancelled' => 'Operation cancelled.',
        'retry_all_complete' => '✅ Retry complete: :success succeeded, :failed failed',
    ],
];
