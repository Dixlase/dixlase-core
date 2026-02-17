# Audit Log Integrity (Hash Chain)

## Overview

Dixlase's audit logs achieve tamper resistance using hash chain technology similar to blockchain. Each log record contains the hash of the previous record, guaranteeing integrity through chaining.

## How It Works

### Hash Chain

```
[Log 1] ──hash──> [Log 2] ──hash──> [Log 3] ──hash──> ...
   │                 │                 │
   └─ genesis        └─ hash(Log 1)    └─ hash(Log 2)
```

The following fields are added to each log record:

| Field | Description |
|-------|-------------|
| `record_hash` | SHA-256 hash of this record (64 characters) |
| `previous_hash` | Hash of the previous record ('genesis' for the first) |
| `chain_sequence` | Sequential number within the chain |
| `hash_algorithm` | Algorithm used (sha256) |
| `verification_status` | Verification result (valid/invalid/null) |
| `last_verified_at` | Last verification timestamp |

### Hash Calculation Target

```php
$data = implode('|', [
    $this->id,
    $this->occurred_at->toIso8601String(),
    $this->severity,
    $this->outcome,
    $this->category,
    $this->action,
    $this->actor_type,
    $this->actor_id,
    $this->target_type,
    $this->target_id,
    $this->ip_address,
    json_encode($this->context),
    $previousHash,  // Hash of the previous record
]);

$hash = hash('sha256', $data);
```

## Daily Seal

Logs are "sealed" each day to guarantee the integrity of the entire day's logs.

### Daily Seal Table

| Field | Description |
|-------|-------------|
| `seal_date` | Target date |
| `first_log_id` | First log ID of the day |
| `last_log_id` | Last log ID of the day |
| `log_count` | Number of log entries |
| `final_hash` | Hash of the final log entry |
| `daily_signature` | HMAC-SHA256 signature |
| `key_version` | Signing key version |

## Commands

### Building the Hash Chain

```bash
# Set up hash chain for unprocessed logs
php artisan audit:integrity build

# Limit the number of records to process
php artisan audit:integrity build --limit=500
```

### Verifying the Hash Chain

```bash
# Verify the entire chain
php artisan audit:integrity verify

# Verify a specific ID range
php artisan audit:integrity verify --from=1000 --to=2000

# Verify the daily seal for a specific date
php artisan audit:integrity verify --date=2025-12-20
```

### Creating Daily Seals

```bash
# Create daily seals for the past 7 days
php artisan audit:integrity seal

# Specify the number of days
php artisan audit:integrity seal --days=30

# Create a seal for a specific date
php artisan audit:integrity seal --date=2025-12-20
```

### Displaying Statistics

```bash
php artisan audit:integrity stats
```

Example output:
```
Audit Log Integrity Statistics

+---------------------------+--------+
| Item                      | Value  |
+---------------------------+--------+
| Total logs                | 15420  |
| Hash chain set            | 15420  |
| Hash chain not set        | 0      |
| Verified                  | 15420  |
| Tampering detected        | 0      |
| Not verified              | 0      |
+---------------------------+--------+

Daily Seal Statistics (past 30 days)

+------------------+-------+
| Item             | Value |
+------------------+-------+
| Total seals      | 30    |
| Valid seals      | 30    |
| Invalid seals    | 0     |
| Sealed log count | 15420 |
+------------------+-------+
```

## Programmatic Usage

### Logging with Hash Chain

```php
use App\Models\AuditLog;

// Standard logging (without hash chain)
AuditLog::log([
    'category' => AuditLog::CATEGORY_AUTH,
    'action' => AuditLog::ACTION_LOGIN,
    'actor' => $member,
]);

// Log with hash chain
AuditLog::logWithHashChain([
    'category' => AuditLog::CATEGORY_SECURITY,
    'action' => AuditLog::ACTION_SETTINGS_UPDATED,
    'actor' => $member,
    'context' => ['changes' => $changes],
]);
```

### Integrity Verification Service

```php
use App\Services\AuditLogIntegrityService;

$service = app(AuditLogIntegrityService::class);

// Verify the chain
$result = $service->verifyChain();
if (!$result['is_valid']) {
    // Tampering detected
    foreach ($result['errors'] as $error) {
        Log::alert('Audit log tampered', $error);
    }
}

// Create a daily seal
$seal = $service->createDailySeal(now()->subDay());

// Verify a daily seal
$result = $service->verifyDailySeal(now()->subDay());

// Get statistics
$stats = $service->getStats();
```

### Handling Tampering Detection

```php
// Retrieve tampered logs
$tamperedLogs = AuditLog::tampered()->get();

foreach ($tamperedLogs as $log) {
    // Send alert
    SystemNotificationService::send(
        'Audit Log Tampering Detected',
        "Log ID {$log->id} has been tampered with.",
        'critical'
    );
}
```

## Recommended Operations

### Scheduled Tasks (Scheduler)

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    // Hourly: Set up hash chain for unprocessed logs
    $schedule->command('audit:integrity build --limit=1000')
        ->hourly();

    // Daily at midnight: Create the previous day's daily seal
    $schedule->command('audit:integrity seal --days=1')
        ->dailyAt('00:30');

    // Weekly: Verify the chain for the past 7 days
    $schedule->command('audit:integrity verify')
        ->weekly();
}
```

### Security Considerations

1. **Protect the signing key**: Securely manage `APP_KEY` or a dedicated `AUDIT_LOG_SECRET`
2. **Regular verification**: Verify the entire chain weekly or daily
3. **Alert configuration**: Set up immediate notifications when tampering is detected
4. **Backups**: Include the daily seal table in your backups
5. **Access control**: Restrict direct access to the audit log table

## Technical Specifications

### Hash Algorithms

- **Record hash**: SHA-256
- **Daily seal**: HMAC-SHA256

### Verification Error Codes

| Code | Description |
|------|-------------|
| `hash_mismatch` | Record hash does not match |
| `chain_broken` | Link to the previous record is broken |
| `sequence_gap` | Gap in the sequence numbers |
| `invalid_genesis` | Invalid first record |

### Performance

- Hash calculation: ~0.1ms per record
- Chain verification: ~1,000 records/second
- Daily seal creation: ~0.5 seconds per day

## Planned for Post-Beta

- Integrity dashboard in the admin panel
- Real-time tampering detection
- External timestamping service integration
- Log export/import with signatures
