<?php

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
