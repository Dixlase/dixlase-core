# Docker セットアップ

> **[English version](../../installation/docker.md)**

Docker 環境では、すべてのサービスが事前設定されたローカル開発環境を提供します。

## クイックスタート

```bash
# 1. リポジトリをクローン
git clone <repository-url> dixlase
cd dixlase/docker

# 2. 環境ファイルをコピー
cp html/.env.example html/.env

# 3. 全サービスを起動
docker compose up -d

# 4. PHP 依存関係をインストール
docker exec -i dixlase-laravel.test-1 composer install

# 5. アプリケーションキーを生成
docker exec -i dixlase-laravel.test-1 php artisan key:generate

# 6. フロントエンドアセットをビルド
docker exec -i dixlase-vite-1 npm install
docker exec -i dixlase-vite-1 npm run build

# 7. インストーラを開く
# http://localhost:8080 にアクセス
```

## サービス一覧

| サービス | ポート | URL | 説明 |
|---------|--------|-----|------|
| Web (Nginx) | 8080 | `http://localhost:8080` | メインアプリケーション |
| Web (HTTPS) | 443 | `https://localhost` | SSL アクセス |
| PHP-FPM | 9000 | （内部） | PHP 処理 |
| MariaDB | 3306 | （内部） | データベース |
| phpMyAdmin | 8081 | `http://localhost:8081` | データベース管理 |
| Vite | 5173 | `http://localhost:5173` | HMR 開発サーバー |
| Mailpit | 8025 | `http://localhost:8025` | メールテスト |
| Redis | 6379 | （内部） | キャッシュストア |
| Selenium | 4444 | `http://localhost:4444` | ブラウザテスト |

## デフォルトデータベース認証情報

| 設定 | 値 |
|------|-----|
| ホスト | `mysql` |
| ポート | `3306` |
| データベース | `dixlase` |
| ユーザー名 | `dixlase` |
| パスワード | `dixlase` |
| Root パスワード | `root` |
| テーブルプレフィックス | `dls_` |

[インストールウィザード](wizard.md)でこれらの値を使用してください。

## デフォルトメール設定

Mailpit はローカルテスト用にすべての送信メールをキャプチャします：

| 設定 | 値 |
|------|-----|
| メーラー | `smtp` |
| ホスト | `mailpit` |
| ポート | `1025` |
| 暗号化 | （なし） |

キャプチャされたメールは `http://localhost:8025` で確認できます。

## よく使うコマンド

### Artisan

```bash
# Artisan コマンドの実行
docker exec -i dixlase-laravel.test-1 php artisan <command>

# マイグレーション実行
docker exec -i dixlase-laravel.test-1 php artisan migrate

# 全キャッシュクリア
docker exec -i dixlase-laravel.test-1 php artisan optimize:clear

# テスト実行
docker exec -i dixlase-laravel.test-1 php artisan test --compact
```

### アセットビルド

```bash
# HMR 付き開発ビルド
docker exec -i dixlase-vite-1 npm run dev

# 本番ビルド
docker exec -i dixlase-vite-1 npm run build
```

### コンテナ管理

```bash
# サービス起動
docker compose up -d

# サービス停止
docker compose down

# ログ表示
docker compose logs -f laravel.test

# コンテナ再ビルド（Dockerfile 変更後）
docker compose build --no-cache
docker compose up -d
```

## トラブルシューティング

### ポート競合

ポートが既に使用中の場合、`docker-compose.yml` でホストポートマッピングを変更してください。例えば、Web ポートを 8080 から 9090 に変更する場合：

```yaml
web:
  ports:
    - "9090:80"   # 8080 から変更
```

### パーミッションエラー

コンテナ内でファイルパーミッションエラーが発生する場合：

```bash
docker exec -i dixlase-laravel.test-1 chmod -R 775 storage bootstrap/cache
docker exec -i dixlase-laravel.test-1 chown -R www-data:www-data storage bootstrap/cache
```

### データベース接続エラー

アプリケーションがデータベースに接続できない場合：

1. MySQL コンテナが起動中か確認: `docker compose ps`
2. `.env` ファイルで `DB_HOST=mysql`（`localhost` ではなく Docker サービス名）を使用しているか確認
3. コンテナ起動後数秒待つ — MariaDB の初期化に時間がかかる場合があります

### Vite / アセットエラー

「Unable to locate file in Vite manifest」エラーが表示される場合：

```bash
docker exec -i dixlase-vite-1 npm run build
```

開発中のホットリロードには、代わりに `npm run dev` を実行してください。

## 次のステップ

コンテナが起動し `http://localhost:8080` にアクセスできたら、[インストールウィザード](wizard.md)に進んでセットアップを完了してください。
