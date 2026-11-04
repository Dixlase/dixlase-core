# Composer Create-Project

Install Dixlase from [Packagist](https://packagist.org/packages/dixlase/dixlase-core) with Composer. No GitHub token is needed.

Unlike the release ZIP, a Composer install is a source install: it carries no compiled front-end assets, so you build them once with Node.js before opening the installer.

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

# 2. Build the core's front-end assets
npm install && npm run build

# 3. Expose the bundled theme's assets
php artisan dls:theme:symlink create DixlaseOnePage
```

`create-project` also copies `.env.example` to `.env`, generates the application key and creates an empty `database/database.sqlite`. It ends by printing the remaining steps; the steps it prints are skipped once their assets exist.

The bundled theme, DixlaseOnePage, is placed in `themes/DixlaseOnePage/` by Composer, and it ships its built stylesheet and scripts: a theme release builds them once and they are covered by the theme's signature, so **do not rebuild them**. Step 3 only creates the symlink core serves them through (`public/assets/themes/DixlaseOnePage`), which the Composer package cannot carry. Without it the front page and the login screen load without the theme's scripts and styles.

Step 2 is still a build, because core's own assets are not part of the Composer package.

## Next Steps

Start PHP's built-in server from the project directory and open the Installation Wizard:

```bash
cd public
PHP_CLI_SERVER_WORKERS=4 php -d variables_order=EGPCS -d max_execution_time=300 \
  -S 127.0.0.1:8000 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
```

- Run it from `public/`: the router script, the same one `php artisan serve` uses, treats the current directory as the web root.
- `variables_order=EGPCS` keeps `PATH` for the commands the wizard runs; without it `composer dump-autoload` fails with "env: php: No such file or directory".
- `PHP_CLI_SERVER_WORKERS=4` lets one slow page load without stalling the others. Leave it out on Windows, which does not support it.
- `max_execution_time=300` gives the final step, which runs the migrations, enough time.

Do not use `php artisan serve` for the wizard. It restarts whenever `.env` changes, which cuts off the step that wrote it. With `--no-reload` it keeps the `.env` it read at start-up for as long as it runs, so the wizard's database settings never take effect and the final step sends you back to the first screen.

Restart the server after a core update or rollback. Both recreate `public/`, and a server started there keeps the old directory.

Then open `http://127.0.0.1:8000`. The [Installation Wizard](../../wizard.md) guides you through the database, the administrator account and mail. Choose **SQLite** in the database step to start without a separate database server.

To serve the site from a web server instead, see [production environment](../production/index.md).

## Installing a Specific Version

```bash
composer create-project dixlase/dixlase-core dixlase "0.2.*"
```

Composer picks the newest tagged release that matches the constraint.
