# Emergency Lockdown

## Overview

The emergency lockdown feature is designed to immediately protect the system when a security incident occurs. It enables rapid response to unauthorized access detection, brute-force attacks, and other security threats.

## Lockdown Types

| Type | Description | Scope of Impact |
|------|-------------|-----------------|
| `full` | Full lockdown | Blocks all access (except SUPER_ADMIN) |
| `admin` | Admin panel lockdown | Admin panel access only is blocked |
| `api` | API lockdown | API endpoints only are blocked |
| `login` | Login lockdown | New logins only are blocked |

## Commands

### Activate Lockdown

```bash
# Full lockdown (with confirmation)
php artisan lockdown activate

# Specify type
php artisan lockdown activate --type=admin

# Specify reason
php artisan lockdown activate --reason="Unauthorized access detected"

# Set auto-release timer (auto-release after 30 minutes)
php artisan lockdown activate --duration=30

# Allow specific IPs
php artisan lockdown activate --allow-ip=192.168.1.1 --allow-ip=10.0.0.1

# Allow specific members
php artisan lockdown activate --allow-member=1 --allow-member=2

# Execute without confirmation
php artisan lockdown activate --force
```

### Deactivate Lockdown

```bash
# Deactivate (with confirmation)
php artisan lockdown deactivate

# Deactivate without confirmation
php artisan lockdown deactivate --force

# Deactivate with reason
php artisan lockdown deactivate --reason="Threat has been resolved"
```

### Check Status

```bash
# Display current status
php artisan lockdown status

# Display history
php artisan lockdown history
```

## Programmatic Usage

### Activate Lockdown

```php
use App\Services\LockdownService;
use App\Models\LockdownStatus;

// Full lockdown
LockdownService::activate(
    type: LockdownStatus::TYPE_FULL,
    reason: 'Unauthorized access detected',
    triggeredBy: auth()->id(),
    autoReleaseMinutes: 60,
    allowedIps: ['192.168.1.1'],
    allowedMembers: [1, 2]
);

// Lock admin panel only
LockdownService::activate(
    type: LockdownStatus::TYPE_ADMIN,
    reason: 'Maintenance work'
);
```

### Deactivate Lockdown

```php
LockdownService::deactivate(
    releasedBy: auth()->id(),
    reason: 'Threat has been resolved'
);
```

### Check Status

```php
// Check if system is locked down
if (LockdownService::isLocked()) {
    // System is in lockdown
}

// Check if a specific type is locked
if (LockdownService::isLocked(LockdownStatus::TYPE_API)) {
    // API is locked
}

// Check if access is allowed
$member = auth()->user();
$ip = request()->ip();

if (LockdownService::isAccessAllowed($ip, $member)) {
    // Access allowed
}
```

### Extend Lockdown

```php
// Extend by 30 minutes
LockdownService::extend(30, auth()->id());
```

### Update Allow List

```php
LockdownService::updateAllowList(
    allowedIps: ['192.168.1.1', '10.0.0.1'],
    allowedMembers: [1, 2, 3],
    performedBy: auth()->id()
);
```

## Middleware

### Basic Usage

```php
// routes/admin.php

// Check all lockdown types
Route::middleware('lockdown')->group(function () {
    // ...
});

// Check specific type only
Route::middleware('lockdown:admin')->group(function () {
    // Admin panel routes
});

Route::middleware('lockdown:api')->group(function () {
    // API routes
});

Route::middleware('lockdown:login')->group(function () {
    // Login routes
});
```

### Middleware Registration

```php
// bootstrap/app.php (Laravel 11+)
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'lockdown' => \App\Http\Middleware\CheckLockdown::class,
    ]);
})
```

## Access Permission Priority

1. **SUPER_ADMIN**: Always has access
2. **Allowed IPs**: IPs included in `allowed_ips`
3. **Allowed Members**: Member IDs included in `allowed_members`
4. **Others**: Access denied

## Auto-Release

When `autoReleaseMinutes` is set, the lockdown is automatically released after the specified duration.

```php
// Auto-release after 60 minutes
LockdownService::activate(
    type: LockdownStatus::TYPE_LOGIN,
    reason: 'Brute-force attack detected',
    autoReleaseMinutes: 60
);
```

Auto-release is checked when the middleware processes a request.

## Audit Logs

Lockdown activation and deactivation are automatically recorded in the audit log:

- `lockdown_activated`: Lockdown activated
- `lockdown_deactivated`: Lockdown deactivated
- `lockdown_auto_released`: Auto-released

## Database Tables

### lockdown_status

Holds the current lockdown state

| Column | Description |
|--------|-------------|
| `type` | Lockdown type |
| `is_active` | Whether active |
| `reason` | Reason |
| `triggered_by` | ID of the user who activated |
| `triggered_at` | Activation timestamp |
| `released_by` | ID of the user who deactivated |
| `released_at` | Deactivation timestamp |
| `auto_release_at` | Scheduled auto-release timestamp |
| `allowed_ips` | Allowed IPs (JSON) |
| `allowed_members` | Allowed members (JSON) |

### lockdown_history

Records the history of lockdown events

| Column | Description |
|--------|-------------|
| `action` | Action (activated/deactivated/extended/modified) |
| `type` | Lockdown type |
| `reason` | Reason |
| `performed_by` | ID of the user who performed the action |
| `ip_address` | IP address |
| `details` | Detailed information (JSON) |
| `performed_at` | Action timestamp |

## Planned for Beta and Beyond

- Lockdown management UI in the admin panel
- Automatic trigger configuration (login failure count, suspicious activity, etc.)
- Slack/email notification integration
- Access attempt logging during lockdown
- Geographic lockdown (country/region-based)
