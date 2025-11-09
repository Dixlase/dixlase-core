# Git除外ルール自動管理システム

## 概要

Dixlaseでは、プラグインとテーマのGit除外ルールを`.git/info/exclude`で自動管理することで、コアの`.gitignore`を素の状態に保ちます。

## 仕組み

### 1. .git/info/excludeとは

- Gitリポジトリ固有の除外ルールを定義するファイル
- `.gitignore`と同じ構文だが、リポジトリに含まれない（ローカル設定）
- 各開発環境で独立して管理可能

### 2. 自動管理の対象

**プラグインディレクトリ**の除外ルール:
```
!plugins/PluginName
```

**テーマディレクトリ**の除外ルール:
```
!themes/ThemeName
```

これらの記述により、`plugins/*`や`themes/*`で除外されたディレクトリを個別に追跡対象に戻します。

## 自動更新のタイミング

### 1. プラグインインストール時（ZIPアップロード）

**トリガー**: 管理画面からプラグインをZIPでアップロード

**処理フロー**:
1. ZIPファイルを展開
2. プラグイン情報をDBに登録
3. マイグレーション実行
4. **GitExcludeHelper::addPluginExclusion()** を呼び出し
5. autoload更新

**実装場所**: `AdminPluginsSettingsController::upload()`

### 2. プラグイン新規作成時

**トリガー**: `php artisan make:plugin PluginName`コマンド実行

**処理フロー**:
1. プラグイン構造を生成
2. `plugin:install`コマンドを呼び出し
3. **GitExcludeHelper::addPluginExclusion()** を呼び出し

**実装場所**: `PluginInstall::handle()`

### 3. プラグインアンインストール時

**トリガー**: 管理画面からプラグインをアンインストール

**処理フロー**:
1. マイグレーションロールバック（オプション）
2. プラグインディレクトリ削除
3. DBからプラグイン情報削除
4. **GitExcludeHelper::removePluginExclusion()** を呼び出し
5. autoload更新

**実装場所**: `AdminPluginsSettingsController::uninstall()`

### 4. テーマインストール時

**トリガー**: `php artisan theme:install ThemeName`コマンド実行

**処理フロー**:
1. テーマディレクトリの存在確認
2. テーマ情報をDBに登録
3. **GitExcludeHelper::addThemeExclusion()** を呼び出し

**実装場所**: `ThemeInstall::handle()`

### 5. テーマアンインストール時

**トリガー**: `php artisan theme:uninstall ThemeName`コマンド実行

**処理フロー**:
1. テーマの無効化（アクティブな場合）
2. テーマディレクトリ削除（オプション）
3. **GitExcludeHelper::removeThemeExclusion()** を呼び出し
4. DBからテーマ情報削除

**実装場所**: `ThemeUninstall::handle()`

## 手動同期コマンド

`plugins/`ディレクトリ内の実際のプラグインディレクトリと`.git/info/exclude`を同期:

```bash
php artisan git:sync-exclusions
```

**重要**: このコマンドは**ファイルシステムベース**で動作します。
- `plugins/`と`themes/`ディレクトリに実際に存在するディレクトリを検出
- DBの登録状態に関係なく、実際のディレクトリを基準に同期
- `.`で始まるディレクトリは自動的に除外

**用途**:
- 初回セットアップ時
- `.git/info/exclude`が破損した場合
- **手動でプラグイン/テーマディレクトリを追加/削除した場合**
- DBに登録されていないプラグイン/テーマディレクトリがある場合

### 手動でプラグインを追加/削除した場合の対応

#### ケース1: プラグインディレクトリを手動で削除した場合

```bash
# 例: plugins/OldPlugin を手動で削除
rm -rf plugins/OldPlugin

# DBからも削除
docker exec dixlase-laravel.test-1 php artisan tinker --execute="DB::table('plugins')->where('directory', 'OldPlugin')->delete();"

# .git/info/excludeを同期（不要な行を自動削除）
docker exec dixlase-laravel.test-1 php artisan git:sync-exclusions
```

**出力例**:
```
Syncing .git/info/exclude with plugin directories...

Current exclusions in .git/info/exclude:
  - DixlaseMenu
  - TestPlugin
  - OldPlugin

Plugin directories in plugins/ folder:
  - DixlaseMenu
  - TestPlugin

Will remove from .git/info/exclude:
  - OldPlugin

✓ Successfully synced .git/info/exclude
  Removed: 1 plugin(s)
  Total exclusions: 2
```

#### ケース2: プラグインディレクトリを手動で追加した場合

```bash
# 例: 別の環境からplugins/NewPluginをコピー
cp -r /path/to/NewPlugin plugins/

# DBに登録
docker exec dixlase-laravel.test-1 php artisan plugin:install NewPlugin

# .git/info/excludeを同期（必要な行を自動追加）
docker exec dixlase-laravel.test-1 php artisan git:sync-exclusions
```

**出力例**:
```
Syncing .git/info/exclude with plugin directories...

Current exclusions in .git/info/exclude:
  - DixlaseMenu
  - TestPlugin

Plugin directories in plugins/ folder:
  - DixlaseMenu
  - TestPlugin
  - NewPlugin

Will add to .git/info/exclude:
  + NewPlugin

✓ Successfully synced .git/info/exclude
  Added: 1 plugin(s)
  Total exclusions: 3
```

#### ケース3: 同期が取れている場合

```bash
docker exec dixlase-laravel.test-1 php artisan git:sync-exclusions
```

**出力例**:
```
Syncing .git/info/exclude with plugin directories...

Current exclusions in .git/info/exclude:
  - DixlaseMenu
  - TestPlugin

Plugin directories in plugins/ folder:
  - DixlaseMenu
  - TestPlugin

✓ Already in sync. No changes needed.
```

## GitExcludeHelperクラス

### 主要メソッド

#### addPluginExclusion(string $pluginName): bool

プラグインの除外ルールを追加します。

```php
GitExcludeHelper::addPluginExclusion('DixlaseMenu');
```

**動作**:
- `.git/info/exclude`が存在しない場合は作成
- プラグインセクションが存在しない場合は作成
- 既に追加されている場合はスキップ
- プラグインセクション内に`!plugins/PluginName`を追加

#### removePluginExclusion(string $pluginName): bool

プラグインの除外ルールを削除します。

```php
GitExcludeHelper::removePluginExclusion('DixlaseMenu');
```

**動作**:
- `.git/info/exclude`から該当行を削除
- ファイルが存在しない場合は成功とみなす

#### syncPluginExclusions(array $installedPlugins): bool

インストール済みプラグインと`.git/info/exclude`を同期します。

```php
$plugins = Plugin::all()->pluck('directory')->toArray();
GitExcludeHelper::syncPluginExclusions($plugins);
```

**動作**:
- 既存のプラグインセクションを削除
- 新しいプラグインセクションを作成
- すべてのインストール済みプラグインを追加

#### hasPluginExclusion(string $pluginName): bool

プラグインの除外ルールが存在するか確認します。

```php
if (GitExcludeHelper::hasPluginExclusion('DixlaseMenu')) {
    // 除外ルールが存在する
}
```

## .git/info/excludeの構造

```
# Git exclude rules

# === Plugin exclusions (auto-managed) ===
# Do not edit this section manually
!plugins/DixlaseMenu
!plugins/DixlasePages
!plugins/DixlaseBlog
# === End plugin exclusions ===
# === Theme exclusions (auto-managed) ===
# Do not edit this section manually
!themes/DixlaseDefaultTheme
# === End theme exclusions ===
```

**重要**: 
- `=== Plugin exclusions (auto-managed) ===`セクション内は自動管理されるため、手動で編集しないでください
- `=== Theme exclusions (auto-managed) ===`セクション内も自動管理されるため、手動で編集しないでくださいます
- このセクション外であれば、手動で除外ルールを追加できます

## エラーハンドリング

### Gitリポジトリが存在しない場合

```php
GitExcludeHelper::addPluginExclusion('PluginName');
// → false を返し、ログに記録
// "Git repository not found. Skipping .git/info/exclude update."
```

### ファイルの書き込み権限がない場合

```php
GitExcludeHelper::addPluginExclusion('PluginName');
// → false を返し、エラーログに記録
// "Failed to add plugin exclusion: [エラーメッセージ]"
```

### 例外処理

すべてのメソッドは例外をキャッチし、ログに記録した上で`false`を返します。
これにより、Git除外ルールの更新失敗がプラグインのインストール/アンインストールを妨げることはありません。

## 利点

1. **コアのクリーンさ**: `.gitignore`を素の状態に保てる
2. **環境ごとの柔軟性**: 各開発環境で異なるプラグイン構成が可能
3. **自動化**: プラグインの追加/削除時に手動操作不要
4. **安全性**: 除外ルール更新の失敗がシステムに影響しない
5. **リリース時の手間削減**: コアリリース時にプラグイン設定を削除する必要なし

## トラブルシューティング

### 除外ルールが反映されない

```bash
# 現在の除外ルールを確認
cat .git/info/exclude

# 手動で同期
php artisan git:sync-exclusions

# Gitステータスを確認
git status
```

### プラグインが追跡されない

```bash
# 除外ルールを確認
cat .git/info/exclude | grep "plugins/PluginName"

# 手動で追加
echo "!plugins/PluginName" >> .git/info/exclude

# または同期コマンドを実行
php artisan git:sync-exclusions
```

### .git/info/excludeが破損した場合

```bash
# バックアップを作成
cp .git/info/exclude .git/info/exclude.backup

# 同期コマンドで再構築
php artisan git:sync-exclusions
```

## 関連ファイル

- **ヘルパークラス**: `app/Helpers/GitExcludeHelper.php`
- **同期コマンド**: `app/Console/Commands/SyncGitExclusions.php`
- **プラグインインストール**: `app/Console/Commands/PluginInstall.php`
- **プラグイン管理**: `app/Http/Controllers/Admin/Settings/AdminPluginsSettingsController.php`
- **ドキュメント**: `COMPOSER_LOCAL_SETUP.md`
