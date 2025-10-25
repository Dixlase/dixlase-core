# プラグイン・テーマ権限設定システム

## 概要

Dixlaseでは、プラグインとテーマごとに独自の権限設定を管理できます。プラグインやテーマをインストールすると、それぞれ専用の権限設定が自動的に`members_role_permissions`テーブルに追加されます。

## アーキテクチャ

### 権限管理の分離

- **コアシステム**: `database/seeders/MemberRolePermissionSeeder.php`
  - ダッシュボード、フロントページ、メディア、設定などのコア機能の権限を管理

- **プラグイン**: 各プラグインの`database/seeders/*RolePermissionSeeder.php`
  - プラグイン固有の機能の権限を管理
  - プラグインインストール時に自動実行

### インストールフロー

1. プラグインZIPをアップロード
2. プラグイン情報をデータベースに登録
3. マイグレーション実行
4. **シーダー実行** ← ここで権限設定が追加される
5. オートロード更新

## プラグイン開発者向けガイド

### 1. 権限シーダーの作成

プラグインディレクトリに権限シーダーを作成します：

```
plugins/YourPlugin/
└── database/
    └── seeders/
        ├── DatabaseSeeder.php
        └── YourPluginRolePermissionSeeder.php  ← 新規作成
```

#### サンプルコード

```php
<?php

namespace Plugins\YourPlugin\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class YourPluginRolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            [
                'menu_key' => 'your-plugin.index',
                'access_roles' => '9,8,7',  // ADMIN, EDITOR, AUTHOR
                'view_roles'  => '9,8,7',
            ],
            [
                'menu_key' => 'your-plugin.create',
                'access_roles' => '9,8',    // ADMIN, EDITOR
                'view_roles'  => '9,8',
            ],
            [
                'menu_key' => 'your-plugin.settings',
                'access_roles' => '9',      // ADMIN only
                'view_roles'  => '9',
            ],
        ];

        foreach ($permissions as $permission) {
            // 既存のレコードをチェック（重複防止）
            $exists = DB::table('members_role_permissions')
                ->where('menu_key', $permission['menu_key'])
                ->exists();

            if (!$exists) {
                DB::table('members_role_permissions')->insert([
                    'menu_key' => $permission['menu_key'],
                    'access_roles' => $permission['access_roles'],
                    'view_roles' => $permission['view_roles'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
```

### 2. DatabaseSeederに登録

プラグインの`DatabaseSeeder.php`に権限シーダーを追加します：

```php
<?php

namespace Plugins\YourPlugin\Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            YourPluginSettingsSeeder::class,
            YourPluginRolePermissionSeeder::class,  // ← 追加
        ]);
    }
}
```

### 3. 権限キーの命名規則

#### 推奨パターン

```php
// パターン1: プラグイン名をプレフィックスに
'plugin-name.index'
'plugin-name.create'
'plugin-name.settings'

// パターン2: 設定画面の場合
'settings.plugin-name'
'settings.plugin-name.advanced'
```

#### 実例

**DixlasePages**:
```php
'pages.index'      // ページ一覧
'pages.create'     // ページ作成
'pages.settings'   // ページ設定
```

**DixlaseInquiry**:
```php
'settings.inquiries'  // 問い合わせ設定
```

### 4. ロール（役割）の値

| ロール | 値 | 説明 |
|--------|-----|------|
| GUEST | 1 | ゲスト（閲覧のみ） |
| RECEPTIONIST | 5 | 受付担当者 |
| CONTRIBUTOR | 6 | 寄稿者 |
| AUTHOR | 7 | 著者 |
| EDITOR | 8 | 編集者 |
| ADMIN | 9 | 管理者 |

#### 権限設定例

```php
// 管理者のみ
'access_roles' => '9',
'view_roles'  => '9',

// 編集者以上
'access_roles' => '9,8',
'view_roles'  => '9,8',

// 著者以上
'access_roles' => '9,8,7',
'view_roles'  => '9,8,7',

// 寄稿者以上（編集可能）、受付担当者とゲスト（閲覧のみ）
'access_roles' => '9,8,7,6',
'view_roles'  => '5,1',
```

## 既存プラグインの実装例

### DixlasePages

**ファイル**: `plugins/DixlasePages/database/seeders/PagesRolePermissionSeeder.php`

```php
$permissions = [
    [
        'menu_key' => 'pages.index',
        'access_roles' => '9,8,7',  // ADMIN, EDITOR, AUTHOR
        'view_roles'  => '9,8,7',
    ],
    [
        'menu_key' => 'pages.create',
        'access_roles' => '9,8,7',
        'view_roles'  => '9,8,7',
    ],
    [
        'menu_key' => 'pages.settings',
        'access_roles' => '9',      // ADMIN only
        'view_roles'  => '9',
    ],
];
```

### DixlaseInquiry

**ファイル**: `plugins/DixlaseInquiry/database/seeders/InquiryRolePermissionSeeder.php`

```php
$permissions = [
    [
        'menu_key' => 'settings.inquiries',
        'access_roles' => '9',      // ADMIN only
        'view_roles'  => '9',
    ],
];
```

## テーマ開発者向けガイド

### 1. テーマ権限シーダーの作成

テーマに設定画面がある場合、権限シーダーを作成します：

```
themes/YourTheme/
└── database/
    └── seeders/
        ├── DatabaseSeeder.php
        └── ThemeRolePermissionSeeder.php  ← 新規作成
```

#### サンプルコード

```php
<?php

namespace Themes\YourTheme\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ThemeRolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // テーマ設定画面の権限のみを追加
        // テーマ一覧とインストールはコア側で管理
        $permissions = [
            [
                'menu_key' => 'settings.themes.settings',
                'access_roles' => '9',      // ADMIN only
                'view_roles'  => '9',
            ],
        ];

        foreach ($permissions as $permission) {
            $exists = DB::table('members_role_permissions')
                ->where('menu_key', $permission['menu_key'])
                ->exists();

            if (!$exists) {
                DB::table('members_role_permissions')->insert([
                    'menu_key' => $permission['menu_key'],
                    'access_roles' => $permission['access_roles'],
                    'view_roles' => $permission['view_roles'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
```

### 2. DatabaseSeederに登録

```php
<?php

namespace Themes\YourTheme\Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ThemeSettingsSeeder::class,
            ThemeRolePermissionSeeder::class,  // ← 追加
        ]);
    }
}
```

### 3. 権限管理の分離

**コア側で管理**:
- `settings.themes.index` - テーマ一覧
- `settings.themes.install` - テーマインストール

**テーマ側で管理**:
- `settings.themes.settings` - テーマ設定画面

### 4. 実装例: DixlaseDefaultTheme

**ファイル**: `themes/DixlaseDefaultTheme/database/seeders/ThemeRolePermissionSeeder.php`

```php
$permissions = [
    [
        'menu_key' => 'settings.themes.settings',
        'access_roles' => '9',
        'view_roles'  => '9',
    ],
];
```

### 5. インストールフロー

1. テーマZIPをアップロード
2. テーマ情報をデータベースに登録
3. **シーダー実行** ← ここで権限設定が追加される
4. 完了

## プラグインアンインストール時の処理

プラグインをアンインストールする際は、権限設定も削除する必要があります。

### アンインストールシーダーの作成（オプション）

```php
<?php

namespace Plugins\YourPlugin\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class YourPluginRolePermissionUninstallSeeder extends Seeder
{
    public function run(): void
    {
        // プラグイン関連の権限を削除
        DB::table('members_role_permissions')
            ->whereIn('menu_key', [
                'your-plugin.index',
                'your-plugin.create',
                'your-plugin.settings',
            ])
            ->delete();
    }
}
```

## トラブルシューティング

### 権限が追加されない

1. **シーダーが実行されているか確認**
   ```bash
   php artisan plugin:seed YourPluginDirectory --force
   ```

2. **DatabaseSeederに登録されているか確認**
   - `DatabaseSeeder.php`の`call()`配列に権限シーダーが含まれているか

3. **重複チェックが機能しているか確認**
   - `exists()`クエリで既存レコードをチェックしているか

### 権限が反映されない

1. **キャッシュをクリア**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

2. **データベースを確認**
   ```sql
   SELECT * FROM members_role_permissions WHERE menu_key LIKE 'your-plugin%';
   ```

3. **ミドルウェアを確認**
   - ルートに`check.menu.access`ミドルウェアが適用されているか

## ベストプラクティス

### ✅ 推奨

1. **重複チェックを必ず実装**
   - プラグイン再インストール時のエラーを防ぐ

2. **created_atとupdated_atを設定**
   - 権限追加日時を記録

3. **適切な権限レベルを設定**
   - 必要最小限の権限を付与

4. **命名規則を統一**
   - プラグイン名をプレフィックスに使用

### ❌ 避けるべき

1. **コアシステムの権限キーを使用**
   - `dashboard`, `settings.base`などは使用しない

2. **すべての機能に管理者権限を要求**
   - 適切な権限レベルを設定する

3. **重複チェックなしで挿入**
   - 再インストール時にエラーが発生

## まとめ

- ✅ プラグインごとに権限シーダーを作成
- ✅ DatabaseSeederに登録
- ✅ 重複チェックを実装
- ✅ 適切な権限レベルを設定
- ✅ インストール時に自動実行される
- ✅ アンインストール時の削除も考慮
