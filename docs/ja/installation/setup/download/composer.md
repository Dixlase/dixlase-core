# Composer Create-Project

Composer を使って [Packagist](https://packagist.org/packages/dixlase/dixlase-core) から Dixlase をインストールします。GitHub のトークンは必要ありません。

リリース ZIP と違い、Composer でのインストールはソースからのインストールです。ビルド済みのフロントエンドアセットは含まれないため、インストーラを開く前に Node.js で一度ビルドします。

## 必要なもの

- PHP 8.3 以上と、[インストールウィザード](../../wizard.md)が確認する拡張
- [Composer](https://getcomposer.org/) 2.x
- [Node.js](https://nodejs.org/) 20.19 以上（または 22.12 以上）と npm
- MySQL / MariaDB。データベースサーバー無しで始める場合は SQLite

## 手順

```bash
# 1. プロジェクトを作成する（最新のリリースが入ります）
composer create-project dixlase/dixlase-core dixlase
cd dixlase

# 2. コアのフロントエンドアセットをビルドする
npm install && npm run build

# 3. 同梱テーマをビルドする
php artisan dls:theme:build DixlaseOnePage
```

`create-project` は、`.env.example` から `.env` を作り、アプリケーションキーを生成し、空の `database/database.sqlite` も作成します。最後に残りの手順を表示します。アセットがすでにある手順は表示されません。

同梱テーマの DixlaseOnePage は、Composer が `themes/DixlaseOnePage/` に配置します。インストールウィザードがテーマをビルドするのはテーマをダウンロードしたときだけなので、このテーマは手順 3 でビルドする必要があります。ビルドしないと、フロントページとログイン画面がテーマのスクリプトとスタイル無しで表示されます。

## 次のステップ

プロジェクトのディレクトリで PHP の組み込みサーバーを起動して、インストールウィザードを開きます。

```bash
cd public
PHP_CLI_SERVER_WORKERS=4 php -d variables_order=EGPCS -d max_execution_time=300 \
  -S 127.0.0.1:8000 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
```

- `public/` から実行してください。ルータスクリプト（`php artisan serve` が使うものと同じ）は、今いるディレクトリを Web ルートとして扱います。
- `variables_order=EGPCS` は、ウィザードが実行するコマンドに `PATH` を残すためのものです。これが無いと、`composer dump-autoload` が「env: php: No such file or directory」で失敗します。
- `PHP_CLI_SERVER_WORKERS=4` で、遅いページが 1 つあっても、ほかのページが止まりません。Windows では使えないので外してください。
- `max_execution_time=300` で、マイグレーションを実行する最後の手順に十分な時間を与えます。

ウィザードには `php artisan serve` を使わないでください。`.env` が変わるたびに再起動するので、`.env` を書いた手順の応答が切れます。`--no-reload` を付けると、起動時に読んだ `.env` を動いている間ずっと使い続けるため、ウィザードが書いたデータベースの設定が反映されず、最後の手順で最初の画面に戻ってしまいます。

コアの更新やロールバックのあとは、サーバーを起動し直してください。どちらも `public/` を作り直すため、起動中のサーバーは古いディレクトリを見たままになります。

`http://127.0.0.1:8000` を開くと、[インストールウィザード](../../wizard.md)がデータベース、管理者アカウント、メールの設定を案内します。データベースサーバーを別に用意せずに始める場合は、データベースの手順で **SQLite** を選んでください。

Web サーバーで公開する場合は、[本番環境](../production/index.md)を参照してください。

## バージョンを指定してインストールする

```bash
composer create-project dixlase/dixlase-core dixlase "0.2.*"
```

Composer は、条件に合うタグ付きリリースのうち最も新しいものを選びます。
