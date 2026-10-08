# Audit Log Usage Guide

## Overview

Dixlase's audit log system provides a unified API for recording "who / when / from where / on what / did what."

## Basic Usage

### Using the Audit Facade

```php
use App\Facades\Audit;
use App\Models\AuditLog;

// Basic logging
Audit::log([
    'category' => AuditLog::CATEGORY_AUTH,
    'action' => AuditLog::ACTION_LOGIN,
    'outcome' => AuditLog::OUTCOME_SUCCESS,
    'actor' => $user,
    'context' => [
        'message' => 'User logged in',
    ],
]);
```

### Shortcut Methods

```php
// Authentication log
Audit::logAuth(AuditLog::ACTION_LOGIN, [
    'actor' => $user,
    'outcome' => AuditLog::OUTCOME_SUCCESS,
]);

// Security log
Audit::logSecurity(AuditLog::ACTION_IP_BLOCKED, [
    'severity' => AuditLog::SEVERITY_WARNING,
    'context' => ['blocked_ip' => '192.168.1.1'],
]);

// Account log
Audit::logAccount(AuditLog::ACTION_PASSWORD_CHANGED, [
    'actor' => $user,
    'target' => $user,
]);

// Extension log
Audit::logExtension(AuditLog::ACTION_PLUGIN_INSTALLED, [
    'actor' => $admin,
    'target_label' => 'DixlaseBlog',
]);

// System log
Audit::logSystem(AuditLog::ACTION_SETTINGS_UPDATED, [
    'actor' => $admin,
    'target_label' => 'security_settings',
]);

// Content log
Audit::logContent('post_published', [
    'actor' => $author,
    'target' => $post,
]);

// Plugin log
Audit::logPlugin('custom_action', [
    'plugin_name' => 'my-plugin',
    'context' => ['custom_data' => 'value'],
]);
```

## Usage from Plugins

### Basic Usage

```php
use App\Facades\Audit;
use App\Models\AuditLog;

class MyPluginController extends Controller
{
    public function store(Request $request)
    {
        $inquiry = Inquiry::create($request->validated());

        // Log from a plugin
        Audit::log([
            'category' => AuditLog::CATEGORY_PLUGIN,
            'action' => 'inquiry_submitted',
            'plugin_name' => 'dixlase-inquiry',
            'plugin_version' => '1.0.0',
            'target' => $inquiry,
            'context' => [
                'message' => 'Inquiry was submitted',
                'form_id' => $request->form_id,
            ],
        ]);

        return redirect()->back();
    }
}
```

### Setting Plugin Context

By setting the plugin context in your ServiceProvider, plugin information is automatically attached to subsequent logs.

```php
use App\Facades\Audit;

class MyPluginServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Set plugin context
        Audit::setPluginContext('my-plugin', '1.0.0');
    }
}
```

## Model-level auditing is deprecated

`App\Traits\AuditableTrait` wrote an audit entry from a model's `created` /
`updated` / `deleted` events. **It is deprecated and will be removed in v0.2.0.**
Nothing in core, the official plugins or the themes uses it.

Audit the **operation** instead of the table write: put it in an
`App\Actions\AbstractAction` subclass and let `audit()` write the row, or call the
`Audit` facade from the service an action invokes.

```php
final class PublishPostAction extends AbstractAction
{
    protected function handle(Actor $actor, array $data): ActionResult
    {
        $post = Post::query()->findOrFail($data['post_id']);
        $post->update(['status' => 'published']);

        // AbstractAction::audit() writes the entry from this result.
        return ActionResult::success($post, 'Post published', $post->title, [
            'diff' => ['status' => ['from' => 'draft', 'to' => 'published']],
        ]);
    }
}
```

See [Action Layer](../../development/action-layer.md) for the full lifecycle. If a
model in your plugin still uses the trait and is also touched inside an action, wrap
the save with `$model->withoutAudit(...)` so only the action's entry survives; the
trait's remaining options are documented in its own source file.

## Logging Settings Changes

```php
use App\Facades\Audit;

// Log a settings change
Audit::logSettingsChange(
    'site_name',           // Setting key
    'Old Site Name',       // Previous value
    'New Site Name',       // New value
    auth()->user(),        // Actor
    'my-plugin'            // Plugin name (optional)
);
```

## Logging Model Changes

```php
use App\Facades\Audit;
use App\Models\AuditLog;

$user->email = 'new@example.com';
$user->save();

// Log model changes
Audit::logModelChange(
    $user,                              // Target model
    AuditLog::ACTION_EMAIL_CHANGED,     // Action
    auth()->user(),                     // Actor
    ['reason' => 'Requested by user']   // Additional context
);
```

## Searching and Retrieving Logs

```php
use App\Facades\Audit;
use App\Models\AuditLog;

// Get logs for an actor
$logs = Audit::getLogsForActor($user, 50);

// Get logs for a target
$logs = Audit::getLogsForTarget($post, 50);

// Get related logs by request ID
$logs = Audit::getLogsForRequest($requestId);

// Recent warnings and above
$warnings = Audit::getRecentWarnings(24, 100);

// Recent failure logs
$failures = Audit::getRecentFailures(24, 100);

// Eloquent queries
$logs = AuditLog::inCategory(AuditLog::CATEGORY_AUTH)
    ->withAction(AuditLog::ACTION_LOGIN)
    ->recent(24)
    ->orderByDesc('occurred_at')
    ->get();
```

## Constants Reference

### Severity

| Constant | Value | Description |
|----------|-------|-------------|
| `SEVERITY_DEBUG` | debug | Debug |
| `SEVERITY_INFO` | info | Informational |
| `SEVERITY_NOTICE` | notice | Notice |
| `SEVERITY_WARNING` | warning | Warning |
| `SEVERITY_ERROR` | error | Error |
| `SEVERITY_CRITICAL` | critical | Critical |
| `SEVERITY_ALERT` | alert | Alert |
| `SEVERITY_EMERGENCY` | emergency | Emergency |

### Outcome

| Constant | Value | Description |
|----------|-------|-------------|
| `OUTCOME_SUCCESS` | success | Success |
| `OUTCOME_FAILURE` | failure | Failure |
| `OUTCOME_DENIED` | denied | Denied |
| `OUTCOME_PENDING` | pending | Pending |
| `OUTCOME_UNKNOWN` | unknown | Unknown |

### Category

| Constant | Value | Description |
|----------|-------|-------------|
| `CATEGORY_AUTH` | auth | Authentication |
| `CATEGORY_ACCOUNT` | account | Account |
| `CATEGORY_DEVICE` | device | Device |
| `CATEGORY_SECURITY` | security | Security |
| `CATEGORY_SESSION` | session | Session |
| `CATEGORY_EXTENSION` | extension | Extension |
| `CATEGORY_CONTENT` | content | Content |
| `CATEGORY_SYSTEM` | system | System |
| `CATEGORY_PLUGIN` | plugin | Plugin |

## Events Automatically Recorded by Core

The following events are automatically recorded in the audit log by the core:

- **Authentication**
  - Login success/failure
  - Logout
  - Lockout
  - Password reset
  - Logout from other devices

- **Account**
  - User registration
  - Email verification completed

## Context (JSON) Structure

```json
{
  "message": "Description text",
  "before": {
    "email": "old@example.com"
  },
  "after": {
    "email": "new@example.com"
  },
  "diff": {
    "email": {
      "from": "old@example.com",
      "to": "new@example.com"
    }
  },
  "meta": {
    "http_method": "POST",
    "url": "/admin/members/123",
    "reason": "Requested by user",
    "extra": {}
  }
}
```

## Best Practices

1. **Use appropriate categories and actions**: Use the predefined constants for consistency
2. **Never log sensitive information**: Do not record passwords, tokens, etc.
3. **Leverage context**: Store before/after values, diffs, and additional information in context
4. **Specify the plugin name**: Always set plugin_name when logging from a plugin
5. **Set appropriate severity levels**: Use warning or higher for deletions and critical changes
