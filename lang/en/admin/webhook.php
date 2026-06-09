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
    // Dead letter related
    'dead_letter' => [
        'notification_subject' => 'Webhook Delivery Failure Notification',
        'notification_message' => 'Webhook ":webhook_name" failed to deliver ":event" event after :attempts attempts. Error: :error',
    ],

    // Header descriptions
    'headers' => [
        'event' => 'Event name',
        'delivery' => 'Delivery ID',
        'event_id' => 'Event ID (for idempotency)',
        'nonce' => 'Nonce (for replay prevention)',
        'timestamp' => 'Timestamp',
        'signature' => 'Signature',
    ],

    // Status
    'status' => [
        'pending' => 'Pending',
        'success' => 'Success',
        'failed' => 'Failed',
        'retrying' => 'Retrying',
        'dead_letter' => 'Dead Letter',
    ],

    // Admin panel (for beta and later)
    'admin' => [
        'title' => 'Webhook Management',
        'list' => 'Webhooks',
        'create' => 'Create Webhook',
        'edit' => 'Edit Webhook',
        'deliveries' => 'Delivery Log',
        'dead_letters' => 'Dead Letters',
        'stats' => 'Statistics',
    ],

    // Error messages
    'errors' => [
        'webhook_inactive' => 'Webhook is inactive',
        'delivery_not_found' => 'Delivery not found',
        'cannot_retry' => 'Cannot retry',
        'signature_invalid' => 'Invalid signature',
        'timestamp_expired' => 'Timestamp expired',
        'nonce_reused' => 'Nonce has been reused',
    ],
];
