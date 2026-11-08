# Composer Create-Project

Composer を使って [Packagist](https://packagist.org/packages/dixlase/dixlase-core) から Dixlase をインストールします。GitHub のトークンは必要ありません。

リリース ZIP と違い、Composer でのインストールはソースからのインストールです。ビルド済みのフロントエンドアセットは含まれませんが、`create-project` が終わる前に Node.js でビルドします。

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

# 2. ローカルのサーバーを起動する
php scripts/serve.php
```

`http://127.0.0.1:8000` を開くと、[インストールウィザード](../../wizard.md)がデータベース、管理者アカウント、メールの設定を案内します。データベースサーバーを別に用意せずに始める場合は、データベースの手順で **SQLite** を選んでください。

## `create-project` がすること

- `.env.example` から `.env` を作り、アプリケーションキーを生成し、空の `database/database.sqlite` を作成します。
- コアのフロントエンドアセットをビルドします（`npm ci --ignore-scripts` のあと `npm run build`）。コア自身のアセットは Composer のパッケージに含まれないためです。
- 同梱テーマのアセットを公開します。DixlaseOnePage は Composer が `themes/DixlaseOnePage/` に配置し、ビルド済みのスタイルシートとスクリプトを同梱しています。テーマのリリースが 1 回だけビルドしたもので、テーマの署名に覆われているため、**ビルドし直すことはありません**。`create-project` は、コアがそれを配信するためのシンボリックリンク（`public/assets/themes/DixlaseOnePage`）を作るだけです。Composer のパッケージはこのリンクを運べません。
- 最後に残りの手順を表示します。

サーバーは起動しません。起動すると、コマンドが終わらなくなるためです。

npm が見つからないときや、途中の手順が失敗したときも、`create-project` は成功し、手で実行する手順を表示します。

```bash
npm ci && npm run build
php artisan dls:theme:symlink create DixlaseOnePage
```

デプロイの前に別の場所でビルドする場合など、ビルドとリンクを飛ばすには `DIXLASE_SKIP_BUILD=1` を指定してください。

```bash
DIXLASE_SKIP_BUILD=1 composer create-project dixlase/dixlase-core dixlase
```

`--no-scripts` でも飛ばせますが、`.env` の作成とアプリケーションキーの生成も行われません。

## ローカルのサーバー

`php scripts/serve.php` は、ウィザードのために PHP の組み込みサーバーを起動します。`--host` と `--port` を指定できます（`php scripts/serve.php --port=8080`）。実行するのは次のコマンドと同じです。

```bash
cd public
PHP_CLI_SERVER_WORKERS=4 php -d variables_order=EGPCS -d max_execution_time=300 \
  -S 127.0.0.1:8000 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
```

- `public/` から実行します。ルータスクリプト（`php artisan serve` が使うものと同じ）は、今いるディレクトリを Web ルートとして扱います。
- `variables_order=EGPCS` は、ウィザードが実行するコマンドに `PATH` を残すためのものです。これが無いと、`composer dump-autoload` が「env: php: No such file or directory」で失敗します。
- `PHP_CLI_SERVER_WORKERS=4` で、遅いページが 1 つあっても、ほかのページが止まりません。Windows では使えないので付けません。
- `max_execution_time=300` で、マイグレーションを実行する最後の手順に十分な時間を与えます。

ウィザードには `php artisan serve` を使わないでください。`.env` が変わるたびに再起動するので、`.env` を書いた手順の応答が切れます。`--no-reload` を付けると、起動時に読んだ `.env` を動いている間ずっと使い続けるため、ウィザードが書いたデータベースの設定が反映されず、最後の手順で最初の画面に戻ってしまいます。

### サーバーの停止と再起動

サーバーは、起動したターミナルが開いている間だけ動きます。

- **止めるとき**は、`php scripts/serve.php` を実行しているターミナルで `Ctrl+C` を押します。ターミナルを閉じても止まります。
- **もう一度起動するとき**は、サイトのディレクトリで同じコマンドを実行します。ウィザードのときと同じように、インストール済みのサイトを表示します。

  ```bash
  cd dixlase
  php scripts/serve.php
  ```

- ポート 8000 がすでに使われているときは、別の番号を指定します（`php scripts/serve.php --port=8080`）。

コアの更新やロールバックのあとは、サーバーを起動し直してください。どちらも `public/` を作り直すため、起動中のサーバーは古いディレクトリを見たままになります。

Web サーバーで公開する場合は、[本番環境](../production/index.md)を参照してください。

## バージョンを指定してインストールする

```bash
composer create-project dixlase/dixlase-core dixlase "0.2.*"
```

Composer は、条件に合うタグ付きリリースのうち最も新しいものを選びます。
