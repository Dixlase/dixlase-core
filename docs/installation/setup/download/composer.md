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

# 3. Build the bundled theme
php artisan dls:theme:build DixlaseOnePage
```

`create-project` also copies `.env.example` to `.env`, generates the application key and creates an empty `database/database.sqlite`. It ends by printing the remaining steps; the steps it prints are skipped once their assets exist.

The bundled theme, DixlaseOnePage, is placed in `themes/DixlaseOnePage/` by Composer. The Installation Wizard builds a theme only when it downloads one, so this theme has to be built by step 3. Without it the front page and the login screen load without the theme's scripts and styles.

## Next Steps

Start a local server and open the Installation Wizard:

```bash
php artisan serve --no-reload
```

Keep `--no-reload`. Without it the development server restarts whenever `.env` changes, and the wizard writes `.env` during its steps: the restart cuts off that request, and the browser reports an empty response.

Then open `http://127.0.0.1:8000`. The [Installation Wizard](../../wizard.md) guides you through the database, the administrator account and mail. Choose **SQLite** in the database step to start without a separate database server.

To serve the site from a web server instead, see [production environment](../production/index.md).

## Installing a Specific Version

```bash
composer create-project dixlase/dixlase-core dixlase "0.2.*"
```

Composer picks the newest tagged release that matches the constraint.
