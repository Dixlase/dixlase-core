# バックアップのコマンド

バックアップは管理画面のほか、コマンドラインからも作れます。このページでは `dls:backup:create` コマンドと、スクリプトから頼れる機械可読な出力を説明します。

## バックアップを作る

```bash
php artisan dls:backup:create
php artisan dls:backup:create --targets=database
php artisan dls:backup:create --targets=database,media,private --retention=30
```

| オプション | 意味 |
| --- | --- |
| `--targets=` | 対象をカンマ区切りで指定します: `database`、`media`、`private`、`custom`、`logs`。省略すると `logs` 以外のすべてです。 |
| `--retention=` | 保持日数です。空にすると無期限に残します。 |
| `--format=` | `text`(既定)または `json` です。 |

記録済みのバックアップは `dls:backup:list` で一覧できます。

## 機械可読な出力(`--format=json`)

サイトを変更する直前に SSH 越しに実行するなど、コマンドを非対話的に動かすツールは、文章を読む代わりに結果を JSON で受け取れます。

```bash
php artisan dls:backup:create --targets=database --format=json
```

このモードでは、**stdout に JSON オブジェクトを 1 行で 1 つだけ**書き、ほかには何も書きません。進行状況のメッセージは stderr に出ます。失敗したときもオブジェクトを書くので、スクリプトは常に stdout を解析できます。

```json
{"success":true,"backup_record_id":42,"file_path":"/var/www/html/storage/app/private/backups/dixlase-backup-20261117-093000-database.zip","file_name":"dixlase-backup-20261117-093000-database.zip","file_size":2048,"duration":1.23,"targets":["database"],"hash":"9f86d08...","error":null}
```

### フィールド

次のフィールド名は契約です。リリースで破壊的変更として告知しないかぎり、名前を変えたり削除したりしません。フィールドが増えることはあるので、スクリプトは知らないフィールドを無視してください。

| フィールド | 型 | 意味 |
| --- | --- | --- |
| `success` | 真偽値 | バックアップを作れたら `true` です。 |
| `backup_record_id` | 整数 または `null` | バックアップの記録の ID です。管理画面や `dls:backup:list` でこのバックアップを探すときに使います。失敗時は `null` です。 |
| `file_path` | 文字列 または `null` | バックアップのアーカイブの絶対パスです。失敗時は `null` です。 |
| `file_name` | 文字列 または `null` | アーカイブのファイル名です。失敗時は `null` です。 |
| `file_size` | 整数 または `null` | アーカイブの大きさ(バイト)です。失敗時は `null` です。 |
| `duration` | 数値 または `null` | かかった秒数で、小数第 2 位に丸めます。失敗時は `null` です。 |
| `targets` | 文字列の配列 | 含めた対象です。失敗時は、指定された対象です。 |
| `hash` | 文字列 または `null` | 検証用に記録したアーカイブのハッシュです。失敗時は `null` です。 |
| `error` | 文字列 または `null` | 失敗した理由です。成功時は `null` です。 |

### 終了コード

終了コードはどちらの形式でも同じです。

| コード | 意味 |
| --- | --- |
| `0` | バックアップを作れました。 |
| `1` | バックアップに失敗しました。JSON オブジェクトの `error` に理由が入ります。 |
| `2` | オプションが正しくありません(知らない対象、または知らない `--format`)。知らない対象のときは JSON オブジェクトを書きます。知らない形式のときは、形式が決まっていないのでエラーだけを出します。 |

### 例

```bash
out=$(php artisan dls:backup:create --targets=database --format=json) || {
  echo "backup failed: $(printf '%s' "$out" | jq -r .error)" >&2
  exit 1
}
backup_id=$(printf '%s' "$out" | jq -r .backup_record_id)
```
