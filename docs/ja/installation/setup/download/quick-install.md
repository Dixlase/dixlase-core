# クイックインストールスクリプト

1行のコマンドでサーバーにDixlaseをインストールします。

## 前提条件

- PHP 8.2 以上
- Composer
- MySQL / MariaDB
- サーバーへのSSHアクセス

## 使い方

```bash
curl -sS https://install.dixlase.com | php
```

スクリプトが行うこと:

1. PHPバージョンと必要な拡張機能のチェック
2. 最新のDixlaseリリースをダウンロード
3. `composer install` を実行
4. アプリケーションキーを生成
5. ディレクトリのパーミッションを設定
6. WebベースのインストールウィザードへのURLを表示

## オプション

```bash
# インストールディレクトリを指定
curl -sS https://install.dixlase.com | php -- --dir=/var/www/dixlase

# バージョンを指定
curl -sS https://install.dixlase.com | php -- --version=1.0.0

# Composerインストールをスキップ（手動で実行する場合）
curl -sS https://install.dixlase.com | php -- --no-composer
```

## インストール後

ブラウザでサイトのURLにアクセスしてください。[インストールウィザード](../../wizard.md)がデータベース設定、管理者アカウント作成、初期設定をガイドします。

## トラブルシューティング

### Permission denied エラー

```bash
sudo chown -R www-data:www-data /path/to/dixlase
sudo chmod -R 755 /path/to/dixlase
sudo chmod -R 775 /path/to/dixlase/storage /path/to/dixlase/bootstrap/cache
```

### Composer が見つからない

先にComposerをインストールしてください:

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```
