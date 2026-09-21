# Quick Install Script

Install Dixlase on your server with a single command.

## Requirements

- PHP 8.3 or higher
- Composer
- MySQL / MariaDB
- SSH access to your server

## Usage

```bash
curl -sS https://install.dixlase.com | php
```

The script will:

1. Check your PHP version and required extensions
2. Download the latest Dixlase release
3. Run `composer install`
4. Generate an application key
5. Set directory permissions
6. Guide you to the web-based Installation Wizard

## Options

```bash
# Specify installation directory
curl -sS https://install.dixlase.com | php -- --dir=/var/www/dixlase

# Specify version
curl -sS https://install.dixlase.com | php -- --version=1.0.0

# Skip Composer install (if you want to run it manually)
curl -sS https://install.dixlase.com | php -- --no-composer
```

## After Installation

Open your browser and navigate to your site URL. The [Installation Wizard](../../wizard.md) will guide you through database configuration, admin account creation, and initial settings.

## Troubleshooting

### Permission denied

```bash
sudo chown -R www-data:www-data /path/to/dixlase
sudo chmod -R 755 /path/to/dixlase
sudo chmod -R 775 /path/to/dixlase/storage /path/to/dixlase/bootstrap/cache
```

### Composer not found

Install Composer first:

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```
