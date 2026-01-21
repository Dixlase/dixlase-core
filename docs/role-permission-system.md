# Dixlase 権限設定システム

## 概要

Dixlaseの権限設定システムは「**宣言（default）と保存（override）を分離**」する設計を採用しています。

- **デフォルト権限**: `config/roles.php`（コア）または `plugins/{slug}/config/roles.php`（プラグイン）で宣言
- **オーバーライド**: 管理画面で変更された場合のみ `role_permission_overrides` テーブルに差分を保存
- **実効権限**: 実行時にデフォルトとオーバーライドを合成して計算

## メリット

1. **プラグインがDBを書き換えない**: インストール時にコアDBへのレコード追加が不要
2. **アンインストールが簡単**: プラグイン削除時の権限レコード削除処理が最小限
3. **サンドボックス思想との整合**: 「要・明示的権限」判定のハードルが下がる
4. **デフォルトに戻す機能**: オーバーライドを削除するだけで実現

## アーキテクチャ

```
┌─────────────────────────────────────────────────────────────┐
│                    管理画面 (UI)                              │
│  ┌─────────────────────────────────────────────────────────┐ │
│  │  権限設定画面 (roles.blade.php)                          │ │
│  │  - デフォルト値を表示                                    │ │
│  │  - 変更時のみオーバーライドとして保存                    │ │
│  │  - 「デフォルトに戻す」= オーバーライド削除              │ │
│  └─────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│              PermissionRegistry (サービス)                    │
│  ┌─────────────────────────────────────────────────────────┐ │
│  │  getEffective($menuKey)                                  │ │
│  │  - config/roles.php からデフォルト取得                   │ │
│  │  - role_permission_overrides からオーバーライド取得      │ │
│  │  - 合成して実効権限を返す                                │ │
│  └─────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
          │                                    │
          ▼                                    ▼
┌─────────────────────┐          ┌─────────────────────────────┐
│  config/roles.php   │          │  role_permission_overrides  │
│  (デフォルト宣言)    │          │  (差分のみ保存)              │
│                     │          │                             │
│  - コア機能         │          │  - source_type: core/plugin │
│  - 変更不可         │          │  - source_id: plugin slug   │
│                     │          │  - menu_key                 │
│                     │          │  - access_roles             │
│                     │          │  - view_roles               │
└─────────────────────┘          └─────────────────────────────┘
```

## 権限の種類

| 権限 | 説明 | 用途 |
|------|------|------|
| `access_roles` | 編集権限（write） | 作成・更新・削除などの操作 |
| `view_roles` | 閲覧権限（read） | メニュー表示・一覧閲覧 |

## 権限値（MemberRole）

| 値 | 定数 | 説明 |
|----|------|------|
| 10 | SUPER_ADMIN | 特権管理者専用 |
| 9 | ADMIN | 管理者以上 |
| 8 | EDITOR | 編集者以上 |
| 7 | AUTHOR | 投稿者以上 |
| 6 | CONTRIBUTOR | 寄稿者以上 |
| 5 | RECEPTIONIST | 受付以上 |
| 1 | GUEST | 全員 |

## 使用方法

### コア機能のデフォルト権限定義

`config/roles.php`:

```php
<?php

use App\Enums\MemberRole;

return [
    'permissions' => [
        'dashboard' => [
            'access_roles' => MemberRole::CONTRIBUTOR->value,
            'view_roles' => MemberRole::GUEST->value,
        ],
        'settings.base.index' => [
            'access_roles' => MemberRole::SUPER_ADMIN->value,
            'view_roles' => MemberRole::SUPER_ADMIN->value,
        ],
        // ...
    ],
];
```

### プラグインのデフォルト権限定義

`plugins/{slug}/config/roles.php`:

```php
<?php

use App\Enums\MemberRole;

return [
    'permissions' => [
        'settings.inquiry.index' => [
            'access_roles' => MemberRole::EDITOR->value,
            'view_roles' => MemberRole::EDITOR->value,
        ],
        'settings.inquiry.settings' => [
            'access_roles' => MemberRole::ADMIN->value,
            'view_roles' => MemberRole::ADMIN->value,
        ],
    ],
];
```

### 権限チェック（コントローラー）

```php
use App\Services\PermissionRegistry;
use App\Enums\MemberRole;

// 実効権限を取得
$effective = PermissionRegistry::getEffective('settings.base.index');
// => ['access_roles' => 10, 'view_roles' => 10, 'is_overridden' => false, ...]

// アクセス可能かチェック
$canAccess = PermissionRegistry::canAccess('settings.base.index', $user->role);

// 閲覧可能かチェック
$canView = PermissionRegistry::canView('settings.base.index', $user->role);

// プラグイン機能の権限チェック
$canAccessPlugin = PermissionRegistry::canAccessPlugin('DixlaseInquiry', 'settings.inquiry.index', $user->role);
```

### 権限チェック（AdminHelper経由）

```php
use App\Helpers\AdminHelper;

// メニューへのアクセス権限
if (AdminHelper::canAccessMenu('settings.base.index')) {
    // アクセス可能
}

// メニューの閲覧権限
if (AdminHelper::canViewMenu('settings.base.index')) {
    // 閲覧可能
}

// メニューの編集権限
if (AdminHelper::canEditMenu('settings.base.index')) {
    // 編集可能
}

// プラグインメニューの権限
if (AdminHelper::canAccessPluginMenu('DixlaseInquiry', 'settings.inquiry.index')) {
    // アクセス可能
}
```

### オーバーライドの操作

```php
use App\Models\RolePermissionOverride;
use App\Services\PermissionRegistry;

// コア機能のオーバーライドを設定
RolePermissionOverride::setCoreOverride(
    'settings.base.index',
    MemberRole::ADMIN->value,  // access_roles
    MemberRole::ADMIN->value,  // view_roles
    auth()->id()               // updated_by
);

// プラグイン機能のオーバーライドを設定
RolePermissionOverride::setPluginOverride(
    'DixlaseInquiry',
    'settings.inquiry.index',
    MemberRole::EDITOR->value,
    MemberRole::EDITOR->value,
    auth()->id()
);

// オーバーライドを削除（デフォルトに戻す）
RolePermissionOverride::resetCoreOverride('settings.base.index');
RolePermissionOverride::resetPluginOverride('DixlaseInquiry', 'settings.inquiry.index');

// キャッシュをクリア
PermissionRegistry::clearCache();
```

## プラグイン開発者向けガイド

### 1. config/roles.php を作成

プラグインディレクトリに `config/roles.php` を作成し、デフォルト権限を定義します。

```php
<?php

use App\Enums\MemberRole;

return [
    'permissions' => [
        // メニューキーは config/admin.php の nav 構造に対応
        'settings.myplugin.index' => [
            'access_roles' => MemberRole::EDITOR->value,
            'view_roles' => MemberRole::EDITOR->value,
        ],
    ],
];
```

### 2. メニューキーの命名規則

- プラグインのメニューキーは `config/admin.php` の `nav` 構造に対応
- 例: `settings.{pluginSlug}.index`, `settings.{pluginSlug}.create`

### 3. ServiceProviderでの登録（オプション）

動的に権限を登録する場合は、ServiceProviderで `PermissionRegistry::registerPlugin()` を使用できます。

```php
use App\Services\PermissionRegistry;

public function boot(): void
{
    PermissionRegistry::registerPlugin('MyPlugin', [
        'settings.myplugin.index' => [
            'access_roles' => MemberRole::EDITOR->value,
            'view_roles' => MemberRole::EDITOR->value,
        ],
    ]);
}
```

### 4. サンドボックス思想との整合

この方式では、プラグインは**デフォルト権限を宣言するだけ**でDBを書き換えません。
そのため、サンドボックスの「要・明示的権限」判定において、権限設定機能を持つことが
心理的ハードルにならなくなります。

## データベーステーブル

### role_permission_overrides

| カラム | 型 | 説明 |
|--------|-----|------|
| id | bigint | 主キー |
| source_type | varchar(20) | `core` または `plugin` |
| source_id | varchar(100) | coreはnull、pluginはslug |
| menu_key | varchar(255) | メニューキー |
| access_roles | tinyint | 編集権限 |
| view_roles | tinyint | 閲覧権限 |
| updated_by | bigint | 更新者のmember_id |
| created_at | timestamp | 作成日時 |
| updated_at | timestamp | 更新日時 |

**ユニーク制約**: `source_type` + `source_id` + `menu_key`

## 孤児オーバーライドの管理

プラグインをアンインストールした後、オーバーライドが残る場合があります。

```php
// 孤児オーバーライドを検出
$activePlugins = ['DixlaseInquiry', 'DixlasePages'];
$orphans = RolePermissionOverride::findOrphanOverrides($activePlugins);

// 孤児オーバーライドを削除
$deletedCount = RolePermissionOverride::deleteOrphanOverrides($activePlugins);
```

## 関連ドキュメント

- [RBAC/権限モデル](./rbac-permissions.md) - ロール階層、Permission Enum、権限チェックの使い方

## 関連ソースファイル

- `config/roles.php` - コアのデフォルト権限定義
- `app/Models/RolePermissionOverride.php` - オーバーライドモデル
- `app/Services/PermissionRegistry.php` - 権限レジストリサービス
- `app/Services/PermissionService.php` - 権限サービス
- `app/Helpers/AdminHelper.php` - 管理画面ヘルパー
- `app/Http/Controllers/Admin/Members/AdminMemberRolesController.php` - 権限設定コントローラー
- `resources/views/admin/members/settings/roles.blade.php` - 権限設定画面
