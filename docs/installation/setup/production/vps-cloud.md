# VPS / Cloud Server

Deploy Dixlase with Nginx, PHP-FPM, and MariaDB/MySQL on a VPS or cloud server. This setup provides full control over your server environment and is suitable for most production workloads.

## Prerequisites

- A VPS or cloud instance (e.g., AWS EC2, DigitalOcean, Vultr, Linode)
- Ubuntu 22.04+ or similar Linux distribution
- Root or sudo access
- A domain name (recommended)

## 1. Install System Packages

```bash
sudo apt update && sudo apt upgrade -y

# PHP 8.3 and required extensions
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt install -y php8.3-fpm php8.3-cli php8.3-mbstring php8.3-xml \
    php8.3-curl php8.3-zip php8.3-pdo php8.3-mysql php8.3-gd \
    php8.3-bcmath php8.3-tokenizer php8.3-fileinfo php8.3-redis

# Nginx
sudo apt install -y nginx

# MariaDB
sudo apt install -y mariadb-server

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Node.js (for asset building)
curl -fsSL https://deb.nodesource.com/setup_24.x | sudo -E bash -
sudo apt install -y nodejs
```

## 2. Configure MariaDB

```bash
sudo mysql_secure_installation
```

Create a database and user:

```sql
sudo mysql -u root -p

CREATE DATABASE dixlase CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'dixlase'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON dixlase.* TO 'dixlase'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## 3. Deploy Dixlase

```bash
# Clone or download Dixlase
cd /var/www
git clone https://github.com/Dixlase/dixlase.git dixlase
cd dixlase

# Install dependencies
composer install --no-dev --optimize-autoloader

# Set up environment
cp .env.example .env
php artisan key:generate
```

Edit `.env` with your settings:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_HOST=127.0.0.1
DB_DATABASE=dixlase
DB_USERNAME=dixlase
DB_PASSWORD=your_secure_password
```

Build frontend assets:

```bash
npm install
npm run build
```

Set permissions:

```bash
sudo chown -R www-data:www-data /var/www/dixlase
sudo chmod -R 755 /var/www/dixlase
sudo chmod -R 775 storage bootstrap/cache
```

## 4. Configure Nginx

Create `/etc/nginx/sites-available/dixlase`:

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/dixlase/public;

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known) {
        deny all;
    }
}
```

Enable the site:

```bash
sudo ln -s /etc/nginx/sites-available/dixlase /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

## 5. SSL with Let's Encrypt

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com
```

Certbot will automatically configure Nginx for HTTPS and set up auto-renewal.

## 6. Run the Installation Wizard

Open `https://yourdomain.com` in your browser. The [Installation Wizard](../../wizard.md) will guide you through database configuration, admin account creation, and initial settings.

## Maintenance

### Updating Dixlase

```bash
cd /var/www/dixlase
git pull origin main
composer install --no-dev --optimize-autoloader
npm install && npm run build
php artisan migrate --force
php artisan optimize
```

### Recommended Cron Job

Add a cron entry for the Laravel scheduler:

```bash
* * * * * cd /var/www/dixlase && php artisan schedule:run >> /dev/null 2>&1
```
