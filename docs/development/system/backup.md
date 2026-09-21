# Dixlase Backup System

A command-line tool for backing up Dixlase files and databases.

## Overview

The backup system provides the following features:

- **File backup**: Compress specified directories into ZIP files
- **Database backup**: Dump MySQL databases to SQL files
- **Backup listing**: View existing backups
- **Cleanup**: Automatically delete old backups

## Requirements

- PHP 8.3 or higher
- ZipArchive PHP extension
- mysqldump (for database backups)

## Command Reference

### backup:create - Create a Backup

```bash
php artisan backup:create [options]
```

#### File Backup Options

| Option | Description |
|--------|-------------|
| `--all` | Back up all files and the database |
| `--core` | Core files (app, bootstrap, config, database/migrations, database/seeders, lang, public, resources, routes, stubs) |
| `--plugins` | plugins directory |
| `--themes` | themes directory |
| `--custom` | custom directory |
| `--storage-public` | storage/app/public directory |
| `--storage-private` | storage/app/private directory |
| `--logs` | storage/logs directory |

#### Database Backup Options

| Option | Description |
|--------|-------------|
| `--database` | Back up the entire database |
| `--tables=*` | Back up specific tables only (multiple tables can be specified) |

#### Additional Options

| Option | Description |
|--------|-------------|
| `--files-only` | Back up files only (skip DB when used with --all) |
| `--db-only` | Back up database only (skip files when used with --all) |

#### Examples

```bash
# Back up everything
php artisan backup:create --all

# Back up plugins and themes only
php artisan backup:create --plugins --themes

# Back up database only
php artisan backup:create --database

# Back up specific tables only
php artisan backup:create --tables=members --tables=base_settings

# Back up core files and database
php artisan backup:create --core --database

# Back up all files (excluding database)
php artisan backup:create --all --files-only

# Back up uploaded files and logs
php artisan backup:create --storage-public --storage-private --logs
```

### backup:list - List Backups

```bash
php artisan backup:list [options]
```

#### Options

| Option | Description |
|--------|-------------|
| `--files` | Show file backups only |
| `--database` | Show database backups only |

#### Examples

```bash
# Show all backups
php artisan backup:list

# Show file backups only
php artisan backup:list --files

# Show database backups only
php artisan backup:list --database
```

### backup:cleanup - Delete Old Backups

```bash
php artisan backup:cleanup [options]
```

#### Options

| Option | Description |
|--------|-------------|
| `--days=30` | Delete backups older than the specified number of days (default: 30 days) |
| `--all` | Delete all backups |
| `--force` | Delete without confirmation |

#### Examples

```bash
# Delete backups older than 30 days
php artisan backup:cleanup

# Delete backups older than 7 days
php artisan backup:cleanup --days=7

# Delete all backups
php artisan backup:cleanup --all

# Delete without confirmation
php artisan backup:cleanup --days=14 --force
```

## Backup File Storage Location

Backup files are stored in the `database/backups/` directory.

### File Naming Convention

- File backups: `dixlase_files_YYYYMMDD_HHMMSS.zip`
- Database backups: `dixlase_db_YYYYMMDD_HHMMSS.sql`

## Excluded Files and Directories

The following are automatically excluded from file backups:

- `.git` directory
- `node_modules` directory
- `vendor` directory
- `.env` files
- `*.log` files
- `.DS_Store` files
- `Thumbs.db` files
- `*.cache` files

## Setting Up Automated Backups

You can set up regular backups using cron jobs:

```bash
# Back up everything daily at 3:00 AM
0 3 * * * cd /path/to/dixlase && php artisan backup:create --all

# Clean up backups every Sunday
0 4 * * 0 cd /path/to/dixlase && php artisan backup:cleanup --days=30 --force
```

## Troubleshooting

### ZIP File Creation Fails

- Verify that the PHP ZipArchive extension is installed
- Verify that the `database/backups/` directory has write permissions

### mysqldump Fails

- Verify that the mysqldump command is installed
- Verify that the database connection settings are correct
- Verify that the database user has the appropriate permissions

### Backup Files Are Too Large

- Back up only specific directories
- Clean up old backups regularly
- Consider managing large files (such as media files) separately

## Security Notes

- Backup files may contain sensitive information
- Restrict access to the `database/backups/` directory
- Move and store backup files in a secure location
- Encrypting production backups is recommended
