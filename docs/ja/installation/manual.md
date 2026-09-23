# 手動インストール
このガイドでは、Docker を使わずに本番サーバーに Dixlase をインストールする方法を説明します。作業前に[システム要件](requirements.md)を満たしていることを確認してください。

## 1. サーバー準備

### PHP 拡張

PHP 8.3以上と必要な拡張をインストールします：

```bash
# Ubuntu / Debian
sudo apt update
sudo apt install php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring \
  php8.3-xml php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath \
  php8.3-tokenizer php8.3-fileinfo php8.3-redis

# CentOS / RHEL (Remi リポジトリ経由)
sudo dnf install php83-php-fpm php83-php-mysqlnd php83-php-mbstring \
  php83-php-xml php83-php-curl php83-php-zip php83-php-gd php83-php-bcmath \
  php83-php-tokenizer php83-php-fileinfo php83-php-redis
```

### MariaDB

MariaDB 10.6以上をインストールして設定します：

```bash
sudo apt install mariadb-server

# インストールのセキュリティ設定
sudo mysql_secure_installation

# データベースとユーザーを作成
sudo mysql -u root -p
```

```sql
CREATE DATABASE dixlase CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'dixlase'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON dixlase.* TO 'dixlase'@'localhost';
FLUSH PRIVILEGES;
```

### Composer と Node.js

```bash
# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Node.js (nvm または NodeSource 経由)
curl -fsSL https://deb.nodesource.com/setup_24.x | sudo -E bash -
sudo apt install -y nodejs
```

## 2. ダウンロードとインストール

```bash
# Web ルートに移動
cd /var/www

# Dixlase をクローンまたは展開
git clone <repository-url> dixlase
cd dixlase

# PHP 依存関係をインストール
composer install --no-dev --optimize-autoloader

# 環境ファイルをコピー
cp .env.example .env

# アプリケーションキーを生成
php artisan key:generate

# フロントエンドアセットをインストール・ビルド
npm install
npm run build
```

## 3. 環境設定

サーバーの詳細に合わせて `.env` を編集します：

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

> **注意:** `INSTALLED=true` を手動で設定しないでください。インストールウィザードがセットアップ完了後に自動的に設定します。

## 4. パーミッション設定

```bash
# 所有権を設定
sudo chown -R www-data:www-data /var/www/dixlase

# ディレクトリパーミッションを設定
sudo find /var/www/dixlase -type d -exec chmod 755 {} \;
sudo find /var/www/dixlase -type f -exec chmod 644 {} \;

# 書き込み可能なディレクトリ
sudo chmod -R 775 storage bootstrap/cache
```

## 5. Web サーバー設定

### Nginx（推奨）

`/etc/nginx/sites-available/dixlase` を作成：

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

サイトを有効化：

```bash
sudo ln -s /etc/nginx/sites-available/dixlase /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Apache

`/etc/apache2/sites-available/dixlase.conf` を作成：

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

サイトと必要なモジュールを有効化：

```bash
sudo a2enmod rewrite
sudo a2ensite dixlase
sudo systemctl reload apache2
```

## 6. SSL 設定

Let's Encrypt と Certbot を使用：

```bash
sudo apt install certbot python3-certbot-nginx   # Nginx の場合
# または
sudo apt install certbot python3-certbot-apache   # Apache の場合

sudo certbot --nginx -d your-domain.com
# または
sudo certbot --apache -d your-domain.com
```

Certbot は自動的に HTTPS を設定し、証明書の自動更新をセットアップします。

## 7. インストールウィザードを実行

ブラウザを開いて以下にアクセスします：

```
https://your-domain.com
```

`.env` で `INSTALLED=false` が設定されているため、自動的にインストールウィザードにリダイレクトされます。各ステップの詳細は[インストールウィザード](wizard.md)ガイドを参照してください。

## 8. インストール後の設定

ウィザード完了後：

- Laravel のスケジューラ用に cron ジョブを設定：

```bash
* * * * * cd /var/www/dixlase && php artisan schedule:run >> /dev/null 2>&1
```

- キューワーカーを設定（オプションだが推奨）：

```bash
# systemd を使用
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

追加の検証とセキュリティ強化手順については[インストール後チェックリスト](post-install.md)を参照してください。
