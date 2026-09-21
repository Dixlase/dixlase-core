# Dixlase デプロイシステム

WordMoveにインスパイアされた、Dixlase用のマルチステージデプロイメントシステムです。

## 概要

Dixlase Deployは、ローカル、ステージング、本番環境間でファイルとデータベースを同期するためのコマンドラインツールです。

### 機能

- **マルチ環境対応**: ローカル、ステージング、本番など複数の環境を設定可能
- **接続方式**: SSH（rsync）またはFTP/SFTP（lftp）
- **同期対象**:
  - コアファイル
  - プラグイン
  - テーマ
  - カスタムフォルダ
  - アップロードファイル
  - データベース（全体または特定テーブル）
- **フック機能**: デプロイ前後にコマンドを実行
- **URL/パス置換**: データベース同期時に自動的にURL/パスを変換
- **環境変数対応**: 機密情報を環境変数で管理
- **JSON形式設定**: 追加パッケージ不要、IDEサポート良好

## システム要件

### ローカル環境

- PHP 8.3以上
- rsync（SSH同期用）
- lftp（FTP/SFTP同期用、オプション）
- mysql/mysqldump（データベース同期用）
- gzip

### リモート環境

- rsync（SSH使用時）
- mysql/mysqldump
- gzip

## インストール

Dixlase Deployはコアシステムに含まれています。追加のインストールは不要です。

## クイックスタート

### 1. 設定ファイルの生成

```bash
php artisan deploy:init
```

これにより `dixlase-deploy.json` が生成されます。

### 2. 設定ファイルの編集

`dixlase-deploy.json` を編集して、環境設定を行います。

### 3. 設定の確認

```bash
php artisan deploy:doctor
```

### 4. 環境一覧の確認

```bash
php artisan deploy:list
```

### 5. デプロイの実行

```bash
# ステージングにプッシュ
php artisan deploy:push staging --plugins --themes

# 本番からプル
php artisan deploy:pull production --uploads
```

## 設定ファイル（dixlase-deploy.json）

### 基本構造

```json
{
  "_comment": "コメントはアンダースコアで始まるキーで記述可能",

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

### 環境変数の使用

機密情報は環境変数で管理できます（`${VAR_NAME}` 形式）：

```json
{
  "database": {
    "password": "${DB_PASSWORD}"
  }
}
```

`.env` ファイルまたはシステム環境変数で設定：

```bash
export STAGING_DB_USER=myuser
export STAGING_DB_PASSWORD=mypassword
```

### SSH設定

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

**推奨**: パスワード認証ではなく、公開鍵認証を使用してください。

### FTP/SFTP設定

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

### 除外パターン

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

### フック（Hooks）

デプロイ前後にコマンドを実行できます：

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

## コマンドリファレンス

### deploy:init

設定ファイルを生成します。

```bash
php artisan deploy:init [--force]
```

| オプション | 説明 |
|-----------|------|
| `--force` | 既存の設定ファイルを上書き |

### deploy:doctor

設定と環境をチェックします。

```bash
php artisan deploy:doctor
```

チェック項目：
- 設定ファイルの存在と妥当性
- rsync、lftp、ssh、mysql、mysqldump、gzipの存在
- 環境設定の確認

### deploy:list

利用可能な環境を一覧表示します。

```bash
php artisan deploy:list
```

### deploy:push

ローカルからリモートにデータをプッシュします。

```bash
php artisan deploy:push <environment> [options]
```

| オプション | 説明 |
|-----------|------|
| `--core` | コアファイルを同期 |
| `--plugins` | プラグインを同期 |
| `--themes` | テーマを同期 |
| `--custom` | カスタムフォルダを同期 |
| `--uploads` | アップロードファイルを同期 |
| `--database` | データベースを同期 |
| `--all` | すべてを同期（データベース含む） |
| `--tables=*` | 特定のテーブルのみ同期 |
| `--dry-run` | 実際には同期せず、何が行われるかを表示 |
| `--force` | 確認プロンプトをスキップ |

**例:**

```bash
# プラグインとテーマをステージングにプッシュ
php artisan deploy:push staging --plugins --themes

# データベースの特定テーブルのみプッシュ
php artisan deploy:push staging --database --tables=posts --tables=pages

# すべてを本番にプッシュ（確認あり）
php artisan deploy:push production --all

# ドライランで確認
php artisan deploy:push staging --all --dry-run
```

### deploy:pull

リモートからローカルにデータをプルします。

```bash
php artisan deploy:pull <environment> [options]
```

オプションは `deploy:push` と同じです。

**例:**

```bash
# 本番からアップロードファイルをプル
php artisan deploy:pull production --uploads

# ステージングからデータベースをプル
php artisan deploy:pull staging --database

# 本番からすべてをプル
php artisan deploy:pull production --all --force
```

## 同期対象

### ファイル同期

| ターゲット | デフォルトパス | 説明 |
|-----------|---------------|------|
| core | / | Dixlaseコアファイル |
| plugins | plugins/ | プラグインディレクトリ |
| themes | themes/ | テーマディレクトリ |
| custom | custom/ | カスタムコードディレクトリ |
| uploads | storage/app/public/ | アップロードファイル |

### データベース同期

- **全テーブル同期**: `--database` オプション
- **特定テーブル同期**: `--tables=table1 --tables=table2`

データベース同期時、以下の処理が自動的に行われます：

1. ソース環境のデータベースをダンプ
2. URL/パスの置換（vhost、dixlase_path）
3. ターゲット環境にインポート

## セキュリティ

### 設定ファイルの保護

`dixlase-deploy.json` には機密情報が含まれる可能性があります。必ず `.gitignore` に追加してください：

```gitignore
dixlase-deploy.json
```

### 環境変数の使用

パスワードなどの機密情報は環境変数で管理することを推奨します：

```json
{
  "database": {
    "password": "${DB_PASSWORD}"
  }
}
```

### SSH鍵認証

パスワード認証ではなく、SSH鍵認証を使用してください：

```bash
# SSH鍵の生成
ssh-keygen -t ed25519 -C "deploy@example.com"

# 公開鍵をリモートサーバーに追加
ssh-copy-id -i ~/.ssh/id_ed25519.pub user@example.com
```

## トラブルシューティング

### rsyncが見つからない

```bash
# macOS
brew install rsync

# Ubuntu/Debian
sudo apt-get install rsync
```

### lftpが見つからない

```bash
# macOS
brew install lftp

# Ubuntu/Debian
sudo apt-get install lftp
```

### SSH接続エラー

1. SSH鍵が正しく設定されているか確認
2. ホスト名とポートが正しいか確認
3. ファイアウォール設定を確認

```bash
# 接続テスト
ssh -p 22 user@example.com
```

### データベース接続エラー

1. データベース認証情報を確認
2. リモートサーバーでmysqlコマンドが利用可能か確認
3. ファイアウォールでMySQLポートが開いているか確認

### パーミッションエラー

リモートサーバーでファイルの書き込み権限があるか確認してください。

## ベストプラクティス

1. **本番環境へのプッシュ前にステージングでテスト**
2. **データベースのバックアップを取る**
3. **`--dry-run` で事前確認**
4. **フックを活用してメンテナンスモードを有効化**
5. **機密情報は環境変数で管理**
6. **設定ファイルはバージョン管理から除外**

## 関連コマンド

- `php artisan deploy:init` - 設定ファイル生成
- `php artisan deploy:doctor` - 環境チェック
- `php artisan deploy:list` - 環境一覧
- `php artisan deploy:push` - プッシュ
- `php artisan deploy:pull` - プル
