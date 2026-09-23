# データベースクリーンアップ機能

## 概要

Dixlaseのデータベースクリーンアップ機能は、古いログやセッションデータなど不要になったデータベースレコードを定期的に削除するための機能です。管理画面から手動で実行でき、プラグインやテーマも独自のクリーンアップ対象を追加できます。

## 主な機能

- **コアテーブルのクリーンアップ**: 管理者ログイン試行履歴、セッション、キャッシュなど
- **プラグイン/テーマテーブルのクリーンアップ**: 各拡張機能が独自のテーブルをクリーンアップ対象に追加可能
- **柔軟な保持期間設定**: テーブルごとに保持日数を設定可能
- **条件付きクリーンアップ**: 期限切れ、使用済み、未使用などの条件を指定可能
- **多言語対応**: 日本語・英語で説明文を表示

## アクセス方法

管理画面 > 設定 > システム設定 > データベース管理

## クリーンアップ対象テーブル（コア）

### 1. ログイン試行履歴 (login_attempts)
- **テーブル**: `members_login_attempts`
- **対象カラム**: `created_at`
- **デフォルト保持期間**: 30日
- **説明**: 管理者のログイン試行記録をクリーンアップします

### 2. パスワードリセットトークン (password_reset_tokens)
- **テーブル**: `members_password_reset_tokens`
- **対象カラム**: `created_at`
- **デフォルト保持期間**: 30日
- **説明**: 古いパスワードリセットトークン記録をクリーンアップします

### 3. 二段階認証試行履歴 (two_fa_attempts)
- **テーブル**: `members_two_fa_attempts`
- **対象カラム**: `created_at`
- **デフォルト保持期間**: 30日
- **説明**: 二段階認証の試行履歴をクリーンアップします

### 4. 二段階認証トークン (two_fa_tokens)
- **テーブル**: `members_two_fa_tokens`
- **対象カラム**: `created_at`
- **デフォルト保持期間**: 7日
- **追加条件**: `expired` (期限切れのトークンのみ)
- **説明**: 期限切れの二段階認証トークンをクリーンアップします

### 5. 二段階認証回復コード (recovery_codes)
- **テーブル**: `members_two_fa_recovery_codes`
- **対象カラム**: `created_at`
- **デフォルト保持期間**: 90日
- **追加条件**: `used` (使用済みのコードのみ)
- **説明**: 使用済み・無効化された二段階認証回復コードをクリーンアップします

### 6. 二段階認証用PASSKEY (passkeys)
- **テーブル**: `members_passkeys`
- **対象カラム**: `last_used_at`（一度も使われていないパスキーは削除しない）
- **デフォルト保持期間**: 365日
- **説明**: 古い二段階認証用PASSKEY（生体認証）をクリーンアップします

### 7. セッション (sessions)
- **テーブル**: `sessions`
- **対象カラム**: `last_activity`
- **カラムタイプ**: `timestamp`
- **デフォルト保持期間**: 7日
- **説明**: 古いセッション記録をクリーンアップします

### 8. キャッシュデータ (cache_data)
- **テーブル**: `cache`
- **対象カラム**: `expiration`
- **カラムタイプ**: `timestamp`
- **デフォルト保持期間**: なし（すべて削除）
- **追加条件**: `expired_cache` (期限切れのキャッシュのみ)
- **説明**: 期限切れのキャッシュデータをクリーンアップします

## プラグイン/テーマでクリーンアップ対象を追加する方法

### 1. 設定ファイルの作成

プラグインまたはテーマのディレクトリに `config/database-cleanup.php` ファイルを作成します。

**ファイルパス例:**
```
plugins/YourPlugin/config/database-cleanup.php
themes/YourTheme/config/database-cleanup.php
```

**設定ファイルの例:**
```php
<?php

return [
    'your_table_key' => [
        'table' => 'plg_your_plugin_table_name',  // テーブル名
        'date_column' => 'created_at',             // 日付カラム名
        'default_days' => 30,                      // デフォルト保持日数
        'name' => 'your-plugin::admin/settings/systems/database.your_table_key.name',
        'description' => 'your-plugin::admin/settings/systems/database.your_table_key.description',
        'enabled' => true,                         // 有効/無効
        'date_column_type' => 'datetime',          // オプション: 'datetime' or 'timestamp'
        'additional_conditions' => 'expired',      // オプション: 追加条件
    ],
    
    // 複数のテーブルを定義可能
    'another_table' => [
        'table' => 'plg_your_plugin_another_table',
        'date_column' => 'updated_at',
        'default_days' => 60,
        'name' => 'your-plugin::admin/settings/systems/database.another_table.name',
        'description' => 'your-plugin::admin/settings/systems/database.another_table.description',
        'enabled' => true,
    ],
];
```

### 2. 翻訳ファイルの作成

コアと同じディレクトリ構造で翻訳ファイルを作成します。

**日本語翻訳ファイル:**
```
plugins/YourPlugin/lang/ja/admin/settings/systems/database.php
```

**英語翻訳ファイル:**
```
plugins/YourPlugin/lang/en/admin/settings/systems/database.php
```

**翻訳ファイルの例:**
```php
<?php

return [
    'your_table_key' => [
        'name' => 'あなたのテーブル名',
        'description' => 'あなたのテーブルの説明文',
    ],
    'another_table' => [
        'name' => '別のテーブル名',
        'description' => '別のテーブルの説明文',
    ],
];
```

### 3. 設定パラメータの詳細

#### 必須パラメータ

| パラメータ | 型 | 説明 |
|----------|-----|------|
| `table` | string | クリーンアップ対象のテーブル名 |
| `date_column` | string | 日付を判定するカラム名 |
| `default_days` | int\|null | デフォルト保持日数（nullの場合はすべて削除） |
| `name` | string | 翻訳キー（テーブル名の表示用） |
| `description` | string | 翻訳キー（説明文の表示用） |
| `enabled` | bool | クリーンアップ機能の有効/無効 |

#### オプションパラメータ

| パラメータ | 型 | 説明 | デフォルト値 |
|----------|-----|------|------------|
| `date_column_type` | string | 日付カラムの型（'datetime' or 'timestamp'） | 'datetime' |
| `additional_conditions` | string | 追加の削除条件 | null |

#### additional_conditions で使用可能な値

| 値 | 説明 |
|----|------|
| `expired` | `expires_at < now()` の条件を追加 |
| `used` | `used_at IS NOT NULL` の条件を追加 |
| `unused` | `last_used_at IS NULL` の条件を追加 |
| `expired_cache` | `expiration < now()` の条件を追加（キャッシュ用） |

### 4. 翻訳キーの命名規則

プラグイン/テーマの翻訳キーは以下の形式で指定します:

```
{plugin-slug}::admin/settings/systems/database.{table_key}.{field}
```

**例:**
```php
'name' => 'dixlase-users::admin/settings/systems/database.login_attempts.name',
'description' => 'dixlase-users::admin/settings/systems/database.login_attempts.description',
```

**プラグインスラッグの確認方法:**
- プラグインディレクトリ名をケバブケース（小文字+ハイフン）に変換
- 例: `DixlaseUsers` → `dixlase-users`

## 実装例: DixlaseUsersプラグイン

### 設定ファイル
`plugins/DixlaseUsers/config/database-cleanup.php`

```php
<?php

return [
    'login_attempts' => [
        'table' => 'plg_dixlase_users_login_attempts',
        'date_column' => 'created_at',
        'default_days' => 30,
        'name' => 'dixlase-users::admin/settings/systems/database.login_attempts.name',
        'description' => 'dixlase-users::admin/settings/systems/database.login_attempts.description',
        'enabled' => true,
    ],

    'sessions' => [
        'table' => 'plg_dixlase_users_sessions',
        'date_column' => 'last_activity',
        'default_days' => 7,
        'name' => 'dixlase-users::admin/settings/systems/database.sessions.name',
        'description' => 'dixlase-users::admin/settings/systems/database.sessions.description',
        'enabled' => true,
        'date_column_type' => 'timestamp',
    ],

    'trusted_devices' => [
        'table' => 'plg_dixlase_users_trusted_devices',
        'date_column' => 'last_used_at',
        'default_days' => 90,
        'name' => 'dixlase-users::admin/settings/systems/database.trusted_devices.name',
        'description' => 'dixlase-users::admin/settings/systems/database.trusted_devices.description',
        'enabled' => true,
        'additional_conditions' => 'unused',
    ],
];
```

### 翻訳ファイル（日本語）
`plugins/DixlaseUsers/lang/ja/admin/settings/systems/database.php`

```php
<?php

return [
    'login_attempts' => [
        'name' => 'ユーザーログイン試行履歴',
        'description' => '古いユーザーログイン試行記録をクリーンアップします',
    ],
    'sessions' => [
        'name' => 'ユーザーセッション',
        'description' => '古いユーザーセッション記録をクリーンアップします',
    ],
    'trusted_devices' => [
        'name' => 'ユーザー信頼済みデバイス',
        'description' => '古いユーザー信頼済みデバイス記録をクリーンアップします',
    ],
];
```

## 使用方法

### 管理画面からのクリーンアップ

1. 管理画面 > 設定 > システム設定 > データベース管理 にアクセス
2. クリーンアップしたいテーブルを選択
3. 保持日数を入力（デフォルト値が設定されている場合は自動入力）
4. 「クリーンアップ」ボタンをクリック
5. 確認ダイアログで「OK」をクリック

### 保持日数の設定

- **0日**: すべてのレコードを削除
- **1日以上**: 指定日数より古いレコードを削除
- **null（設定なし）**: すべてのレコードを削除（キャッシュデータなど）

## 注意事項

1. **バックアップの推奨**: クリーンアップ実行前にデータベースのバックアップを推奨します
2. **削除は取り消せません**: 一度削除したデータは復元できません
3. **追加条件の確認**: `additional_conditions` を使用する場合、対象カラムがテーブルに存在することを確認してください
4. **テーブル名のプレフィックス**: プラグインのテーブルには `plg_` プレフィックスを付けることを推奨します
5. **翻訳ファイルの配置**: コアと同じディレクトリ構造（`lang/{locale}/admin/settings/systems/database.php`）を使用してください

## トラブルシューティング

### プラグインのクリーンアップ項目が表示されない

1. `config/database-cleanup.php` が正しい場所に配置されているか確認
2. 設定ファイルの構文エラーがないか確認
3. `enabled` が `true` に設定されているか確認

### 翻訳が表示されない

1. 翻訳ファイルが正しいディレクトリ構造で配置されているか確認
2. 翻訳キーのプレフィックスが正しいか確認（プラグインスラッグ）
3. 翻訳ファイルの構文エラーがないか確認

### クリーンアップが実行されない

1. テーブル名が正しいか確認
2. `date_column` が実際のテーブルに存在するか確認
3. `additional_conditions` で指定したカラムが存在するか確認
4. データベース接続が正常か確認

## 関連ファイル

- **コア設定**: `config/admin/database-cleanup.php`
- **サービス**: `app/Services/DatabaseCleanupService.php`
- **リクエスト**: `app/Http/Requests/Admin/Settings/Systems/AdminSystemDatabaseCleanupRequest.php`
- **ビュー**: `resources/views/admin/settings/systems/database.blade.php`
- **翻訳（日本語）**: `lang/ja/admin/settings/systems/database.php`
- **翻訳（英語）**: `lang/en/admin/settings/systems/database.php`

## 開発者向け情報

### DatabaseCleanupService

クリーンアップ処理を担当するサービスクラス。

**主要メソッド:**
- `getPluginCleanupInfo()`: プラグインのクリーンアップ設定を取得
- `cleanup($type, $days)`: 指定されたタイプのクリーンアップを実行
- `applyAdditionalCondition($query, $condition)`: 追加条件を適用

### バリデーション

`AdminSystemDatabaseCleanupRequest` クラスで、コアとプラグインのクリーンアップタイプを動的に検証します。

```php
// コアのクリーンアップタイプ
$coreTypes = array_keys(config('admin.database-cleanup', []));

// プラグインのクリーンアップタイプ
$pluginTypes = array_keys($pluginCleanupInfo);

// すべてのタイプを結合
$allTypes = array_merge($coreTypes, $pluginTypes, ['all']);
```

## まとめ

データベースクリーンアップ機能は、Dixlaseシステムのパフォーマンスを維持するための重要な機能です。プラグインやテーマ開発者は、この機能を活用して独自のテーブルのクリーンアップを簡単に実装できます。
