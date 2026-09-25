# Installation Wizard
The installation wizard guides you through the initial configuration of Dixlase. It runs automatically when `INSTALLED=false` in your `.env` file.

## Overview

The wizard consists of the following steps:

1. **Server Check & Language** - Verify requirements and choose language
2. **Mode Selection** - Simple or Advanced installation
3. **Basic Settings** - Site name and admin account
4. **Environment** - URL, timezone, SSL, and debug settings
5. **Database** - Connection configuration with test
6. **Mail** - SMTP settings with connection, send, and receive tests
7. **Confirmation** - Review all settings
8. **Complete** - Finalize installation

## Step 0: Server Check & Language

On first access, the wizard checks your server environment:

- **PHP version** (8.3+ required)
- **Required extensions** (Ctype, cURL, DOM, Fileinfo, JSON, Mbstring, OpenSSL, PCRE, PDO, Tokenizer, XML)
- **Optional extensions** (BCMath)
- **Directory permissions** (`storage/`, `bootstrap/cache/`)

Each check shows a pass/fail status. All required checks must pass before proceeding.

You can also switch the interface language (English / Japanese) at this step.

## Mode Selection

Choose your installation mode:

| Mode | Description |
|------|-------------|
| **Simple** | Pre-configures sensible defaults. Best for most users. Skips advanced options like debug mode and custom admin URL. |
| **Advanced** | Full control over every setting. For experienced administrators. |

### Simple Mode Defaults

When Simple mode is selected, these values are set automatically:

- `APP_ENV` = `production`
- `APP_DEBUG` = `false`
- Admin URL path = `admin`
- Force SSL = `true`

## Step 1: Basic Settings

| Field | Required | Description |
|-------|----------|-------------|
| Site Name | Yes | Your website's display name |
| Admin Account Name | Yes | Login username for the super administrator |
| Admin Display Name | No | Display name (defaults to account name) |
| Admin Email | Yes | Email address for the admin account |
| Admin Password | Yes | Password for the admin account |

The admin account created here has the **Super Admin** role (highest privilege level).

## Step 2: Environment Settings

| Field | Required | Simple Mode | Description |
|-------|----------|-------------|-------------|
| App URL | Yes | User input | Your site's domain (without protocol) |
| Environment | Yes | `production` | `production` or `local` |
| Debug Mode | Yes | `false` | Enable/disable debug output |
| Admin URL | Yes | `admin` | Path segment for admin panel (e.g., `admin` makes it `https://example.com/admin`) |
| Timezone | Yes | User input | Server timezone (default: `Asia/Tokyo`) |
| Force SSL | Yes | `true` | Redirect all traffic to HTTPS |

> **Tip:** In production, always keep Debug Mode off and Force SSL on.

## Step 3: Database Settings

| Field | Required | Default | Description |
|-------|----------|---------|-------------|
| Connection | Yes | `mysql` | Database driver |
| Host | Yes | `mysql` (Docker) / `127.0.0.1` | Database server hostname |
| Port | Yes | `3306` | Database port |
| Database | Yes | `dixlase` | Database name |
| Username | Yes | `dixlase` | Database username |
| Password | No | - | Database password |
| Preserve Data | No | Off | Keep existing data during migration |

### Connection Test

Click the **Test Connection** button to verify database connectivity before proceeding. The wizard creates a temporary connection to the specified database and reports success or failure.

### Preserve Data

- **Off** (default): Runs `migrate:fresh` — drops all tables and recreates them. Use this for new installations.
- **On**: Runs `migrate` — only applies new migrations. Use this when reinstalling without losing existing data.

## Step 4: Mail Settings

| Field | Required | Default | Description |
|-------|----------|---------|-------------|
| Mailer | Yes | `smtp` | Mail transport (smtp, sendmail, etc.) |
| Host | Yes | - | SMTP server hostname |
| Port | Yes | - | SMTP port (25, 465, 587, 1025, etc.) |
| Username | No | - | SMTP authentication username |
| Password | No | - | SMTP authentication password |
| Encryption | No | `tls` | TLS, SSL, or none |
| From Address | No | (admin email) | Default sender address |

### Mail Tests

The wizard provides three test stages:

1. **Connection Test** - Verifies the SMTP server is reachable and accepts connections
2. **Send Test** - Sends a test email with a verification link
3. **Receive Test** - Confirms the test email was received by clicking the verification link

All three tests are optional but recommended. You can proceed without completing them.

> **Docker users:** Use `mailpit` as the host and `1025` as the port. View test emails at `http://localhost:8025`.

## Step 5: Confirmation

The confirmation page displays a summary of all settings entered:

- Basic settings (site name, admin account)
- Environment settings (URL, timezone, SSL)
- Database settings (host, database, username)
- Mail settings (host, port, encryption)
- Mail test results (connection, send, receive status)

Review all values carefully. Use the back buttons to correct any settings before proceeding.

## Step 6: Installation Execution

When you click **Install**, the wizard performs these operations in order:

1. Writes all settings to the `.env` file
2. Clears the configuration cache
3. Runs database migrations (fresh or incremental based on Preserve Data setting)
4. Seeds the database with default data
5. Runs theme migrations and seeders
6. Creates the admin user account
7. Initializes base and security settings
8. Creates storage symlinks
9. Generates file integrity baseline

This process may take a minute. Do not close the browser or navigate away.

## Step 7: Complete

After successful installation, you will see a completion page with:

- A link to the admin login page
- Your configured admin URL

Click **Finalize** to set `INSTALLED=true` in your `.env` file. After finalization, the installation wizard will no longer be accessible.

## Troubleshooting

### Wizard Does Not Appear

- Verify `INSTALLED=false` in your `.env` file
- Clear the config cache: `php artisan config:clear`

### Database Migration Fails

- Check that the database user has CREATE, DROP, ALTER, and INDEX privileges
- Ensure the database exists and is empty (for fresh install)
- Check the `storage/logs/install.log` file for detailed error messages

### Mail Test Fails

- Verify the SMTP host is reachable from the server
- Check firewall rules for the SMTP port
- For Gmail/Office 365, use app-specific passwords rather than account passwords
- Confirm the encryption type matches the port (587 = TLS, 465 = SSL)

### Installation Hangs

- Check PHP's `max_execution_time` (recommended: 300 seconds)
- Review `storage/logs/install.log` for errors
- Ensure sufficient disk space for migrations and seeding

---

## Installing from the command line

`dls:install` runs the same pipeline as the wizard without a browser. Use it when
there is no web server to point at the wizard, or when the installation should be
scripted — the one-liner installer does exactly this.

```bash
php artisan dls:install \
    --site-name="My Site" \
    --admin-name=admin \
    --admin-email=admin@example.com \
    --url=example.com \
    --db=sqlite \
    --force
```

Anything you leave out is asked for, so the command is also usable by hand. With
`--no-interaction` a missing setting is an error instead of a prompt.

### Passwords

`--admin-password` works, but the value ends up in the shell history and in the
process list. Prefer the environment:

```bash
DIXLASE_ADMIN_PASSWORD='…' php artisan dls:install …
```

`DIXLASE_DB_PASSWORD` and `DIXLASE_MAIL_PASSWORD` do the same for the other two.
Run the command interactively and the administrator password is asked for without
echoing.

### What it refuses to do

The command stops before writing when:

- the installation is already complete (`INSTALLED=true`)
- another run holds the lock (`storage/framework/install-running`)
- a required setting is missing and prompting is not allowed
- a setting fails the same validation the wizard applies
- **it has not been confirmed.** By default every table in the target database is
  dropped and recreated, so a non-interactive run needs `--force`. Pass
  `--preserve-data` to migrate without dropping anything.

### Database

`--db=sqlite` (the default) creates the file if it does not exist, including its
directory; `--db-database` sets the path. For MySQL, pass `--db-host`,
`--db-port`, `--db-database` and `--db-username`; the database itself must already
exist and the user must be able to create tables in it.

### After it finishes

The command prints the site and admin URLs, and `.env` is left with
`INSTALLED=true`. Nothing else is needed — the completion screen the wizard shows
only performs the same finalisation the command has already done.
