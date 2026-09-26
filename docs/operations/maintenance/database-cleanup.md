# Database Cleanup

## Overview

The Dixlase database cleanup feature periodically removes unnecessary database records such as old logs and session data. It can be executed manually from the admin panel, and plugins or themes can register their own cleanup targets.

## Key Features

- **Core table cleanup**: Admin login attempt history, sessions, cache, etc.
- **Plugin/theme table cleanup**: Each extension can register its own tables as cleanup targets
- **Flexible retention period settings**: Configurable retention days per table
- **Conditional cleanup**: Supports conditions such as expired, used, or unused
- **Japanese and English**: Descriptions displayed in Japanese and English

## How to Access

Admin Panel > Settings > System Settings > Database Management

## Core Cleanup Targets

### 1. Login Attempt History (login_attempts)
- **Table**: `members_login_attempts`
- **Date column**: `created_at`
- **Default retention period**: 30 days
- **Description**: Cleans up admin login attempt records

### 2. Password Reset Tokens (password_reset_tokens)
- **Table**: `members_password_reset_tokens`
- **Date column**: `created_at`
- **Default retention period**: 30 days
- **Description**: Cleans up old password reset token records

### 3. 2FA Attempt History (two_fa_attempts)
- **Table**: `members_two_fa_attempts`
- **Date column**: `created_at`
- **Default retention period**: 30 days
- **Description**: Cleans up 2FA attempt history

### 4. 2FA Tokens (two_fa_tokens)
- **Table**: `members_two_fa_tokens`
- **Date column**: `created_at`
- **Default retention period**: 7 days
- **Additional condition**: `expired` (expired tokens only)
- **Description**: Cleans up expired 2FA tokens

### 5. 2FA Recovery Codes (recovery_codes)
- **Table**: `members_two_fa_recovery_codes`
- **Date column**: `created_at`
- **Default retention period**: 90 days
- **Additional condition**: `used` (used codes only)
- **Description**: Cleans up used and invalidated 2FA recovery codes

### 6. 2FA Passkeys (passkeys)
- **Table**: `members_passkeys`
- **Date column**: `last_used_at` (passkeys that have never been used are kept)
- **Default retention period**: 365 days
- **Description**: Cleans up old 2FA passkeys (biometric authentication)

### 7. Sessions (sessions)
- **Table**: `sessions`
- **Date column**: `last_activity`
- **Column type**: `timestamp`
- **Default retention period**: 7 days
- **Description**: Cleans up old session records

### 8. Cache Data (cache_data)
- **Table**: `cache`
- **Date column**: `expiration`
- **Column type**: `timestamp`
- **Default retention period**: None (deletes all)
- **Additional condition**: `expired_cache` (expired cache only)
- **Description**: Cleans up expired cache data

## Adding Cleanup Targets from Plugins/Themes

### 1. Creating a Configuration File

Create a `config/database-cleanup.php` file in your plugin or theme directory.

**Example file paths:**
```
plugins/YourPlugin/config/database-cleanup.php
themes/YourTheme/config/database-cleanup.php
```

**Example configuration file:**
```php
<?php

return [
    'your_table_key' => [
        'table' => 'plg_your_plugin_table_name',  // Table name
        'date_column' => 'created_at',             // Date column name
        'default_days' => 30,                      // Default retention days
        'name' => 'your-plugin::admin/settings/systems/database.your_table_key.name',
        'description' => 'your-plugin::admin/settings/systems/database.your_table_key.description',
        'enabled' => true,                         // Enabled/disabled
        'date_column_type' => 'datetime',          // Optional: 'datetime' or 'timestamp'
        'additional_conditions' => 'expired',      // Optional: additional conditions
    ],

    // Multiple tables can be defined
    'another_table' => [
        'table' => 'plg_your_plugin_another_table',
        'date_column' => 'updated_at',
        'default_days' => 60,
        'name' => 'your-plugin::admin/settings/systems/database.another_table.name',
        'description' => 'your-plugin::admin/settings/systems/database.another_table.description',
        'enabled' => true,
    ],
];
```

### 2. Creating Translation Files

Create translation files following the same directory structure as the core.

**Japanese translation file:**
```
plugins/YourPlugin/lang/ja/admin/settings/systems/database.php
```

**English translation file:**
```
plugins/YourPlugin/lang/en/admin/settings/systems/database.php
```

**Example translation file:**
```php
<?php

return [
    'your_table_key' => [
        'name' => 'Your Table Name',
        'description' => 'Description of your table',
    ],
    'another_table' => [
        'name' => 'Another Table Name',
        'description' => 'Description of another table',
    ],
];
```

### 3. Configuration Parameter Details

#### Required Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `table` | string | Name of the table to clean up |
| `date_column` | string | Column name used for date evaluation |
| `default_days` | int\|null | Default retention days (null deletes all records) |
| `name` | string | Translation key (for table name display) |
| `description` | string | Translation key (for description display) |
| `enabled` | bool | Enable/disable the cleanup feature |

#### Optional Parameters

| Parameter | Type | Description | Default |
|-----------|------|-------------|---------|
| `date_column_type` | string | Date column type ('datetime' or 'timestamp') | 'datetime' |
| `additional_conditions` | string | Additional deletion conditions | null |

#### Available Values for additional_conditions

| Value | Description |
|-------|-------------|
| `expired` | Adds the condition `expires_at < now()` |
| `used` | Adds the condition `used_at IS NOT NULL` |
| `unused` | Adds the condition `last_used_at IS NULL` |
| `expired_cache` | Adds the condition `expiration < now()` (for cache) |

### 4. Translation Key Naming Convention

Translation keys for plugins/themes follow this format:

```
{plugin-slug}::admin/settings/systems/database.{table_key}.{field}
```

**Example:**
```php
'name' => 'dixlase-users::admin/settings/systems/database.login_attempts.name',
'description' => 'dixlase-users::admin/settings/systems/database.login_attempts.description',
```

**How to determine the plugin slug:**
- Convert the plugin directory name to kebab-case (lowercase with hyphens)
- Example: `DixlaseUsers` -> `dixlase-users`

## Implementation Example: DixlaseUsers Plugin

### Configuration File
`plugins/DixlaseUsers/config/database-cleanup.php`

```php
<?php

return [
    'login_attempts' => [
        'table' => 'plg_dixlase_users_login_attempts',
        'date_column' => 'created_at',
        'default_days' => 30,
        'name' => 'dixlase-users::admin/settings/systems/database.login_attempts.name',
        'description' => 'dixlase-users::admin/settings/systems/database.login_attempts.description',
        'enabled' => true,
    ],

    'sessions' => [
        'table' => 'plg_dixlase_users_sessions',
        'date_column' => 'last_activity',
        'default_days' => 7,
        'name' => 'dixlase-users::admin/settings/systems/database.sessions.name',
        'description' => 'dixlase-users::admin/settings/systems/database.sessions.description',
        'enabled' => true,
        'date_column_type' => 'timestamp',
    ],

    'trusted_devices' => [
        'table' => 'plg_dixlase_users_trusted_devices',
        'date_column' => 'last_used_at',
        'default_days' => 90,
        'name' => 'dixlase-users::admin/settings/systems/database.trusted_devices.name',
        'description' => 'dixlase-users::admin/settings/systems/database.trusted_devices.description',
        'enabled' => true,
        'additional_conditions' => 'unused',
    ],
];
```

### Translation File (Japanese)
`plugins/DixlaseUsers/lang/ja/admin/settings/systems/database.php`

```php
<?php

return [
    'login_attempts' => [
        'name' => 'ユーザーログイン試行履歴',
        'description' => '古いユーザーログイン試行記録をクリーンアップします',
    ],
    'sessions' => [
        'name' => 'ユーザーセッション',
        'description' => '古いユーザーセッション記録をクリーンアップします',
    ],
    'trusted_devices' => [
        'name' => 'ユーザー信頼済みデバイス',
        'description' => '古いユーザー信頼済みデバイス記録をクリーンアップします',
    ],
];
```

## Usage

### Running Cleanup from the Admin Panel

1. Navigate to Admin Panel > Settings > System Settings > Database Management
2. Select the tables you want to clean up
3. Enter the retention days (auto-filled if a default value is configured)
4. Click the "Cleanup" button
5. Click "OK" in the confirmation dialog

### Retention Period Settings

- **0 days**: Deletes all records
- **1 or more days**: Deletes records older than the specified number of days
- **null (not set)**: Deletes all records (e.g., cache data)

## Important Notes

1. **Backup recommended**: It is recommended to back up the database before running cleanup
2. **Deletion is irreversible**: Deleted data cannot be recovered
3. **Verify additional conditions**: When using `additional_conditions`, ensure the target columns exist in the table
4. **Table name prefix**: It is recommended to prefix plugin tables with `plg_`
5. **Translation file placement**: Use the same directory structure as the core (`lang/{locale}/admin/settings/systems/database.php`)

## Troubleshooting

### Plugin cleanup items are not displayed

1. Verify that `config/database-cleanup.php` is placed in the correct location
2. Check the configuration file for syntax errors
3. Verify that `enabled` is set to `true`

### Translations are not displayed

1. Verify that translation files are placed in the correct directory structure
2. Check that the translation key prefix is correct (plugin slug)
3. Check the translation file for syntax errors

### Cleanup does not execute

1. Verify that the table name is correct
2. Verify that the `date_column` exists in the actual table
3. Verify that columns specified in `additional_conditions` exist
4. Check that the database connection is working properly

## Related Files

- **Core configuration**: `config/admin/database-cleanup.php`
- **Service**: `app/Services/DatabaseCleanupService.php`
- **Request**: `app/Http/Requests/Admin/Settings/Systems/AdminSystemDatabaseCleanupRequest.php`
- **View**: `resources/views/admin/settings/systems/database.blade.php`
- **Translation (Japanese)**: `lang/ja/admin/settings/systems/database.php`
- **Translation (English)**: `lang/en/admin/settings/systems/database.php`

## Developer Reference

### DatabaseCleanupService

The service class responsible for cleanup processing.

**Key methods:**
- `getPluginCleanupInfo()`: Retrieves plugin cleanup configuration
- `cleanup($type, $days)`: Executes cleanup for the specified type
- `applyAdditionalCondition($query, $condition)`: Applies additional conditions

### Validation

The `AdminSystemDatabaseCleanupRequest` class dynamically validates both core and plugin cleanup types.

```php
// Core cleanup types
$coreTypes = array_keys(config('admin.database-cleanup', []));

// Plugin cleanup types
$pluginTypes = array_keys($pluginCleanupInfo);

// Merge all types
$allTypes = array_merge($coreTypes, $pluginTypes, ['all']);
```

## Summary

The database cleanup feature is essential for maintaining Dixlase system performance. Plugin and theme developers can leverage this feature to easily implement cleanup for their own tables.
