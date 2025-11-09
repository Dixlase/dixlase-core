# Composer Local Setup Guide

## 概要

Dixlaseでは、コアの`composer.json`と`.gitignore`を素の状態に保つため、以下の仕組みを採用しています:

1. **composer.local.json**: カスタムプラグインやパッケージの設定を分離
2. **.git/info/exclude**: プラグインのGit除外ルールを自動管理

## セットアップ手順

### 1. composer.local.jsonの作成

```bash
cp composer.local.json.example composer.local.json
```

### 2. プラグインのautoload設定を追加

`composer.local.json`を編集して、プラグインのautoload設定を追加します:

```json
{
    "autoload": {
        "psr-4": {
            "Plugins\\YourPlugin\\App\\": "plugins/YourPlugin/app",
            "Plugins\\YourPlugin\\Database\\Factories\\": "plugins/YourPlugin/database/factories",
            "Plugins\\YourPlugin\\Database\\Seeders\\": "plugins/YourPlugin/database/seeders"
        }
    }
}
```

### 3. 追加パッケージが必要な場合

```json
{
    "require": {
        "vendor/package": "^1.0"
    },
    "autoload": {
        "psr-4": {
            "Plugins\\YourPlugin\\App\\": "plugins/YourPlugin/app"
        }
    }
}
```

### 4. autoloadの再生成

設定を追加したら、autoloadを再生成します:

```bash
composer dump-autoload
```

または、パッケージを追加した場合:

```bash
composer update
```

## 仕組み

- **wikimedia/composer-merge-plugin**: `composer.json`と`composer.local.json`を自動的にマージ
- **composer.local.json**: Gitの監視対象外（`.gitignore`に追加済み）
- **composer.json**: コアの設定のみを含み、素の状態を維持

## 利点

1. **コアのクリーンさ**: `composer.json`を素の状態に保てる
2. **リリース時の手間削減**: カスタム設定を削除する必要がない
3. **環境ごとの柔軟性**: 開発環境とプロダクション環境で異なる設定が可能
4. **コンフリクト回避**: 複数の開発者が異なるプラグインを使用しても衝突しない

## 注意事項

- `composer.local.json`は各環境で個別に管理してください
- 新しい環境では`composer.local.json.example`からコピーして設定してください
- プラグインを追加したら必ず`composer dump-autoload`を実行してください

## Git除外ルールの自動管理

### 自動更新のタイミング

`.git/info/exclude`は以下のタイミングで自動的に更新されます:

1. **プラグインインストール時** (ZIPアップロード)
2. **プラグイン新規作成時** (`make:plugin`コマンド)
3. **プラグインアンインストール時**

### 手動で同期する場合

`plugins/`ディレクトリ内の実際のプラグインディレクトリと`.git/info/exclude`を同期:

```bash
docker exec dixlase-laravel.test-1 php artisan git:sync-exclusions
```

**重要**: このコマンドは**ファイルシステムベース**で動作します:
- **検出**: `plugins/`ディレクトリに実際に存在するディレクトリを自動検出
- **追加**: 実際に存在するが`.git/info/exclude`にないプラグインを追加
- **削除**: `.git/info/exclude`にあるが実際には存在しないプラグインを削除
- **差分表示**: 追加/削除されるプラグインを色付きで表示
- **DB非依存**: DBの登録状態に関係なく、実際のディレクトリを基準に同期

**使用例**:

```bash
# 手動でプラグインディレクトリを削除した後
rm -rf plugins/OldPlugin
docker exec dixlase-laravel.test-1 php artisan tinker --execute="DB::table('plugins')->where('directory', 'OldPlugin')->delete();"
docker exec dixlase-laravel.test-1 php artisan git:sync-exclusions

# 出力:
# Will remove from .git/info/exclude:
#   - OldPlugin
# ✓ Successfully synced .git/info/exclude
#   Removed: 1 plugin(s)
```

### .git/info/excludeの構造

```
# === Plugin exclusions (auto-managed) ===
# Do not edit this section manually
!plugins/DixlaseMenu
!plugins/DixlasePages
!plugins/DixlaseBlog
# === End plugin exclusions ===
```

**重要**: `=== Plugin exclusions (auto-managed) ===`セクション内は自動管理されるため、手動で編集しないでください。

## トラブルシューティング

### クラスが見つからない場合

```bash
docker exec dixlase-laravel.test-1 composer dump-autoload
```

### merge-pluginが動作しない場合

```bash
docker exec dixlase-laravel.test-1 composer require wikimedia/composer-merge-plugin
docker exec dixlase-laravel.test-1 composer update
```

### 設定が反映されない場合

1. `composer.local.json`の構文が正しいか確認
2. `composer.json`の`extra.merge-plugin`設定を確認
3. キャッシュをクリア: `docker exec dixlase-laravel.test-1 composer clear-cache`

### Git除外ルールが反映されない場合

```bash
# 除外ルールを確認
cat .git/info/exclude

# 手動で同期
docker exec dixlase-laravel.test-1 php artisan git:sync-exclusions
```
