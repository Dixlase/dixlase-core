# DB 権限分離

> 英語版は [`docs/operations/db-privilege-separation.md`](../../operations/db-privilege-separation.md) を参照。

このガイドは、Dixlase を **2 つの DB ユーザー** で運用する手順を解説する。
アプリ用(DML のみ) と マイグレーション用(DDL 含む)に分離することで、
誤配信された破壊的クエリが本番テーブルを DROP できない状態を作る。
背景は [2026-05-29 インシデント post-mortem](../incidents/2026-05-29-mysql-tables-dropped.md) を参照。

## 何が起きるか

| ユーザー                          | 用途                                                                                      | 権限                                                  |
| --------------------------------- | ----------------------------------------------------------------------------------------- | ----------------------------------------------------- |
| `DB_USERNAME` (既定 `dixlase`)    | アプリ実行時、Web リクエスト、queue worker、Tinker、通常の artisan                       | `SELECT, INSERT, UPDATE, DELETE` のみ                 |
| `DB_MIGRATE_USERNAME`             | `php artisan migrate --database=mysql_migrate`、デプロイスクリプト                       | `ALL PRIVILEGES` (CREATE, ALTER, DROP, TRUNCATE, …)   |

runtime アカウントが `DROP TABLE` を発行しようとした場合 — それが
誤配信された `RefreshDatabase` であろうと、暴発した `migrate:fresh` で
あろうと、SQL インジェクションペイロードであろうと、ただのタイポで
あろうと — MySQL/MariaDB がワイヤープロトコル層で拒否する。

## 有効化方法(新規インストール)

1. **migrator の認証情報を決める**:

   ```bash
   # .env (DB_USERNAME / DB_PASSWORD と並列)
   DB_MIGRATE_USERNAME=dixlase_migrator
   DB_MIGRATE_PASSWORD=<強いランダムパスワード>
   ```

2. **MySQL データディレクトリを初期化**。docker インストーラの init
   フック (`mysql-initdb/01-permissions.sh`) はデータディレクトリが
   空のときだけ走る。新規インストールであれば自動。既存環境への適用は
   後述「既存インストールへの適用」を参照。

3. **分離を検証**。runtime ユーザーで DDL を試す:

   ```bash
   docker exec dixlase-dev-mysql mariadb -udixlase -pdixlase dixlase \
     -e "DROP TABLE dls_members"
   # → ERROR 1142 (42000): DROP command denied to user 'dixlase'@'%'
   ```

   migrator でマイグレーションが通ることを確認:

   ```bash
   docker exec dixlase-dev-app php artisan migrate --database=mysql_migrate
   # → 以前と同じように migration が走る
   ```

## 有効化方法(既存インストール)

init フックは既に存在するデータディレクトリに対しては再発火しないため、
権限分離は手動適用が必要。**短時間 DB が止まる**ので、メンテナンス
ウィンドウ中に実施すること。

```bash
# 1. 先にバックアップ
docker exec dixlase-dev-mysql mariadb-dump -uroot -p<root-pass> dixlase \
  > backup-pre-privilege-split.sql

# 2. 分離を適用
docker exec -i dixlase-dev-mysql mariadb -uroot -p<root-pass> <<'SQL'
-- 既存アプリユーザーを DML のみに制限
REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'dixlase'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE, EXECUTE, SHOW VIEW
  ON `dixlase`.* TO 'dixlase'@'%';

-- migrator ユーザー作成
CREATE USER 'dixlase_migrator'@'%' IDENTIFIED BY '<強いランダムパスワード>';
GRANT ALL PRIVILEGES ON `dixlase`.* TO 'dixlase_migrator'@'%';

FLUSH PRIVILEGES;
SQL

# 3. .env に DB_MIGRATE_USERNAME / DB_MIGRATE_PASSWORD を設定

# 4. config を再キャッシュ + 上記と同じ DROP TABLE / migrate テストで検証
docker exec dixlase-dev-app php artisan config:cache
```

## 分離下でのマイグレーション実行

migration コマンドには常に `--database=mysql_migrate` を渡す:

```bash
php artisan migrate --database=mysql_migrate
php artisan migrate:rollback --database=mysql_migrate
```

`dls:plugin:install` / `dls:theme:install` 経由のプラグイン/テーマ
マイグレーションは設定済みの接続を使うため、`artisan migrate` の境界
だけ migrator 接続を指定すればよい。

デプロイスクリプトでは migration ステップだけ migrator に切り替える。
アプリ起動、アセットビルド、キャッシュウォームアップは既定 DB ユーザー
のまま。

## ロールバック

分離が運用上の問題を起こした場合、いつでも単一ユーザーモードに戻せる:

```bash
docker exec -i dixlase-dev-mysql mariadb -uroot -p<root-pass> <<'SQL'
GRANT ALL PRIVILEGES ON `dixlase`.* TO 'dixlase'@'%';
DROP USER IF EXISTS 'dixlase_migrator'@'%';
FLUSH PRIVILEGES;
SQL
```

その後、`.env` の `DB_MIGRATE_USERNAME` / `DB_MIGRATE_PASSWORD` を
削除する。

## 分離なしの場合の挙動

`DB_MIGRATE_USERNAME` が未設定なら、`config/database.php` の
`mysql_migrate` 接続は `DB_USERNAME` / `DB_PASSWORD` にフォールバック
する。つまり既存インストールはこれまで通り動作する — 権限分離は完全に
opt-in で、有効化には両方の環境変数 AND 新鮮(または手動で再分類した)
MySQL データディレクトリが必要。

## 関連

- [2026-05-29 Brand MySQL DROP post-mortem](../incidents/2026-05-29-mysql-tables-dropped.md) — この機能が存在する理由
- `config/database.php` — `mysql_migrate` 接続定義
- `dixlase-docker-installer` `mysql-initdb/01-permissions.sh` — 新規データディレクトリ用 init フック
