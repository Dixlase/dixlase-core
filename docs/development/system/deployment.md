# Dixlase Deploy System

A multi-stage deployment system for Dixlase, inspired by WordMove.

## Overview

Dixlase Deploy is a command-line tool for synchronizing files and databases between local, staging, and production environments.

### Features

- **Multi-environment support**: Configure multiple environments such as local, staging, and production
- **Connection methods**: SSH (rsync) or FTP/SFTP (lftp)
- **Sync targets**:
  - Core files
  - Plugins
  - Themes
  - Custom folders
  - Upload files
  - Database (full or specific tables)
- **Hook functionality**: Execute commands before and after deployment
- **URL/path replacement**: Automatically converts URLs/paths during database sync
- **Environment variable support**: Manage sensitive information with environment variables
- **JSON configuration**: No additional packages required, good IDE support

## System Requirements

### Local Environment

- PHP 8.2 or higher
- rsync (for SSH sync)
- lftp (for FTP/SFTP sync, optional)
- mysql/mysqldump (for database sync)
- gzip

### Remote Environment

- rsync (when using SSH)
- mysql/mysqldump
- gzip

## Installation

Dixlase Deploy is included in the core system. No additional installation is required.

## Quick Start

### 1. Generate Configuration File

```bash
php artisan deploy:init
```

This generates `dixlase-deploy.json`.

### 2. Edit Configuration File

Edit `dixlase-deploy.json` to configure your environments.

### 3. Verify Configuration

```bash
php artisan deploy:doctor
```

### 4. List Environments

```bash
php artisan deploy:list
```

### 5. Execute Deployment

```bash
# Push to staging
php artisan deploy:push staging --plugins --themes

# Pull from production
php artisan deploy:pull production --uploads
```

## Configuration File (dixlase-deploy.json)

### Basic Structure

```json
{
  "_comment": "Comments can be written using keys starting with underscore",

  "global": {
    "sql_adapter": "mysql",
    "default_connection": "ssh"
  },

  "local": {
    "vhost": "http://localhost:8080",
    "dixlase_path": "/var/www/html",
    "database": {
      "name": "dixlase",
      "user": "root",
      "password": "password",
      "host": "localhost",
      "port": 3306
    }
  },

  "staging": {
    "vhost": "https://staging.example.com",
    "dixlase_path": "/var/www/staging",
    "database": {
      "name": "dixlase_staging",
      "user": "${STAGING_DB_USER}",
      "password": "${STAGING_DB_PASSWORD}",
      "host": "localhost"
    },
    "ssh": {
      "host": "staging.example.com",
      "user": "deploy",
      "port": 22
    },
    "exclude": [
      ".git/",
      "node_modules/",
      ".env"
    ]
  },

  "production": {
    "vhost": "https://example.com",
    "dixlase_path": "/var/www/production"
  }
}
```

### Using Environment Variables

Sensitive information can be managed with environment variables (`${VAR_NAME}` format):

```json
{
  "database": {
    "password": "${DB_PASSWORD}"
  }
}
```

Set via `.env` file or system environment variables:

```bash
export STAGING_DB_USER=myuser
export STAGING_DB_PASSWORD=mypassword
```

### SSH Configuration

```json
{
  "ssh": {
    "host": "example.com",
    "user": "deploy",
    "port": 22,
    "key": "~/.ssh/id_rsa"
  }
}
```

**Recommended**: Use public key authentication instead of password authentication.

### FTP/SFTP Configuration

```json
{
  "ftp": {
    "host": "ftp.example.com",
    "user": "ftpuser",
    "password": "${FTP_PASSWORD}",
    "port": 21,
    "passive": true,
    "scheme": "sftp"
  }
}
```

### Exclude Patterns

```json
{
  "exclude": [
    ".git/",
    ".gitignore",
    "node_modules/",
    "vendor/",
    ".env",
    ".env.*",
    "dixlase-deploy.json",
    "storage/logs/*",
    "storage/framework/cache/*",
    "storage/framework/sessions/*",
    "storage/framework/views/*",
    "bootstrap/cache/*",
    "*.sql",
    "*.sql.gz"
  ]
}
```

### Hooks

Execute commands before and after deployment:

```json
{
  "hooks": {
    "push": {
      "before": [
        {"command": "php artisan down --secret=maintenance-token", "where": "remote"}
      ],
      "after": [
        {"command": "composer install --no-dev --optimize-autoloader", "where": "remote"},
        {"command": "php artisan migrate --force", "where": "remote"},
        {"command": "php artisan config:cache", "where": "remote"},
        {"command": "php artisan up", "where": "remote"}
      ]
    },
    "pull": {
      "before": [
        {"command": "php artisan down", "where": "local"}
      ],
      "after": [
        {"command": "php artisan up", "where": "local"}
      ]
    }
  }
}
```

## Command Reference

### deploy:init

Generates the configuration file.

```bash
php artisan deploy:init [--force]
```

| Option | Description |
|--------|-------------|
| `--force` | Overwrite existing configuration file |

### deploy:doctor

Checks configuration and environment.

```bash
php artisan deploy:doctor
```

Check items:
- Existence and validity of configuration file
- Presence of rsync, lftp, ssh, mysql, mysqldump, gzip
- Environment configuration verification

### deploy:list

Lists available environments.

```bash
php artisan deploy:list
```

### deploy:push

Pushes data from local to remote.

```bash
php artisan deploy:push <environment> [options]
```

| Option | Description |
|--------|-------------|
| `--core` | Sync core files |
| `--plugins` | Sync plugins |
| `--themes` | Sync themes |
| `--custom` | Sync custom folders |
| `--uploads` | Sync upload files |
| `--database` | Sync database |
| `--all` | Sync everything (including database) |
| `--tables=*` | Sync only specific tables |
| `--dry-run` | Show what would be done without actually syncing |
| `--force` | Skip confirmation prompts |

**Examples:**

```bash
# Push plugins and themes to staging
php artisan deploy:push staging --plugins --themes

# Push only specific database tables
php artisan deploy:push staging --database --tables=posts --tables=pages

# Push everything to production (with confirmation)
php artisan deploy:push production --all

# Verify with dry run
php artisan deploy:push staging --all --dry-run
```

### deploy:pull

Pulls data from remote to local.

```bash
php artisan deploy:pull <environment> [options]
```

Options are the same as `deploy:push`.

**Examples:**

```bash
# Pull upload files from production
php artisan deploy:pull production --uploads

# Pull database from staging
php artisan deploy:pull staging --database

# Pull everything from production
php artisan deploy:pull production --all --force
```

## Sync Targets

### File Sync

| Target | Default Path | Description |
|--------|-------------|-------------|
| core | / | Dixlase core files |
| plugins | plugins/ | Plugin directory |
| themes | themes/ | Theme directory |
| custom | custom/ | Custom code directory |
| uploads | storage/app/public/ | Upload files |

### Database Sync

- **Full table sync**: `--database` option
- **Specific table sync**: `--tables=table1 --tables=table2`

During database sync, the following processing is performed automatically:

1. Dump the source environment's database
2. Replace URLs/paths (vhost, dixlase_path)
3. Import into the target environment

## Security

### Protecting the Configuration File

`dixlase-deploy.json` may contain sensitive information. Be sure to add it to `.gitignore`:

```gitignore
dixlase-deploy.json
```

### Using Environment Variables

It is recommended to manage sensitive information such as passwords using environment variables:

```json
{
  "database": {
    "password": "${DB_PASSWORD}"
  }
}
```

### SSH Key Authentication

Use SSH key authentication instead of password authentication:

```bash
# Generate SSH key
ssh-keygen -t ed25519 -C "deploy@example.com"

# Add public key to remote server
ssh-copy-id -i ~/.ssh/id_ed25519.pub user@example.com
```

## Troubleshooting

### rsync Not Found

```bash
# macOS
brew install rsync

# Ubuntu/Debian
sudo apt-get install rsync
```

### lftp Not Found

```bash
# macOS
brew install lftp

# Ubuntu/Debian
sudo apt-get install lftp
```

### SSH Connection Error

1. Verify that the SSH key is correctly configured
2. Verify that the hostname and port are correct
3. Check firewall settings

```bash
# Connection test
ssh -p 22 user@example.com
```

### Database Connection Error

1. Verify database credentials
2. Verify that the mysql command is available on the remote server
3. Verify that the MySQL port is open in the firewall

### Permission Error

Verify that you have write permissions for files on the remote server.

## Best Practices

1. **Test on staging before pushing to production**
2. **Back up the database**
3. **Verify with `--dry-run` beforehand**
4. **Use hooks to enable maintenance mode**
5. **Manage sensitive information with environment variables**
6. **Exclude configuration files from version control**

## Related Commands

- `php artisan deploy:init` - Generate configuration file
- `php artisan deploy:doctor` - Environment check
- `php artisan deploy:list` - List environments
- `php artisan deploy:push` - Push
- `php artisan deploy:pull` - Pull
