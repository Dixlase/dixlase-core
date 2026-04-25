# Dixlase アップグレード手順

Dixlase をリリース済みのバージョン間でアップグレードする際の Runbook（例: v0.1.0 → v0.1.x、v0.1 → v0.2）。

> v0.1.0 リリース前は、スキーマ変更は開発環境での `php artisan migrate:fresh` で吸収している。本 Runbook は最初の安定版リリースがタグ付けされ、`database/migration-lock.json` がコミットされた時点から有効となる。

## 1. 前提条件

- 本番ホストに SSH / シェル接続でき、`docker exec` / `artisan` / `composer` を実行できること
- サイトのバックアップ先に書き込み可能なこと
- リクエスト停止（`artisan down`）が許容されるメンテナンス枠を確保していること
- 対象バージョンのリリースノートとプラグイン固有のアップグレード情報を入手済みであること

## 2. マイグレーション依存順序

プラグイン側のマイグレーションは以下の順序で実行しなければならない（外部キーが上流プラグインのテーブルを参照するため）。コアは常に最初。

1. **コア**（`database/migrations/`）
2. **DixlaseAuthority** — ロール／権限テーブル。members 連携プラグインから参照される
3. **DixlasePages** — ページツリー。下流コンテンツから利用される
4. **DixlaseLegal** — 法的ページ。Pages に依存する
5. **DixlaseMenus** — メニュー項目。Pages / Legal / Authority のルートを参照しうる
6. **DixlaseInquiry** — フォーム送信記録。下流依存なし
7. **DixlaseOfficialDocs** — 文書レコード。Pages を参照する可能性あり

マイグレーションが存在しないプラグイン（執筆時点で DixlaseSigner など）はスキップする。デプロイ後に `php artisan dls:plugin:list` で有効プラグインを確認すること。

## 3. バックアップ

本番ホストに手を入れる前に必ず実施する。

### 3.1 データベースダンプ

```bash
docker exec dixlase-mysql mariadb-dump \
  --single-transaction \
  --routines \
  --events \
  --triggers \
  -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" \
  > "/backup/dixlase-$(date +%Y%m%d-%H%M%S).sql"
```

ダンプが空でないことを確認し、gzip 圧縮する:

```bash
test -s /backup/dixlase-*.sql && gzip /backup/dixlase-*.sql
```

### 3.2 ファイルシステムスナップショット

`storage/`、`public/uploads/`、`.env`、`custom/` 配下のサイト固有ファイル、そして現行の `database/migration-lock.json` をバックアップする。

```bash
tar czf "/backup/dixlase-files-$(date +%Y%m%d-%H%M%S).tar.gz" \
  .env \
  storage \
  public/uploads \
  custom \
  database/migration-lock.json
```

## 4. アップグレード手順

各ステップは Docker ホスト上のプロジェクトルートから実行する。

### 4.1 メンテナンスモードに入る

```bash
docker exec dixlase-php php artisan down --render="errors::503"
```

利用者には 503 ページが返る。ウィンドウは短く保つこと。

### 4.2 新リリースを取得

デプロイ方式による:

- Git ベース: `git fetch && git checkout v0.1.x && git submodule update --init --recursive`
- Tarball ベース: リリース tarball をサイトルートに展開（`.env` と `storage/` は温存）

### 4.3 PHP 依存パッケージをインストール

```bash
docker exec dixlase-php composer install --no-dev --optimize-autoloader --no-interaction
```

`--no-dev` により本番イメージから開発専用パッケージ（Pint、Larastan 等）を除外する。

### 4.4 マイグレーションの不可侵性を検証

```bash
docker exec dixlase-php php artisan dls:migration:lint
```

現在のマイグレーションファイルと `database/migration-lock.json` を照合する。lint が通れば、前回リリース以降ロック済みマイグレーションの編集・削除がないことが保証される。

**失敗した場合はアップグレードを中止し原因を調査する** — ロック済みマイグレーションが改変されており、監査証跡の前提が崩れている状態。変更を revert するか、影響を把握した上で `dls:migration:lint --lock` を再発行してドリフトを受け入れるかを判断する（安易な再発行は避ける）。

### 4.5 マイグレーションを依存順序で実行

```bash
# コア
docker exec dixlase-php php artisan migrate --force

# プラグイン（依存順序で）
docker exec dixlase-php php artisan dls:plugin:migrate DixlaseAuthority --force
docker exec dixlase-php php artisan dls:plugin:migrate DixlasePages --force
docker exec dixlase-php php artisan dls:plugin:migrate DixlaseLegal --force
docker exec dixlase-php php artisan dls:plugin:migrate DixlaseMenus --force
docker exec dixlase-php php artisan dls:plugin:migrate DixlaseInquiry --force
docker exec dixlase-php php artisan dls:plugin:migrate DixlaseOfficialDocs --force

# テーマ（存在する場合）
docker exec dixlase-php php artisan dls:theme:migrate --force
```

本番環境では `--force` が必須（付けない場合 Laravel が中断する）。サイトにインストールされていないプラグインはスキップする。

### 4.6 キャッシュを更新

キャッシュは**必ずコンテナ内**で再生成する。ホスト側で実行するとコンテナ内のパスと不一致になりエラーとなる。

```bash
docker exec dixlase-php php artisan config:cache
docker exec dixlase-php php artisan route:cache
docker exec dixlase-php php artisan view:cache
docker exec dixlase-php php artisan event:cache
```

### 4.7 フロントエンドアセットをビルド（ソース配布の場合）

リリース物にビルド済みアセットが含まれていない場合:

```bash
docker exec dixlase-vite npm ci
docker exec dixlase-vite npm run build
```

### 4.8 トラフィック開放前の動作確認

```bash
# マイグレーション状況: 未実行が 0 件であることを確認
docker exec dixlase-php php artisan migrate:status | tail -20

# ヘルスチェック（社内向け、トラフィック開放前）
docker exec dixlase-php php artisan dls:health:check || true
```

### 4.9 メンテナンスモード解除

```bash
docker exec dixlase-php php artisan up
```

公開サイトが 200 を返すこと、管理画面へのログインができることを確認する。

## 5. ロールバック

4.3 以降のステップで失敗し、前進での復旧が不可能な場合はロールバックする。

### 5.1 データベースを復元

```bash
gunzip -c /backup/dixlase-YYYYMMDD-HHMMSS.sql.gz | \
  docker exec -i dixlase-mysql mariadb -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE"
```

### 5.2 ファイルを復元

```bash
tar xzf /backup/dixlase-files-YYYYMMDD-HHMMSS.tar.gz -C /srv/dixlase/
```

### 5.3 前リリースをチェックアウト

Git ベース:

```bash
git checkout <previous-tag>
git submodule update --init --recursive
docker exec dixlase-php composer install --no-dev --optimize-autoloader --no-interaction
```

### 5.4 ステップ 4.6（キャッシュ更新）と 4.9（メンテナンス解除）を再実行する。

## 6. ステージングでのドライラン

本 Runbook を初めて実行する際は、本番相当のデータ量・プラグイン構成をもつステージング環境で先行実施し、下表に結果を記入してコミットすること。

| ステップ | ステージング所要時間 | 備考 |
| --- | --- | --- |
| 3.1 データベースダンプ | _初回ステージング実行時に記入_ | |
| 3.2 ファイルシステムスナップショット | _初回ステージング実行時に記入_ | |
| 4.3 composer install | _初回ステージング実行時に記入_ | |
| 4.4 dls:migration:lint | _初回ステージング実行時に記入_ | |
| 4.5 マイグレーション（合計） | _初回ステージング実行時に記入_ | |
| 4.6 キャッシュ更新 | _初回ステージング実行時に記入_ | |
| 4.7 npm build | _初回ステージング実行時に記入_ | |
| **全体（down → up）** | _初回ステージング実行時に記入_ | |

ステージング実行中に遭遇したトラブルシュート事項は [operations/emergency/](emergency/index.md) に追記する。

## 7. 関連

- [メンテナンス](maintenance/index.md) — 定常的な DB クリーンアップ
- [緊急対応](emergency/index.md) — 本番アップグレード失敗時の復旧
- マイグレーション不可侵ポリシー: コア `CLAUDE.md` の「マイグレーションの編集方針」節
