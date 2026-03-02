# Manual Installation

> **[日本語版はこちら](../ja/installation/manual.md)**

This guide covers installing Dixlase on a production server without Docker. Ensure your server meets the [System Requirements](requirements.md) before proceeding.

## 1. Server Preparation

### PHP Extensions

Install PHP 8.2+ (8.3 recommended) with the required extensions:

```bash
# Ubuntu / Debian
sudo apt update
sudo apt install php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring \
  php8.3-xml php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath \
  php8.3-tokenizer php8.3-fileinfo php8.3-redis

# CentOS / RHEL (via Remi)
sudo dnf install php83-php-fpm php83-php-mysqlnd php83-php-mbstring \
  php83-php-xml php83-php-curl php83-php-zip php83-php-gd php83-php-bcmath \
  php83-php-tokenizer php83-php-fileinfo php83-php-redis
```

### MariaDB

Install and configure MariaDB 10.6+:

```bash
sudo apt install mariadb-server

# Secure the installation
sudo mysql_secure_installation

# Create a database and user
sudo mysql -u root -p
```

```sql
CREATE DATABASE dixlase CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'dixlase'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON dixlase.* TO 'dixlase'@'localhost';
FLUSH PRIVILEGES;
```

### Composer and Node.js

```bash
# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Node.js (via nvm or NodeSource)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

## 2. Download and Install

```bash
# Navigate to your web root
cd /var/www

# Clone or extract Dixlase
git clone <repository-url> dixlase
cd dixlase

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Install and build frontend assets
npm install
npm run build
```

## 3. Environment Configuration

Edit `.env` with your server details:

```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dixlase
DB_USERNAME=dixlase
DB_PASSWORD=your_secure_password
DB_PREFIX=dls_

INSTALLED=false
```

> **Note:** Do not set `INSTALLED=true` manually. The installation wizard will set this after completing setup.

## 4. Set Permissions

```bash
# Set ownership
sudo chown -R www-data:www-data /var/www/dixlase

# Set directory permissions
sudo find /var/www/dixlase -type d -exec chmod 755 {} \;
sudo find /var/www/dixlase -type f -exec chmod 644 {} \;

# Writable directories
sudo chmod -R 775 storage bootstrap/cache
```

## 5. Web Server Configuration

### Nginx (Recommended)

Create `/etc/nginx/sites-available/dixlase`:

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/dixlase/public;

    index index.php;

    charset utf-8;
    client_max_body_size 64M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable the site:

```bash
sudo ln -s /etc/nginx/sites-available/dixlase /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Apache

Create `/etc/apache2/sites-available/dixlase.conf`:

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /var/www/dixlase/public

    <Directory /var/www/dixlase/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/dixlase-error.log
    CustomLog ${APACHE_LOG_DIR}/dixlase-access.log combined
</VirtualHost>
```

Enable the site and required modules:

```bash
sudo a2enmod rewrite
sudo a2ensite dixlase
sudo systemctl reload apache2
```

## 6. SSL Configuration

Using Let's Encrypt with Certbot:

```bash
sudo apt install certbot python3-certbot-nginx   # for Nginx
# or
sudo apt install certbot python3-certbot-apache   # for Apache

sudo certbot --nginx -d your-domain.com
# or
sudo certbot --apache -d your-domain.com
```

Certbot will automatically configure HTTPS and set up certificate renewal.

## 7. Run the Installation Wizard

Open your browser and navigate to:

```
https://your-domain.com
```

Since `INSTALLED=false` in your `.env`, you will be redirected to the installation wizard automatically. See the [Installation Wizard](wizard.md) guide for details on each step.

## 8. Post-Installation

After the wizard completes:

- Set up a cron job for Laravel's scheduler:

```bash
* * * * * cd /var/www/dixlase && php artisan schedule:run >> /dev/null 2>&1
```

- Configure a queue worker (optional but recommended):

```bash
# Using systemd
sudo nano /etc/systemd/system/dixlase-worker.service
```

```ini
[Unit]
Description=Dixlase Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
WorkingDirectory=/var/www/dixlase
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --max-time=3600
Restart=always

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable dixlase-worker
sudo systemctl start dixlase-worker
```

See the [Post-Install Checklist](post-install.md) for additional verification and security hardening steps.
