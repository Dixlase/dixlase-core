# Composer Create-Project

Install Dixlase from [Packagist](https://packagist.org/packages/dixlase/dixlase-core) with Composer. No GitHub token is needed.

Unlike the release ZIP, a Composer install is a source install: it carries no compiled front-end assets. `create-project` builds them with Node.js for you before it finishes.

## Requirements

- PHP 8.3 or higher, with the extensions the [Installation Wizard](../../wizard.md) checks for
- [Composer](https://getcomposer.org/) 2.x
- [Node.js](https://nodejs.org/) 20.19 or higher (or 22.12 or higher) with npm
- MySQL / MariaDB, or SQLite to start without a database server

## Steps

```bash
# 1. Create the project (installs the latest release)
composer create-project dixlase/dixlase-core dixlase
cd dixlase

# 2. Start the local server
php scripts/serve.php
```

Then open `http://127.0.0.1:8000`. The [Installation Wizard](../../wizard.md) guides you through the database, the administrator account and mail. Choose **SQLite** in the database step to start without a separate database server.

## What `create-project` Does

- Copies `.env.example` to `.env`, generates the application key and creates an empty `database/database.sqlite`.
- Builds core's front-end assets: `npm ci --ignore-scripts`, then `npm run build`. Core's own assets are not part of the Composer package.
- Exposes the bundled theme's assets. DixlaseOnePage is placed in `themes/DixlaseOnePage/` by Composer and ships its built stylesheet and scripts: a theme release builds them once and they are covered by the theme's signature, so they are **never rebuilt**. `create-project` only creates the symlink core serves them through (`public/assets/themes/DixlaseOnePage`), which the Composer package cannot carry.
- Ends by printing the remaining steps.

It does not start the server, which would keep the command from returning.

When npm is not found, or a step fails, `create-project` still succeeds and prints the step to run by hand:

```bash
npm ci && npm run build
php artisan dls:theme:symlink create DixlaseOnePage
```

To skip the build and the link, for example when the assets are built elsewhere before a deploy, set `DIXLASE_SKIP_BUILD=1`:

```bash
DIXLASE_SKIP_BUILD=1 composer create-project dixlase/dixlase-core dixlase
```

`--no-scripts` skips them as well, together with the `.env` and the application key.

## The Local Server

`php scripts/serve.php` starts PHP's built-in server for the wizard. It takes `--host` and `--port` (`php scripts/serve.php --port=8080`). It runs the same command as:

```bash
cd public
PHP_CLI_SERVER_WORKERS=4 php -d variables_order=EGPCS -d max_execution_time=300 \
  -S 127.0.0.1:8000 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
```

- It runs from `public/`: the router script, the same one `php artisan serve` uses, treats the current directory as the web root.
- `variables_order=EGPCS` keeps `PATH` for the commands the wizard runs; without it `composer dump-autoload` fails with "env: php: No such file or directory".
- `PHP_CLI_SERVER_WORKERS=4` lets one slow page load without stalling the others. It is left out on Windows, which does not support it.
- `max_execution_time=300` gives the final step, which runs the migrations, enough time.

Do not use `php artisan serve` for the wizard. It restarts whenever `.env` changes, which cuts off the step that wrote it. With `--no-reload` it keeps the `.env` it read at start-up for as long as it runs, so the wizard's database settings never take effect and the final step sends you back to the first screen.

### Stopping and Restarting the Server

The server runs only while its terminal is open.

- **Stop it** with `Ctrl+C` in the terminal running `php scripts/serve.php`. Closing the terminal stops it too.
- **Start it again** from the site directory with the same command. It serves an installed site just as it served the wizard:

  ```bash
  cd dixlase
  php scripts/serve.php
  ```

- If port 8000 is already taken, pass another one: `php scripts/serve.php --port=8080`.

Restart the server after a core update or rollback. Both recreate `public/`, and a server started there keeps the old directory.

To serve the site from a web server instead, see [production environment](../production/index.md).

## Installing a Specific Version

```bash
composer create-project dixlase/dixlase-core dixlase "0.2.*"
```

Composer picks the newest tagged release that matches the constraint.
