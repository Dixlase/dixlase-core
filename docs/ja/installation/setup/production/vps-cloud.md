# VPS / クラウドサーバー

VPS やクラウドサーバーに Nginx、PHP-FPM、MariaDB/MySQL を使用して Dixlase をデプロイします。サーバー環境を完全に制御でき、ほとんどの本番ワークロードに適しています。

## 前提条件

- VPS またはクラウドインスタンス（例: AWS EC2、DigitalOcean、Vultr、Linode）
- Ubuntu 22.04+ または同等の Linux ディストリビューション
- root または sudo アクセス権
- ドメイン名（推奨）

## 1. システムパッケージのインストール

```bash
sudo apt update && sudo apt upgrade -y

# PHP 8.3 と必要な拡張
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

# Node.js（アセットビルド用）
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

## 2. MariaDB の設定

```bash
sudo mysql_secure_installation
```

データベースとユーザーを作成します：

```sql
sudo mysql -u root -p

CREATE DATABASE dixlase CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'dixlase'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON dixlase.* TO 'dixlase'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## 3. Dixlase のデプロイ

```bash
# Dixlase をクローンまたはダウンロード
cd /var/www
git clone https://github.com/Dixlase/dixlase.git dixlase
cd dixlase

# 依存パッケージのインストール
composer install --no-dev --optimize-autoloader

# 環境設定
cp .env.example .env
php artisan key:generate
```

`.env` を編集します：

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_HOST=127.0.0.1
DB_DATABASE=dixlase
DB_USERNAME=dixlase
DB_PASSWORD=your_secure_password
```

フロントエンドアセットをビルドします：

```bash
npm install
npm run build
```

パーミッションを設定します：

```bash
sudo chown -R www-data:www-data /var/www/dixlase
sudo chmod -R 755 /var/www/dixlase
sudo chmod -R 775 storage bootstrap/cache
```

## 4. Nginx の設定

`/etc/nginx/sites-available/dixlase` を作成します：

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

サイトを有効化します：

```bash
sudo ln -s /etc/nginx/sites-available/dixlase /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

## 5. Let's Encrypt で SSL 設定

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com
```

Certbot が自動的に Nginx の HTTPS 設定と証明書の自動更新を構成します。

## 6. インストールウィザードの実行

ブラウザで `https://yourdomain.com` を開きます。[インストールウィザード](../../wizard.md)がデータベース設定、管理者アカウント作成、初期設定をガイドします。

## メンテナンス

### Dixlase の更新

```bash
cd /var/www/dixlase
git pull origin main
composer install --no-dev --optimize-autoloader
npm install && npm run build
php artisan migrate --force
php artisan optimize
```

### 推奨 Cron 設定

Laravel スケジューラー用の cron エントリを追加します：

```bash
* * * * * cd /var/www/dixlase && php artisan schedule:run >> /dev/null 2>&1
```
