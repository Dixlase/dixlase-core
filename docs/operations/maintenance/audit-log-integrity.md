# Audit Log Integrity (Hash Chain)

## Overview

Dixlase layers two mechanisms over the audit log to **detect** tampering. They do not prevent it, and they do not guarantee anything.

- **A hash chain per row (unkeyed).** Each row carries a SHA-256 (`record_hash`) over 13 fields and the previous row's hash. No key is involved, so a row that is rewritten and re-hashed still forms a consistent chain on its own.
- **A daily seal per day (keyed).** For each completed day, an HMAC-SHA256 is recorded over the day's count, first and last row and last row's hash. The key is `AUDIT_LOG_SECRET`, or `APP_KEY` when that is unset. A recomputed chain no longer matches the seal unless the key is known as well.

Both live in the same database as the audit log. A new row is unprotected until the hourly `audit:integrity build` links it into the chain. Seals are created automatically every day and verification runs daily on the schedule; the full pass that re-hashes every row is run by hand. See "Security Considerations" for what this can and cannot prove.

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

Each completed day is pinned by a daily seal. Deleting or inserting a row, or rewriting the end of a day, is detected because the day no longer matches its seal.

### Daily Seal Table

| Field | Description |
|-------|-------------|
| `seal_date` | Target date |
| `first_log_id` | First log ID of the day |
| `last_log_id` | Last log ID of the day |
| `log_count` | Number of log entries |
| `final_hash` | Hash of the final log entry |
| `daily_signature` | The seal's HMAC-SHA256 |
| `key_version` | Seal key version |

## Commands

### Building the Hash Chain

```bash
# Set up hash chain for unprocessed logs
php artisan audit:integrity build

# Limit the number of records to process
php artisan audit:integrity build --limit=500
```

### Verifying Integrity

```bash
# Standard pass: every daily seal + the chain since the last verified record
php artisan audit:integrity verify

# Full pass: every daily seal + every chained record
php artisan audit:integrity verify --all

# Verify a specific ID range only
php artisan audit:integrity verify --from=1000 --to=2000

# Verify the daily seal for a specific date (including the chain inside that day)
php artisan audit:integrity verify --date=2025-12-20
```

What the standard pass covers:

- **Every daily seal, O(1) per day.** The seal's HMAC is recomputed and the row
  count and final hash are compared. This catches deleted or inserted rows, a
  rewritten tail and a tampered seal row, for every retained day.
- **The chain from the last verified record onward, O(rows).** Records that were
  already marked `valid` are not re-hashed; the newest of them is used as the anchor.

What it does **not** cover: an in-place edit of an already verified row that leaves
`record_hash` untouched. Only re-hashing that row detects it, so run
`verify --all` on a documented cadence (weekly is a reasonable default). The
command prints a reminder after every incremental pass.

The command exits non-zero when any seal or record fails, or when records are
still marked as tampered from an earlier run.

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
    app(SystemNotificationService::class)->sendAdminNotification(
        'Audit Log Tampering Detected',
        "Log ID {$log->id} has been tampered with.",
        ['audit_log_id' => $log->id]
    );
}
```

## Admin Panel

### Site Health

The dashboard's **Site Health** panel (advanced mode) shows an **Audit Log Integrity**
item derived from `AuditLogIntegrityService::getHealth()`:

| State | Badge | Meaning |
|-------|-------|---------|
| Tampered | Critical | A record or a daily seal failed verification |
| Chain stalled | Warning | Entries have waited more than 2 hours to be chained — the hourly `audit:integrity build` is not running |
| Seal overdue | Warning | The most recent completed day with entries has no seal after 04:30 the next day — the daily `audit:integrity seal` is not running |
| Verification stale | Recommended | An entry chained more than 30 days ago has never been verified, or the oldest verification is older than 30 days (an incremental pass does not refresh old rows — run `verify --all`). Entries chained since the last pass are expected to be unverified and do not count |
| OK | OK | Chain and seals are up to date (day one shows OK: seals only exist for completed days) |

This is the safety net for a missing cron: without it a scheduler that never
runs leaves the chain silently empty. The snapshot is cached for five minutes;
every `audit:integrity` run clears the cache.

### Audit Log Detail

The audit log detail screen shows each entry's chain sequence, record hash,
verification status (verified / tampered / not verified yet / not chained yet)
and last verification time. These fields are read-only; the command above
maintains them.

## Recommended Operations

### Scheduled Tasks (Scheduler)

Core schedules chain building, sealing and verification in `routes/console.php` —
nothing to configure beyond running the Laravel scheduler (`php artisan schedule:run`
from cron):

| Task | Schedule |
|------|----------|
| `audit:integrity build` | hourly |
| `audit:integrity seal` | daily at 03:30 (after the 03:00 file-integrity scan) |
| `audit:integrity verify` | daily at 03:45 (after the seal) |

The chain is also built once when the web installer completes, so install-time
events are protected before the first hourly run.

Generation is always on and has no switch: a record cannot be protected
retroactively, so protection must not depend on someone remembering to run it.
Verification runs daily (an incremental `audit:integrity verify`, which meets the
daily review PCI DSS 10.4.1 expects; NIST AU-9 sets no cadence). When it fails, every
administrator who can open **Security → Integrity** sees a banner across the admin
panel, and the admin notification mail is sent when notifications and a mail server are
configured. The mail goes out once, when the failure is first found; the banner stays
until a later verification passes. A manual `verify` or `verify --all` (but not a
`--date` or `--from`/`--to` run, which covers only a slice) raises or clears the banner
the same way. The scheduled run is incremental, so still run `verify --all`
periodically (see "Verifying Integrity").

### Security Considerations

1. **Protect the seal key**: Seals are keyed with `AUDIT_LOG_SECRET` (`config('app.audit_log_secret')`), falling back to `APP_KEY` when unset. With the default, the chain and the seals detect tampering **in the database**, but an attacker who can also read `.env` can recompute both. Setting a dedicated `AUDIT_LOG_SECRET` — ideally not stored on the same host — widens what the seals prove. Choose it before the first seal is created and never change it afterwards (or rotate `APP_KEY` while it is unset): seals made with a previous key fail verification and Site Health turns critical, because key rotation (`key_version`) is not implemented yet
2. **Regular verification**: Run `audit:integrity verify` on a documented cadence and `verify --all` periodically (see "Verifying Integrity")
3. **Alert configuration**: Configure the notification e-mail and the mail server so that a failed daily verification is mailed as well as shown as a banner
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
| `redaction_unrecorded` | The record is marked redacted, but no recorded erasure explains it |

### Redacted Records (Personal-Data Erasure)

Erasing a member's personal data (Privacy → erase) keeps their audit rows
but clears `ip_address` and replaces the other identifying fields. Because
`ip_address` is part of the record hash, a redacted row no longer matches
its own hash, and the hash cannot be recomputed without the value that was
erased.

So the erasure records itself. It writes one `privacy_data_erased` row
naming the operator and the subject (by id only), and stamps every row it
redacts with `redacted_at` and `redaction_id`, which equals that row's
`request_id`. The erasure is refused, and nothing is erased, if its row
cannot be written.

Verification then treats a redacted row as follows:

- The row's own hash is not checked. Its place in the chain still is: the
  next row's `previous_hash` and the daily seal pin its `record_hash`, so the
  row cannot be removed or reordered unnoticed.
- The erasure row must exist, carry the row's `redaction_id`, name the row's
  actor as its subject and come after the row. Otherwise the row fails with
  `redaction_unrecorded`.
- An accepted row is counted as **redacted** (verification status
  `redacted`), not as valid or tampered, and does not raise the tampering
  alert.

Fields outside the hash (`user_agent`, `actor_name`, `target_label`) were
never protected by it; see "Hash Calculation Target" above.

Rows redacted before this behaviour existed carry no `redacted_at` and still
fail with `hash_mismatch`. They are not backfilled: nothing records which
erasure changed them.

### Performance

- Hash calculation: ~0.1ms per record
- Chain verification: ~1,000 records/second
- Daily seal creation: ~0.5 seconds per day

## Planned for Post-Beta

- ON/OFF and frequency settings for the scheduled verification (verification only — generation stays always-on)
- "Verify now" button in the admin panel (queued; a synchronous chain pass grows with the row count)
- Chaining inline on write, removing the up-to-one-hour blind spot of the hourly batch
- Real-time tampering detection
- External timestamping service integration
- Log export/import with signatures
