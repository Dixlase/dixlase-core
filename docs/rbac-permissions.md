# RBAC/権限モデル

## 概要

Dixlaseは役割ベースアクセス制御（RBAC）を採用しています。各メンバーにはロールが割り当てられ、ロールに応じた権限が付与されます。

## ロール階層

| ロール | 値 | 説明 |
|--------|-----|------|
| SUPER_ADMIN | 10 | 特権管理者（全権限） |
| ADMIN | 9 | 管理者 |
| EDITOR | 8 | 編集者 |
| AUTHOR | 7 | 投稿者 |
| CONTRIBUTOR | 6 | 寄稿者 |
| RECEPTIONIST | 5 | 受付 |
| GUEST | 1 | ゲスト |

ロールは階層的で、上位ロールは下位ロールの権限を全て持ちます。

## 権限一覧

### ダッシュボード
| 権限 | 最低ロール | 説明 |
|------|-----------|------|
| `dashboard.view` | GUEST | ダッシュボード閲覧 |

### メンバー管理
| 権限 | 最低ロール | 説明 |
|------|-----------|------|
| `members.view` | EDITOR | メンバー閲覧 |
| `members.create` | ADMIN | メンバー作成 |
| `members.update` | ADMIN | メンバー編集 |
| `members.delete` | SUPER_ADMIN | メンバー削除 |
| `members.manage_roles` | SUPER_ADMIN | 権限管理 |

### 設定
| 権限 | 最低ロール | 説明 |
|------|-----------|------|
| `settings.view` | EDITOR | 設定閲覧 |
| `settings.base` | ADMIN | 基本設定 |
| `settings.security` | SUPER_ADMIN | セキュリティ設定 |
| `settings.members` | ADMIN | メンバー設定 |
| `settings.system` | SUPER_ADMIN | システム設定 |
| `settings.api` | SUPER_ADMIN | API設定 |

### プラグイン
| 権限 | 最低ロール | 説明 |
|------|-----------|------|
| `plugins.view` | EDITOR | プラグイン閲覧 |
| `plugins.install` | SUPER_ADMIN | インストール |
| `plugins.uninstall` | SUPER_ADMIN | アンインストール |
| `plugins.enable` | ADMIN | 有効化 |
| `plugins.disable` | ADMIN | 無効化 |
| `plugins.settings` | ADMIN | 設定 |

### テーマ
| 権限 | 最低ロール | 説明 |
|------|-----------|------|
| `themes.view` | EDITOR | テーマ閲覧 |
| `themes.install` | SUPER_ADMIN | インストール |
| `themes.uninstall` | SUPER_ADMIN | アンインストール |
| `themes.enable` | ADMIN | 有効化 |
| `themes.disable` | ADMIN | 無効化 |
| `themes.settings` | ADMIN | 設定 |

### メディア
| 権限 | 最低ロール | 説明 |
|------|-----------|------|
| `media.view` | CONTRIBUTOR | メディア閲覧 |
| `media.upload` | AUTHOR | アップロード |
| `media.delete` | EDITOR | 削除 |

### システム
| 権限 | 最低ロール | 説明 |
|------|-----------|------|
| `system.logs_view` | EDITOR | ログ閲覧 |
| `system.logs_delete` | SUPER_ADMIN | ログ削除 |
| `system.cache_clear` | EDITOR | キャッシュクリア |
| `system.maintenance` | SUPER_ADMIN | メンテナンスモード |
| `system.backup` | SUPER_ADMIN | バックアップ |
| `system.restore` | SUPER_ADMIN | リストア |

## 使用方法

### コントローラーでの権限チェック

```php
use App\Enums\Permission;
use App\Services\PermissionService;

class MemberController extends Controller
{
    public function index()
    {
        // 権限チェック（失敗時は403）
        PermissionService::authorize(Permission::MEMBERS_VIEW);
        
        // または条件分岐
        if (PermissionService::can(Permission::MEMBERS_CREATE)) {
            // 作成ボタンを表示
        }
    }
}
```

### ミドルウェアでの権限チェック

```php
// routes/admin.php

// 単一の権限
Route::get('/members', [MemberController::class, 'index'])
    ->middleware('permission:members.view');

// 複数の権限（いずれか）
Route::post('/members', [MemberController::class, 'store'])
    ->middleware('permission:members.create,members.update');

// ロールチェック
Route::get('/settings/security', [SecurityController::class, 'index'])
    ->middleware('role:super_admin');
```

### Bladeテンプレートでの権限チェック

```blade
@if(auth()->user()->hasPermission(\App\Enums\Permission::MEMBERS_CREATE))
    <a href="{{ route('admin.members.create') }}">メンバー作成</a>
@endif

@if(auth()->user()->isSuperAdmin())
    <a href="{{ route('admin.settings.security') }}">セキュリティ設定</a>
@endif
```

### Memberモデルでの権限チェック

```php
$member = Member::find(1);

// 権限チェック
if ($member->hasPermission(Permission::MEMBERS_VIEW)) {
    // ...
}

// ロールチェック
if ($member->isAdmin()) {
    // ...
}

// 全権限を取得
$permissions = $member->getPermissions();
```

## 危険な権限

以下の権限は「危険な権限」としてマークされています：

- `members.delete` - メンバー削除
- `members.manage_roles` - 権限管理
- `plugins.install` / `plugins.uninstall`
- `themes.install` / `themes.uninstall`
- `system.backup` / `system.restore`
- `system.maintenance`
- `system.logs_delete`
- `api.keys_delete`
- `webhooks.delete`

これらの権限は監査ログに記録され、β版以降では強制再認証が必要になる予定です。

## メニュー権限との連携

> **Note:** 権限システムがリファクタリングされました。
> 詳細は `docs/role-permission-system.md` を参照してください。

新方式では `PermissionRegistry` サービスを使用してメニューごとの権限をチェックします。
デフォルト権限は `config/roles.php` で宣言し、管理画面で変更した場合のみ `role_permission_overrides` テーブルに保存されます。

```php
use App\Services\PermissionRegistry;

// メニュー権限の取得（デフォルト＋オーバーライド合成済み）
$effective = PermissionRegistry::getEffective('settings.security');

// アクセス可能かチェック
if (PermissionRegistry::canAccess('settings.security', $member->role)) {
    // ...
}
```

## ミドルウェア登録

`bootstrap/app.php`または`app/Http/Kernel.php`にミドルウェアを登録：

```php
// bootstrap/app.php (Laravel 11+)
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'permission' => \App\Http\Middleware\CheckPermission::class,
        'role' => \App\Http\Middleware\CheckRole::class,
    ]);
})
```

## β版以降の予定

- 権限のカスタマイズUI
- 危険な操作の強制再認証
- 権限変更の監査ログ
- プラグイン独自権限の登録API
