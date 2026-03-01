# Security Settings Unified Registry

## Overview

`SecuritySettingsRegistry` is a service that centrally manages scattered security settings. It provides a unified API to get and set settings that are distributed across multiple tables (`security_settings`, `members_settings`, `base_settings`).

## Setting Categories

| Category | Description |
|----------|-------------|
| `auth` | Authentication (2FA, password policy) |
| `login` | Login (attempt limits, lockout) |
| `session` | Session (driver, lifetime) |
| `captcha` | CAPTCHA settings |
| `ip` | IP restrictions (allow/block lists) |
| `csp` | Content Security Policy |
| `extension` | Extension security |
| `notification` | System notifications |
| `api` | API settings (rate limiting, signatures) |
| `lockdown` | Lockdown settings |

## Usage

### Retrieving Settings

```php
use App\Services\SecuritySettingsRegistry;

// Get a single setting
$captchaEnabled = SecuritySettingsRegistry::get('captcha_enabled');
$maxAttempts = SecuritySettingsRegistry::get('login_max_attempts');

// Specify a default value
$timeout = SecuritySettingsRegistry::get('api_timeout', 30);

// Get by category
$authSettings = SecuritySettingsRegistry::getByCategory('auth');
// => ['two_factor_enabled' => true, 'password_min_length' => 8, ...]

// Get all settings
$allSettings = SecuritySettingsRegistry::getAll();

// Get all settings grouped by category
$grouped = SecuritySettingsRegistry::getAllGrouped();
// => ['auth' => [...], 'login' => [...], ...]
```

### Updating Settings

```php
// Update a single setting
SecuritySettingsRegistry::set('captcha_enabled', true);
SecuritySettingsRegistry::set('login_max_attempts', 10);

// Bulk update multiple settings
SecuritySettingsRegistry::setMultiple([
    'captcha_enabled' => true,
    'captcha_driver' => 'google',
    'captcha_site_key' => 'xxx',
]);
```

### Retrieving Setting Definitions

```php
// Get the definition of a specific setting
$definition = SecuritySettingsRegistry::getDefinition('captcha_enabled');
// => [
//     'category' => 'captcha',
//     'source' => 'security_settings',
//     'type' => 'bool',
//     'default' => false,
//     'description' => 'Enable/disable CAPTCHA',
// ]

// Get all definitions
$allDefinitions = SecuritySettingsRegistry::getDefinition();

// Check if a setting exists
if (SecuritySettingsRegistry::has('captcha_enabled')) {
    // ...
}
```

### Cache Management

```php
// Clear cache for a specific setting
SecuritySettingsRegistry::clearCache('captcha_enabled');

// Clear all caches
SecuritySettingsRegistry::clearCache();
```

### Export / Import

```php
// Export settings (backup)
$backup = SecuritySettingsRegistry::export();
// => [
//     'version' => '1.0',
//     'exported_at' => '2025-12-21T21:00:00+09:00',
//     'settings' => [...],
// ]

// Export including sensitive information
$backup = SecuritySettingsRegistry::export(includeSensitive: true);

// Import settings (restore)
$count = SecuritySettingsRegistry::import($backup);
```

## Settings Reference

### Authentication (auth)

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `two_factor_enabled` | bool | false | Enable/disable 2FA |
| `two_factor_mode` | string | optional | 2FA mode |
| `password_min_length` | int | 8 | Minimum password length |
| `password_require_mixed_case` | bool | true | Require uppercase and lowercase |
| `password_require_numbers` | bool | true | Require digits |
| `password_require_symbols` | bool | false | Require symbols |
| `password_check_pwned` | bool | true | Check for compromised passwords |

### Login (login)

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `login_max_attempts` | int | 5 | Maximum login attempts |
| `login_lockout_duration` | int | 15 | Lockout duration (minutes) |
| `login_notification_enabled` | bool | true | Login notifications |
| `lockout_notification_enabled` | bool | true | Lockout notifications |

### Session (session)

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `session_driver` | string | file | Session driver |
| `session_lifetime` | int | 120 | Session lifetime (minutes) |
| `session_encrypt` | bool | false | Session encryption |

### CAPTCHA (captcha)

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `captcha_enabled` | bool | false | Enable/disable CAPTCHA |
| `captcha_driver` | string | google | CAPTCHA driver |
| `captcha_site_key` | string | | Site key |
| `captcha_secret_key` | string | | Secret key (sensitive) |

### IP Restrictions (ip)

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `enable_allowed_admin_ips` | bool | false | Enable admin IP allow list |
| `allowed_admin_ips` | string | | Admin allowed IP list |
| `enable_blocked_admin_ips` | bool | false | Enable admin IP block list |
| `blocked_admin_ips` | string | | Admin blocked IP list |

### CSP (csp)

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `csp_enabled` | bool | true | Enable/disable CSP |
| `csp_mode` | string | standard | CSP mode |
| `csp_log_violations` | bool | true | Log CSP violations |

### API (api)

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `api_rate_limit_enabled` | bool | true | Enable rate limiting |
| `api_rate_limit_per_minute` | int | 60 | Requests per minute limit |
| `api_signature_required` | bool | true | Require request signatures |
| `api_timestamp_tolerance` | int | 300 | Timestamp tolerance (seconds) |

## Setting Sources

Settings are stored in the following tables:

| Source | Table | Purpose |
|--------|-------|---------|
| `security_settings` | `security_settings` | General security |
| `members_settings` | `members_settings` | Member-related |
| `base_settings` | `base_settings` | Base settings |

The registry automatically determines which source each setting is stored in and reads from / writes to the appropriate table.

## Caching

Setting values are cached for 5 minutes. When a setting is updated, its corresponding cache is automatically cleared.

## Planned for Post-Beta

- Security settings dashboard in the admin panel
- Audit logging for setting changes
- Enhanced setting validation
- Plugin API for registering custom settings
