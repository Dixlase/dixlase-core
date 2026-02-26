# Dixlase Webhook Specification

## Overview

Dixlase provides a webhook delivery feature for external systems. When events occur, HTTP POST requests are sent to registered endpoints, enabling real-time integrations.

## Version

- **Specification Version**: v1
- **Status**: Alpha

## 1. Architecture

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│  Dixlase Event  │────▶│ WebhookDispatcher│────▶│   Queue (Job)   │
│  (DixlaseEvents)│     │                  │     │  (SendWebhook)  │
└─────────────────┘     └──────────────────┘     └────────┬────────┘
                                                          │
                                                          ▼
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│ External System │◀────│   HTTP POST      │◀────│  DixlaseSigner  │
│   (Endpoint)    │     │  (with signature)│     │  (HMAC-SHA256)  │
└─────────────────┘     └──────────────────┘     └─────────────────┘
```

## 2. Basic Usage

### 2.1 Sending via Facade

```php
use App\Facades\Webhook;

// Asynchronous dispatch (via queue)
Webhook::dispatch('dixlase.backup.completed', [
    'path' => '/backups/backup_20251221.zip',
    'size' => 1024000,
]);

// Synchronous dispatch (immediate)
Webhook::dispatchSync('order.created', [
    'order_id' => 123,
    'total' => 9800,
]);
```

### 2.2 Using WebhookDispatcher Directly

```php
use App\Services\WebhookDispatcher;

// Asynchronous dispatch
WebhookDispatcher::dispatch('event.name', $payload);

// Send to a specific webhook
WebhookDispatcher::dispatchTo($webhook, 'event.name', $payload);
```

### 2.3 Usage from Plugins

```php
use App\Facades\Webhook;
use App\Events\DixlaseEvents;

// Fire a plugin event (webhooks are automatically dispatched)
event(DixlaseEvents::PLUGIN_ACTIVATED, ['plugin' => 'MyPlugin']);

// Send a custom event
Webhook::dispatch('myplugin.order.shipped', [
    'order_id' => $order->id,
    'tracking_number' => $trackingNumber,
]);
```

## 3. Registering Webhooks

### 3.1 Direct Database Registration

```php
use App\Models\Webhook;

$webhook = Webhook::create([
    'name' => 'Slack Notification',
    'url' => 'https://hooks.slack.com/services/xxx',
    'secret' => Webhook::generateSecret(),
    'events' => ['dixlase.backup.completed', 'dixlase.backup.failed'],
    'is_active' => true,
    'environment' => 'live',
    'timeout' => 30,
    'retry_count' => 3,
    'headers' => [
        'X-Custom-Header' => 'value',
    ],
]);
```

### 3.2 Subscribing to All Events

```php
$webhook = Webhook::create([
    'name' => 'All Events Monitor',
    'url' => 'https://example.com/webhook',
    'secret' => Webhook::generateSecret(),
    'events' => null, // null or ['*'] to subscribe to all events
]);
```

## 4. Request Format

### 4.1 HTTP Headers

| Header | Description | Example |
|--------|-------------|---------|
| `Content-Type` | Content type | `application/json` |
| `User-Agent` | User agent | `Dixlase-Webhook/1.0` |
| `X-Dixlase-Event` | Event name | `dixlase.backup.completed` |
| `X-Dixlase-Delivery` | Delivery ID | `12345` |
| `X-Dixlase-Event-Id` | Event ID (UUID for idempotency) | `550e8400-e29b-41d4-a716-446655440000` |
| `X-Dixlase-Nonce` | Nonce (for replay prevention) | `a1b2c3d4...` (64 chars) |
| `X-Dixlase-Timestamp` | Timestamp | `1734700800` |
| `X-Dixlase-Signature` | Signature | `v1=abc123...` |

### 4.2 Request Body

```json
{
    "event": "dixlase.backup.completed",
    "timestamp": 1734700800,
    "data": {
        "path": "/backups/backup_20251221.zip",
        "size": 1024000,
        "duration": 45.2
    }
}
```

## 5. Signature Verification

Webhook requests include an `X-Dixlase-Signature` header. The receiving side can verify this signature to confirm the request's authenticity.

### 5.1 Signature Verification Example (PHP)

```php
function verifyWebhookSignature(
    string $payload,
    string $signature,
    string $timestamp,
    string $secret
): bool {
    // Check timestamp validity (within 5 minutes)
    if (abs(time() - (int)$timestamp) > 300) {
        return false;
    }

    // Build the signing string
    $bodyHash = hash('sha256', $payload);
    $signingString = implode("\n", [
        $timestamp,
        'POST',
        parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
        $bodyHash,
    ]);

    // Compute the signature
    $expectedSignature = 'v1=' . hash_hmac('sha256', $signingString, $secret);

    // Constant-time comparison
    return hash_equals($expectedSignature, $signature);
}

// Usage example
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_DIXLASE_SIGNATURE'] ?? '';
$timestamp = $_SERVER['HTTP_X_DIXLASE_TIMESTAMP'] ?? '';
$secret = 'whsec_your_webhook_secret';

if (!verifyWebhookSignature($payload, $signature, $timestamp, $secret)) {
    http_response_code(401);
    exit('Invalid signature');
}

// Signature verification succeeded, process the payload
$data = json_decode($payload, true);
```

### 5.2 Signature Verification Example (Node.js)

```javascript
const crypto = require('crypto');

function verifyWebhookSignature(payload, signature, timestamp, secret) {
    // Timestamp check
    const now = Math.floor(Date.now() / 1000);
    if (Math.abs(now - parseInt(timestamp)) > 300) {
        return false;
    }

    // Compute signature
    const bodyHash = crypto.createHash('sha256').update(payload).digest('hex');
    const signingString = [timestamp, 'POST', '/webhook', bodyHash].join('\n');
    const expectedSignature = 'v1=' + crypto
        .createHmac('sha256', secret)
        .update(signingString)
        .digest('hex');

    // Constant-time comparison
    return crypto.timingSafeEqual(
        Buffer.from(expectedSignature),
        Buffer.from(signature)
    );
}
```

## 6. Retry Mechanism

### 6.1 Retry Policy

When delivery fails, retries are performed with exponential backoff:

| Attempt | Wait Time |
|---------|-----------|
| 1st | Immediate |
| 2nd | After 1 minute |
| 3rd | After 5 minutes |
| 4th | After 30 minutes |

### 6.2 Retry Processing Command

```bash
# Process pending retries
php artisan webhooks:retry
```

### 6.3 Scheduler Registration

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    $schedule->command('webhooks:retry')->everyMinute();
}
```

## 7. Delivery Statuses

| Status | Description |
|--------|-------------|
| `pending` | Awaiting delivery |
| `success` | Delivery succeeded (2xx response) |
| `failed` | Delivery failed (retry limit reached) |
| `retrying` | Awaiting retry |

## 8. Available Events

All events defined in the `DixlaseEvents` class are eligible for webhook delivery:

### Backup Events
- `dixlase.backup.started`
- `dixlase.backup.completed`
- `dixlase.backup.failed`
- `dixlase.backup.cleanup.started`
- `dixlase.backup.cleanup.completed`

### Deploy Events
- `dixlase.deploy.before`
- `dixlase.deploy.after`
- `dixlase.deploy.failed`

### Plugin Events
- `dixlase.plugin.installed`
- `dixlase.plugin.activated`
- `dixlase.plugin.deactivated`
- `dixlase.plugin.uninstalled`

### Security Events
- `dixlase.integrity.scan.completed`
- `dixlase.security.alert`

### Other
- `dixlase.cache.cleared`
- `dixlase.maintenance.enabled`
- `dixlase.maintenance.disabled`

## 9. Retrieving Statistics

```php
use App\Facades\Webhook;

// Overall statistics
$stats = Webhook::getStats();
// [
//     'total' => 100,
//     'successful' => 95,
//     'failed' => 3,
//     'pending' => 2,
//     'success_rate' => 95.0,
// ]

// Statistics for a specific webhook
$stats = Webhook::getStats($webhookId, days: 30);
```

## 10. Idempotency

Webhooks may be delivered multiple times for the same event. The receiving side can use `X-Dixlase-Event-Id` to prevent duplicate processing.

### 10.1 How Event IDs Work

- Each webhook delivery is assigned a unique UUID v4 `event_id`
- Retries of the same event use the same `event_id`
- Manual retries use the same `event_id` with a new `nonce`

### 10.2 Idempotency Implementation Example on the Receiving Side (PHP)

```php
// Get the event ID
$eventId = $_SERVER['HTTP_X_DIXLASE_EVENT_ID'] ?? '';

// Check if already processed
if (ProcessedWebhook::where('event_id', $eventId)->exists()) {
    // Already processed - return 200 and exit
    http_response_code(200);
    exit('Already processed');
}

// Execute processing
processWebhook($payload);

// Record as processed
ProcessedWebhook::create(['event_id' => $eventId, 'processed_at' => now()]);
```

### 10.3 Replay Prevention

The `X-Dixlase-Nonce` header generates a unique value for each delivery. Combined with the timestamp, it can prevent replay attacks.

```php
// Nonce verification (optional)
$nonce = $_SERVER['HTTP_X_DIXLASE_NONCE'] ?? '';
$timestamp = $_SERVER['HTTP_X_DIXLASE_TIMESTAMP'] ?? '';

// Check used nonces (retain those within the last 5 minutes)
if (UsedNonce::where('nonce', $nonce)->exists()) {
    http_response_code(401);
    exit('Nonce already used');
}

// Record the nonce
UsedNonce::create(['nonce' => $nonce, 'timestamp' => $timestamp]);
```

## 11. Dead Letter

Webhooks that fail delivery after reaching the maximum retry count are recorded as "dead letters".

### 11.1 How Dead Letters Work

1. Delivery fails and the retry limit is reached
2. Details are recorded in the `webhook_dead_letters` table
3. An admin notification is sent (if configured)
4. Manual retry is available from the admin panel

### 11.2 Dead Letter Commands

```bash
# Display statistics
php artisan webhooks:dead-letters --stats

# Send notifications for unnotified dead letters
php artisan webhooks:dead-letters --notify

# Clean up old dead letters (older than 90 days)
php artisan webhooks:dead-letters --cleanup --days=90
```

### 11.3 Dead Letter Table Structure

| Column | Description |
|--------|-------------|
| `event_id` | Event ID (for idempotency tracking) |
| `event` | Event name |
| `payload` | Original payload |
| `last_error` | Last error message |
| `total_attempts` | Total number of attempts |
| `attempt_log` | Detailed log of each attempt (JSON) |
| `notified` | Notification sent flag |
| `manually_retried` | Manual retry flag |

## 12. Best Practices

### Receiving Side Implementation

1. **Always verify the signature** - Reject unauthorized requests
2. **Check the timestamp** - Prevent replay attacks
3. **Ensure idempotency with event IDs** - Handle the same event being delivered multiple times gracefully
4. **Verify the nonce** (optional) - Stronger replay prevention
5. **Respond promptly** - Return 200 within 5 seconds
6. **Process asynchronously** - Queue heavy processing

### Sending Side (Dixlase)

1. **Select appropriate events** - Subscribe only to needed events
2. **Store secrets securely** - Use environment variables or encrypted storage
3. **Monitor error logs** - Detect delivery failures

## 13. Features Planned for Post-Beta

- Webhook management UI
- Delivery log viewing and search
- Manual resend functionality
- Webhook test delivery
- Event filtering (conditional delivery)
- Rate limit configuration

---

**Document Version**: 1.0.0
**Last Updated**: 2025-12-21
**Author**: Dixlase Development Team
