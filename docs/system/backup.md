# Dixlase バックアップシステム

Dixlaseのファイルとデータベースをバックアップするためのコマンドラインツールです。

## 概要

バックアップシステムは以下の機能を提供します：

- **ファイルバックアップ**: 指定したディレクトリをZIPファイルに圧縮
- **データベースバックアップ**: MySQLデータベースをSQLファイルにダンプ
- **バックアップ一覧**: 作成済みバックアップの確認
- **クリーンアップ**: 古いバックアップの自動削除

## 必要条件

- PHP 8.2以上
- ZipArchive PHP拡張
- mysqldump（データベースバックアップ用）

## コマンド一覧

### backup:create - バックアップの作成

```bash
php artisan backup:create [options]
```

#### ファイルバックアップオプション

| オプション | 説明 |
|-----------|------|
| `--all` | すべてのファイルとデータベースをバックアップ |
| `--core` | コアファイル（app, bootstrap, config, database/migrations, database/seeders, lang, public, resources, routes, stubs） |
| `--plugins` | pluginsディレクトリ |
| `--themes` | themesディレクトリ |
| `--custom` | customディレクトリ |
| `--storage-public` | storage/app/publicディレクトリ |
| `--storage-private` | storage/app/privateディレクトリ |
| `--logs` | storage/logsディレクトリ |

#### データベースバックアップオプション

| オプション | 説明 |
|-----------|------|
| `--database` | データベース全体をバックアップ |
| `--tables=*` | 特定のテーブルのみバックアップ（複数指定可能） |

#### その他のオプション

| オプション | 説明 |
|-----------|------|
| `--files-only` | ファイルのみバックアップ（--allと併用時にDBをスキップ） |
| `--db-only` | データベースのみバックアップ（--allと併用時にファイルをスキップ） |

#### 使用例

```bash
# すべてをバックアップ
php artisan backup:create --all

# プラグインとテーマのみバックアップ
php artisan backup:create --plugins --themes

# データベースのみバックアップ
php artisan backup:create --database

# 特定のテーブルのみバックアップ
php artisan backup:create --tables=members --tables=base_settings

# コアファイルとデータベースをバックアップ
php artisan backup:create --core --database

# すべてのファイルをバックアップ（データベースは除く）
php artisan backup:create --all --files-only

# アップロードファイルとログをバックアップ
php artisan backup:create --storage-public --storage-private --logs
```

### backup:list - バックアップ一覧

```bash
php artisan backup:list [options]
```

#### オプション

| オプション | 説明 |
|-----------|------|
| `--files` | ファイルバックアップのみ表示 |
| `--database` | データベースバックアップのみ表示 |

#### 使用例

```bash
# すべてのバックアップを表示
php artisan backup:list

# ファイルバックアップのみ表示
php artisan backup:list --files

# データベースバックアップのみ表示
php artisan backup:list --database
```

### backup:cleanup - 古いバックアップの削除

```bash
php artisan backup:cleanup [options]
```

#### オプション

| オプション | 説明 |
|-----------|------|
| `--days=30` | 指定日数より古いバックアップを削除（デフォルト: 30日） |
| `--all` | すべてのバックアップを削除 |
| `--force` | 確認なしで削除 |

#### 使用例

```bash
# 30日より古いバックアップを削除
php artisan backup:cleanup

# 7日より古いバックアップを削除
php artisan backup:cleanup --days=7

# すべてのバックアップを削除
php artisan backup:cleanup --all

# 確認なしで削除
php artisan backup:cleanup --days=14 --force
```

## バックアップファイルの保存場所

バックアップファイルは `database/backups/` ディレクトリに保存されます。

### ファイル命名規則

- ファイルバックアップ: `dixlase_files_YYYYMMDD_HHMMSS.zip`
- データベースバックアップ: `dixlase_db_YYYYMMDD_HHMMSS.sql`

## 除外されるファイル・ディレクトリ

ファイルバックアップでは以下が自動的に除外されます：

- `.git` ディレクトリ
- `node_modules` ディレクトリ
- `vendor` ディレクトリ
- `.env` ファイル
- `*.log` ファイル
- `.DS_Store` ファイル
- `Thumbs.db` ファイル
- `*.cache` ファイル

## 自動バックアップの設定

cronジョブを使用して定期的なバックアップを設定できます：

```bash
# 毎日午前3時にすべてをバックアップ
0 3 * * * cd /path/to/dixlase && php artisan backup:create --all

# 毎週日曜日にバックアップをクリーンアップ
0 4 * * 0 cd /path/to/dixlase && php artisan backup:cleanup --days=30 --force
```

## トラブルシューティング

### ZIPファイルの作成に失敗する

- PHP ZipArchive拡張がインストールされているか確認してください
- `database/backups/` ディレクトリに書き込み権限があるか確認してください

### mysqldumpが失敗する

- mysqldumpコマンドがインストールされているか確認してください
- データベース接続設定が正しいか確認してください
- データベースユーザーに適切な権限があるか確認してください

### バックアップファイルが大きすぎる

- 特定のディレクトリのみをバックアップしてください
- 古いバックアップを定期的にクリーンアップしてください
- 大きなファイル（メディアファイルなど）は別途管理することを検討してください

## セキュリティに関する注意

- バックアップファイルには機密情報が含まれる可能性があります
- `database/backups/` ディレクトリへのアクセスを制限してください
- バックアップファイルを安全な場所に移動・保管してください
- 本番環境のバックアップは暗号化することを推奨します
