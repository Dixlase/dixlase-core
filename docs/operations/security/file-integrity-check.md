# File Integrity Check

## Overview

The file integrity check is a security feature that detects tampering with Dixlase core files. It compares current files against a baseline (a reference set of file hashes) to detect unauthorized modifications, additions, and deletions.

### Key Features

- **Baseline generation**: Records hash values of core files
- **Integrity scanning**: Compares current files against the baseline
- **Change detection**: Detects modified, added, and deleted files
- **Suspicious file detection**: Detects PHP files in locations where they should not exist
- **Scan history management**: Stores and displays past scan results
- **Automatic scheduling**: Runs an automatic scan daily at 3:00 AM

## Baseline Information

### What Is a Baseline?

A baseline is a record of SHA-256 hash values for core files in their normal state. It is created during the initial scan or when the baseline is regenerated.

### Storage Location

```
storage/app/dixlase/security/core_hashes.json
```

### Baseline Structure

```json
{
    "meta": {
        "generated_at": "2026-02-10T02:00:00+09:00",
        "app_version": "1.0.0",
        "hash_algo": "sha256",
        "paths": [...],
        "ignore_patterns": [...]
    },
    "files": {
        "app/Http/Controllers/Controller.php": "abc123...",
        "config/app.php": "def456...",
        ...
    }
}
```

### When to Regenerate the Baseline

Regenerate the baseline in the following cases:

1. **After a Dixlase update**
   - Core files will have been updated

2. **After intentionally modifying core files**
   - When applying bug fixes or customizations

3. **When false positives occur frequently**
   - The baseline may be outdated

## Scanned Directories

The following directories and files are subject to integrity checks:

### Core Directories

| Path | Description |
|------|-------------|
| `app/` | Application logic (controllers, models, services, etc.) |
| `bootstrap/` | Application bootstrapping |
| `config/` | Configuration files |
| `routes/` | Route definitions |
| `resources/` | Views, language files, assets |
| `database/migrations/` | Database migrations |
| `public/index.php` | Entry point |
| `public/build/` | Built assets (JS/CSS) |
| `artisan` | Artisan command-line tool |
| `composer.json` | Dependency definitions |
| `composer.lock` | Dependency lock file |

### Reasons for Inclusion

- **app/**: Detect tampering with core logic
- **bootstrap/**: Protect the bootstrap process
- **config/**: Detect unauthorized configuration changes
- **routes/**: Prevent route tampering
- **resources/**: Protect UI templates
- **database/migrations/**: Protect schema definitions
- **public/build/**: Detect tampering with production assets (important)
- **composer.json/lock**: Detect unauthorized dependency changes

## Excluded Directories and Patterns

The following directories and files are excluded from integrity checks:

### Exclusion List

| Path/Pattern | Description | Reason for Exclusion |
|-------------|-------------|---------------------|
| `custom/` | Customization files | Intentionally added/modified by users |
| `storage/` | Storage directory | Logs, cache, uploaded files, etc. |
| `vendor/` | Composer dependency packages | Managed by Composer (separate handling planned for the future) |
| `node_modules/` | npm dependency packages | Managed by npm |
| `bootstrap/cache/` | Bootstrap cache | Auto-generated |
| `.git/` | Git repository | Version control system |
| `.env` | Environment configuration file | Varies by environment |
| `.env.*` | Environment configuration files (per environment) | Varies by environment |
| `public/uploads/` | User uploads | Dynamically changed |
| `public/storage/` | Storage symlink | Dynamic content |
| `public/hot` | Vite dev server | Development environment only |
| `*.log` | Log files | Constantly changing |

### Reasons for Exclusion

1. **Dynamically changing files**
   - Logs, cache, sessions, etc.
   - Frequently changed during normal operation

2. **User customizations**
   - `custom/` directory
   - Intentional changes, not tampering

3. **Environment-dependent files**
   - `.env` files
   - Content differs by environment

4. **External packages**
   - `vendor/`, `node_modules/`
   - Managed by package managers

## Custom Directory Structure

When customizing core, plugins, or themes, place files under the `custom/` directory.

### Recommended Structure

```
custom/
├── app/              # Custom application logic
├── config/           # Custom configuration files
├── database/         # Custom migrations and seeders
├── lang/             # Custom language files
├── plugins/          # Custom plugins
├── resources/        # Custom views and assets
├── routes/           # Custom routes
├── tests/            # Custom tests
└── themes/           # Custom themes
```

### Benefits

- **Separation from core**: Minimizes conflicts during updates
- **Clear management**: Customizations are immediately visible
- **Easy backups**: Only the `custom/` directory needs to be backed up
- **Excluded from integrity checks**: Prevents false positives

## Running Scans

### Manual Scan

Run from the admin panel:

1. **Admin Panel > Security Settings > File Integrity**
2. Click the "Run Scan" button
3. Click "Confirm" in the confirmation modal

### Automatic Scan

Runs automatically daily at 3:00 AM (scheduled).

```php
// routes/console.php
Schedule::command('dls:integrity:scan --scheduled')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->runInBackground();
```

### Command Line

```bash
# Manual scan
php artisan dls:integrity:scan

# Scheduled execution
php artisan dls:integrity:scan --scheduled

# Regenerate baseline
php artisan dls:integrity:regenerate-baseline
```

## Understanding Scan Results

### Status

- **OK**: All files are normal
- **WARNING**: Minor issues detected
- **CRITICAL**: Serious tampering detected

### Detected Issues

1. **Changed Files**
   - Files with mismatched hash values
   - Possible core file tampering

2. **Added Files**
   - Files not present in the baseline
   - Possible unauthorized file additions

3. **Removed Files**
   - Files present in the baseline but no longer exist
   - Possible deletion of important files

4. **Suspicious Files**
   - PHP files found in locations such as `public/uploads/`
   - Possible web shells or backdoors

## Troubleshooting

### Too Many False Positives

**Cause**: Baseline is outdated or intentional changes were made

**Solution**:
1. Verify whether the changes are legitimate
2. If the changes are legitimate, regenerate the baseline

### Scan Is Slow

**Cause**: Large number of target files

**Solution**:
1. Review the exclusion patterns
2. Remove unnecessary files
3. Consider upgrading server specifications

### Baseline Cannot Be Generated

**Cause**: No write permissions on the storage directory

**Solution**:
```bash
chmod -R 775 storage/app/dixlase/security
chown -R www-data:www-data storage/app/dixlase/security
```

## Security Notes

1. **Protect the baseline file**
   - Set appropriate permissions on `storage/app/dixlase/security/`
   - Prevent unauthorized access

2. **Regular scanning**
   - Verify that the automatic schedule is running correctly
   - Periodic manual execution is also recommended

3. **Respond to alerts**
   - Investigate critical alerts immediately
   - Review the details of any changes

4. **Log retention**
   - Back up scan history regularly
   - Exercise caution when using the delete function

## Related Files

- **Service**: `app/Services/FileIntegrityService.php`
- **Controller**: `app/Http/Controllers/Admin/Settings/Security/AdminSecurityIntegrityController.php`
- **Model**: `app/Models/FileIntegrityAudit.php`
- **View**: `resources/views/admin/settings/security/integrity.blade.php`
- **Commands**: `app/Console/Commands/FileIntegrity/`
- **Baseline**: `storage/app/dixlase/security/core_hashes.json`

## Changelog

- **2026-02-10**: Initial version
  - Added `resources`, `database/migrations`, and `public/build` to scanned directories
  - Added `custom`, `public/uploads`, `public/storage`, `public/hot`, and `*.log` to exclusion patterns
  - Clarified the custom directory structure
