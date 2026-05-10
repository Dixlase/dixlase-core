# Dixlase Events API Specification

## Overview

This document defines the Events API for Dixlase. Events provide extension points for plugins to hook into core functionality without modifying the core codebase.

## Version

- **Specification Version**: 1.0
- **Status**: Stable

## 1. Event System Architecture

### 1.1 Design Philosophy

- **Loose coupling**: Core fires events, plugins listen
- **Predictable payloads**: Each event has a documented payload structure
- **Lifecycle coverage**: Events for before/after/failed states
- **Namespace convention**: `dixlase.{category}.{action}`

### 1.2 Event Flow

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   Core Action   │ ──▶ │   Fire Event    │ ──▶ │ Plugin Listener │
│  (e.g. backup)  │     │ (with payload)  │     │ (handle event)  │
└─────────────────┘     └─────────────────┘     └─────────────────┘
```

## 2. Event Categories

### 2.1 Backup Events

| Event | Constant | Payload |
|-------|----------|---------|
| Backup started | `BACKUP_STARTED` | `['type' => string, 'options' => array]` |
| Backup completed | `BACKUP_COMPLETED` | `['type' => string, 'path' => string, 'size' => int, 'duration' => float]` |
| Backup failed | `BACKUP_FAILED` | `['type' => string, 'error' => string, 'exception' => Throwable\|null]` |
| Cleanup started | `BACKUP_CLEANUP_STARTED` | `['files' => array, 'retention_days' => int]` |
| Cleanup completed | `BACKUP_CLEANUP_COMPLETED` | `['deleted_count' => int, 'freed_bytes' => int]` |
| Restore started | `BACKUP_RESTORE_STARTED` | `['path' => string, 'type' => string]` |
| Restore completed | `BACKUP_RESTORE_COMPLETED` | `['path' => string, 'type' => string, 'duration' => float]` |
| Restore failed | `BACKUP_RESTORE_FAILED` | `['path' => string, 'error' => string]` |

### 2.2 Deploy Events

| Event | Constant | Payload |
|-------|----------|---------|
| Deploy before | `DEPLOY_BEFORE` | `['environment' => string, 'targets' => array, 'options' => array]` |
| Deploy after | `DEPLOY_AFTER` | `['environment' => string, 'targets' => array, 'duration' => float]` |
| Deploy failed | `DEPLOY_FAILED` | `['environment' => string, 'error' => string, 'exception' => Throwable\|null]` |
| Sync before | `DEPLOY_SYNC_BEFORE` | `['environment' => string, 'target' => string, 'files' => array]` |
| Sync after | `DEPLOY_SYNC_AFTER` | `['environment' => string, 'target' => string, 'synced_count' => int]` |
| Database before | `DEPLOY_DATABASE_BEFORE` | `['environment' => string, 'tables' => array]` |
| Database after | `DEPLOY_DATABASE_AFTER` | `['environment' => string, 'tables' => array, 'rows_affected' => int]` |

### 2.3 Translation Events

| Event | Constant | Payload |
|-------|----------|---------|
| Locale changed | `LOCALE_CHANGED` | `['locale' => string, 'previous' => string]` |
| Model retrieved | `TRANSLATION_MODEL_RETRIEVED` | `[Model $model]` |
| Model saving | `TRANSLATION_MODEL_SAVING` | `[Model $model]` |
| Model saved | `TRANSLATION_MODEL_SAVED` | `[Model $model]` |
| Model deleted | `TRANSLATION_MODEL_DELETED` | `[Model $model]` |
| Field updated | `TRANSLATION_FIELD_UPDATED` | `[Model $model, string $field, mixed $value, string $locale]` |
| Field deleted | `TRANSLATION_FIELD_DELETED` | `[Model $model, string $field, string\|null $locale]` |

### 2.4 Plugin Events

| Event | Constant | Payload |
|-------|----------|---------|
| Installing | `PLUGIN_INSTALLING` | `['plugin' => string, 'version' => string]` |
| Installed | `PLUGIN_INSTALLED` | `['plugin' => string, 'version' => string]` |
| Activating | `PLUGIN_ACTIVATING` | `['plugin' => string]` |
| Activated | `PLUGIN_ACTIVATED` | `['plugin' => string]` |
| Deactivating | `PLUGIN_DEACTIVATING` | `['plugin' => string]` |
| Deactivated | `PLUGIN_DEACTIVATED` | `['plugin' => string]` |
| Uninstalling | `PLUGIN_UNINSTALLING` | `['plugin' => string, 'delete_data' => bool]` |
| Uninstalled | `PLUGIN_UNINSTALLED` | `['plugin' => string]` |
| Updating | `PLUGIN_UPDATING` | `['plugin' => string, 'from_version' => string, 'to_version' => string]` |
| Updated | `PLUGIN_UPDATED` | `['plugin' => string, 'from_version' => string, 'to_version' => string]` |

### 2.5 Theme Events

| Event | Constant | Payload |
|-------|----------|---------|
| Activating | `THEME_ACTIVATING` | `['theme' => string, 'previous' => string\|null]` |
| Activated | `THEME_ACTIVATED` | `['theme' => string, 'previous' => string\|null]` |

### 2.6 Cache Events

| Event | Constant | Payload |
|-------|----------|---------|
| Clearing | `CACHE_CLEARING` | `['type' => string]` |
| Cleared | `CACHE_CLEARED` | `['type' => string]` |

### 2.7 Maintenance Events

| Event | Constant | Payload |
|-------|----------|---------|
| Enabled | `MAINTENANCE_ENABLED` | `['secret' => string\|null, 'retry' => int\|null]` |
| Disabled | `MAINTENANCE_DISABLED` | `[]` |

### 2.8 Security Events

| Event | Constant | Payload |
|-------|----------|---------|
| Integrity scan started | `INTEGRITY_SCAN_STARTED` | `['scope' => string]` |
| Integrity scan completed | `INTEGRITY_SCAN_COMPLETED` | `['scope' => string, 'status' => string, 'changes' => array]` |
| Security alert | `SECURITY_ALERT` | `['type' => string, 'details' => array]` |

### 2.9 AuditLogCreated event (frozen DTO payload)

`App\Events\AuditLogCreated` is fired after every audit log record is created. Unlike the events listed above (which carry positional argument arrays), this event delivers a frozen, typed payload object — `App\DTO\Audit\AuditLogPayload` — so SIEM exporters and external integrations can rely on a stable contract.

#### Schema version

The payload schema is versioned through `AuditLogCreated::SCHEMA_VERSION` (currently **1**). The version is also embedded in the payload itself as the `version` field, so JSON-serialised deliveries (webhooks, SIEM streams) can be routed without consulting the constant.

**Compatibility policy**

- Within the same major version, fields may only be **added**. Existing field names, types, and nullability are frozen.
- Removing a field, renaming a field, changing a field's type, or flipping its nullability **bumps `SCHEMA_VERSION`**.
- The compatibility policy is the same as the broader Plugin API stability pledge — see [`PLUGIN-API.md`](../../../PLUGIN-API.md#stability-pledge).

#### Payload fields (v1)

| Field | Type | Nullable | Description |
|---|---|---|---|
| `version` | `int` | no | Schema version. Equal to `AuditLogCreated::SCHEMA_VERSION` at dispatch time. |
| `id` | `int` | no | Primary key of the `audit_logs` row. |
| `occurred_at` | `string` | no | ISO 8601 timestamp (e.g. `"2026-05-10T12:34:56+00:00"`). |
| `severity` | `string` | no | One of `AuditLog::SEVERITY_*` (`debug`, `info`, `notice`, `warning`, `error`, `critical`, `alert`, `emergency`). |
| `outcome` | `string` | no | One of `AuditLog::OUTCOME_*` (`success`, `failure`, `denied`, `pending`, `unknown`). |
| `category` | `string` | no | One of `AuditLog::CATEGORY_*` (`auth`, `account`, `device`, `security`, `session`, `extension`, `content`, `system`, `plugin`, …). |
| `action` | `string` | no | Audit action key. Naming format: see [`docs/development/naming.md`](../naming.md#audit-log-actions). |
| `site_id` | `int` | yes | Site scope. `null` for global / cross-site events. |
| `actor_type` | `string` | yes | Polymorphic actor class name (e.g. `"App\\Models\\Member"`). `null` for system actions. |
| `actor_id` | `int` | yes | Polymorphic actor primary key. |
| `actor_name` | `string` | yes | Snapshot of the actor display name at event time. |
| `impersonated_by_id` | `int` | yes | Real operator ID when impersonation is active. |
| `target_type` | `string` | yes | Polymorphic target class name. |
| `target_id` | `int` | yes | Polymorphic target primary key. |
| `target_label` | `string` | yes | Human-readable target identifier (email, title, slug, …). |
| `ip_address` | `string` | yes | Source IP (IPv4 or IPv6). |
| `user_agent` | `string` | yes | HTTP `User-Agent`. |
| `request_id` | `string` | yes | Request correlation ID. |
| `session_id` | `string` | yes | Session identifier. |
| `plugin_name` | `string` | yes | Plugin slug. `null` for core actions. |
| `plugin_version` | `string` | yes | Plugin version. `null` for core actions. |
| `actor_source` | `string` | yes | One of `AuditLog::ACTOR_SOURCE_*` (`web`, `api`, `cli`, `scheduler`, `ai_plugin`, `webhook`, `queue`). |
| `is_ai_generated` | `bool` | no | `true` when the action originated from an AI plugin. |
| `context` | `array<string,mixed>` | no | Free-form JSON-serialisable context. May be empty (`[]`) but is never `null`. |
| `record_hash` | `string` | yes | SHA-256 of this row in the tamper-evident chain. |
| `chain_sequence` | `int` | yes | Sequence number within the tamper-evident chain. |

#### What is intentionally NOT exposed

The following columns of the `audit_logs` row are deliberately omitted from the payload because they belong to the integrity-chain implementation, not to subscribers:

- `previous_hash`
- `hash_algorithm`
- `verification_status`
- `last_verified_at`
- `schema_version` (the per-row column; the payload's own `version` field replaces it for subscribers)

If you need the full row for forensic purposes inside the application, query `AuditLog::find($payload->id)` directly. SIEM consumers should rely only on the documented payload fields.

#### Listening to the event

```php
use App\Events\AuditLogCreated;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Event;

Event::listen(AuditLogCreated::class, function (AuditLogCreated $event) {
    // Branch on schema version if your listener supports multiple versions
    if ($event->payload->version !== 1) {
        return;
    }

    if ($event->payload->severity === AuditLog::SEVERITY_CRITICAL) {
        // Forward to SIEM
        Http::post($siemEndpoint, $event->payload->toArray());
    }
});
```

#### Webhook delivery

When delivered through `WebhookDispatcher`, the payload's `toArray()` shape becomes the JSON body. Subscribers consuming the webhook see exactly the same field set as in-process listeners:

```json
{
  "event": "dixlase.audit.log.created",
  "data": {
    "version": 1,
    "id": 42,
    "occurred_at": "2026-05-10T12:34:56+00:00",
    "severity": "info",
    "outcome": "success",
    "category": "auth",
    "action": "login",
    ...
  },
  "timestamp": 1746880496
}
```

#### Naming and identifier conventions

For naming rules of `action`, `category`, `actor_source`, etc., see [`docs/development/naming.md`](../naming.md).

## 3. Usage

### 3.1 Listening to Events

```php
use App\Events\DixlaseEvents;
use Illuminate\Support\Facades\Event;

// Method 1: Using Event facade
Event::listen(DixlaseEvents::BACKUP_COMPLETED, function ($payload) {
    Log::info('Backup completed', $payload);
    
    // Send notification
    Notification::send($admins, new BackupCompletedNotification($payload));
});

// Method 2: In EventServiceProvider
protected $listen = [
    DixlaseEvents::BACKUP_COMPLETED => [
        SendBackupNotification::class,
        UploadToCloudStorage::class,
    ],
];

// Method 3: Using subscriber class
class BackupEventSubscriber
{
    public function handleStarted($payload)
    {
        // ...
    }

    public function handleCompleted($payload)
    {
        // ...
    }

    public function subscribe($events)
    {
        $events->listen(DixlaseEvents::BACKUP_STARTED, [self::class, 'handleStarted']);
        $events->listen(DixlaseEvents::BACKUP_COMPLETED, [self::class, 'handleCompleted']);
    }
}
```

### 3.2 Firing Events (For Core/Plugin Developers)

```php
use App\Events\DixlaseEvents;

// Fire backup started event
event(DixlaseEvents::BACKUP_STARTED, [
    'type' => 'full',
    'options' => ['include_uploads' => true],
]);

// After backup completes
event(DixlaseEvents::BACKUP_COMPLETED, [
    'type' => 'full',
    'path' => '/backups/backup-2025-01-01.zip',
    'size' => 1024000,
    'duration' => 45.5,
]);
```

### 3.3 Getting Event Lists

```php
use App\Events\DixlaseEvents;

// Get all events
$allEvents = DixlaseEvents::all();

// Get events by category
$backupEvents = DixlaseEvents::byCategory('backup');
$deployEvents = DixlaseEvents::byCategory('deploy');
$translationEvents = DixlaseEvents::byCategory('translation');
```

## 4. Best Practices

### 4.1 For Plugin Developers

1. **Always use constants** instead of string literals
2. **Handle exceptions** in listeners to prevent breaking the chain
3. **Keep listeners lightweight** - queue heavy operations
4. **Log important events** for debugging

### 4.2 Event Listener Example

```php
namespace MyPlugin\Listeners;

use App\Events\DixlaseEvents;
use Illuminate\Contracts\Queue\ShouldQueue;

class UploadBackupToS3 implements ShouldQueue
{
    public function handle($payload)
    {
        try {
            $path = $payload['path'];
            
            // Upload to S3
            Storage::disk('s3')->put(
                'backups/' . basename($path),
                file_get_contents($path)
            );
            
            Log::info('Backup uploaded to S3', ['path' => $path]);
        } catch (\Exception $e) {
            Log::error('Failed to upload backup to S3', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
```

### 4.3 Conditional Listening

```php
Event::listen(DixlaseEvents::BACKUP_COMPLETED, function ($payload) {
    // Only process full backups
    if ($payload['type'] !== 'full') {
        return;
    }
    
    // Process...
});
```

## 5. Webhook Integration

Events can be used to trigger webhooks:

```php
use App\Support\DixlaseSigner;

Event::listen(DixlaseEvents::BACKUP_COMPLETED, function ($payload) {
    $webhookUrl = config('backup.webhook_url');
    $secret = config('backup.webhook_secret');
    
    if (!$webhookUrl) {
        return;
    }
    
    $body = json_encode([
        'event' => 'backup.completed',
        'data' => $payload,
        'timestamp' => time(),
    ]);
    
    $headers = DixlaseSigner::signWebhook(
        parse_url($webhookUrl, PHP_URL_PATH),
        $body,
        $secret
    );
    
    Http::withHeaders($headers)->post($webhookUrl, $payload);
});
```

## 6. Testing Events

```php
use App\Events\DixlaseEvents;
use Illuminate\Support\Facades\Event;

class BackupTest extends TestCase
{
    public function test_backup_fires_events()
    {
        Event::fake([
            DixlaseEvents::BACKUP_STARTED,
            DixlaseEvents::BACKUP_COMPLETED,
        ]);
        
        // Perform backup
        $this->artisan('backup:run');
        
        // Assert events were fired
        Event::assertDispatched(DixlaseEvents::BACKUP_STARTED);
        Event::assertDispatched(DixlaseEvents::BACKUP_COMPLETED, function ($event, $payload) {
            return $payload['type'] === 'full';
        });
    }
}
```

## 7. Future Events

The following events are planned for future releases:

### 7.1 E-Commerce (When EC API is implemented)

- `dixlase.order.created`
- `dixlase.order.paid`
- `dixlase.order.shipped`
- `dixlase.order.completed`
- `dixlase.order.cancelled`
- `dixlase.cart.updated`
- `dixlase.payment.processed`
- `dixlase.payment.failed`

### 7.2 User Management (When User Plugin is implemented)

- `dixlase.user.registered`
- `dixlase.user.verified`
- `dixlase.user.login`
- `dixlase.user.logout`
- `dixlase.user.password.reset`

---

**Document Version**: 1.0.0  
**Last Updated**: 2025-01-01  
**Author**: Dixlase Development Team
