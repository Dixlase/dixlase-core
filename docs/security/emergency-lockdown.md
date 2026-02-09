# 緊急ロックダウン機能

## 概要

緊急ロックダウン機能は、セキュリティインシデント発生時に即座にシステムを保護するための機能です。不正アクセスの検知、ブルートフォース攻撃、その他のセキュリティ脅威に対して、迅速に対応できます。

## ロックダウンタイプ

| タイプ | 説明 | 影響範囲 |
|--------|------|----------|
| `full` | 完全ロックダウン | 全アクセス遮断（SUPER_ADMIN以外） |
| `admin` | 管理画面ロックダウン | 管理画面のみアクセス不可 |
| `api` | APIロックダウン | APIエンドポイントのみアクセス不可 |
| `login` | ログインロックダウン | 新規ログインのみ不可 |

## コマンド

### ロックダウン発動

```bash
# 完全ロックダウン（確認あり）
php artisan lockdown activate

# タイプを指定
php artisan lockdown activate --type=admin

# 理由を指定
php artisan lockdown activate --reason="不正アクセス検知"

# 自動解除時間を設定（30分後に自動解除）
php artisan lockdown activate --duration=30

# 特定のIPを許可
php artisan lockdown activate --allow-ip=192.168.1.1 --allow-ip=10.0.0.1

# 特定のメンバーを許可
php artisan lockdown activate --allow-member=1 --allow-member=2

# 確認なしで実行
php artisan lockdown activate --force
```

### ロックダウン解除

```bash
# 解除（確認あり）
php artisan lockdown deactivate

# 確認なしで解除
php artisan lockdown deactivate --force

# 理由を指定して解除
php artisan lockdown deactivate --reason="脅威が解消されたため"
```

### 状態確認

```bash
# 現在の状態を表示
php artisan lockdown status

# 履歴を表示
php artisan lockdown history
```

## プログラムからの使用

### ロックダウン発動

```php
use App\Services\LockdownService;
use App\Models\LockdownStatus;

// 完全ロックダウン
LockdownService::activate(
    type: LockdownStatus::TYPE_FULL,
    reason: '不正アクセス検知',
    triggeredBy: auth()->id(),
    autoReleaseMinutes: 60,
    allowedIps: ['192.168.1.1'],
    allowedMembers: [1, 2]
);

// 管理画面のみロック
LockdownService::activate(
    type: LockdownStatus::TYPE_ADMIN,
    reason: 'メンテナンス作業'
);
```

### ロックダウン解除

```php
LockdownService::deactivate(
    releasedBy: auth()->id(),
    reason: '脅威が解消されたため'
);
```

### 状態確認

```php
// ロックダウン中かどうか
if (LockdownService::isLocked()) {
    // ロックダウン中
}

// 特定タイプがロックされているか
if (LockdownService::isLocked(LockdownStatus::TYPE_API)) {
    // APIがロックされている
}

// アクセスが許可されているか
$member = auth()->user();
$ip = request()->ip();

if (LockdownService::isAccessAllowed($ip, $member)) {
    // アクセス許可
}
```

### ロックダウン延長

```php
// 30分延長
LockdownService::extend(30, auth()->id());
```

### 許可リスト更新

```php
LockdownService::updateAllowList(
    allowedIps: ['192.168.1.1', '10.0.0.1'],
    allowedMembers: [1, 2, 3],
    performedBy: auth()->id()
);
```

## ミドルウェア

### 基本的な使用

```php
// routes/admin.php

// 全タイプのロックダウンをチェック
Route::middleware('lockdown')->group(function () {
    // ...
});

// 特定タイプのみチェック
Route::middleware('lockdown:admin')->group(function () {
    // 管理画面ルート
});

Route::middleware('lockdown:api')->group(function () {
    // APIルート
});

Route::middleware('lockdown:login')->group(function () {
    // ログインルート
});
```

### ミドルウェア登録

```php
// bootstrap/app.php (Laravel 11+)
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'lockdown' => \App\Http\Middleware\CheckLockdown::class,
    ]);
})
```

## アクセス許可の優先順位

1. **SUPER_ADMIN**: 常にアクセス可能
2. **許可されたIP**: `allowed_ips`に含まれるIP
3. **許可されたメンバー**: `allowed_members`に含まれるメンバーID
4. **その他**: アクセス拒否

## 自動解除

`autoReleaseMinutes`を設定すると、指定時間後に自動的にロックダウンが解除されます。

```php
// 60分後に自動解除
LockdownService::activate(
    type: LockdownStatus::TYPE_LOGIN,
    reason: 'ブルートフォース攻撃検知',
    autoReleaseMinutes: 60
);
```

自動解除は、ミドルウェアがリクエストを処理する際にチェックされます。

## 監査ログ

ロックダウンの発動・解除は自動的に監査ログに記録されます：

- `lockdown_activated`: ロックダウン発動
- `lockdown_deactivated`: ロックダウン解除
- `lockdown_auto_released`: 自動解除

## データベーステーブル

### lockdown_status

現在のロックダウン状態を保持

| カラム | 説明 |
|--------|------|
| `type` | ロックダウンタイプ |
| `is_active` | アクティブかどうか |
| `reason` | 理由 |
| `triggered_by` | 発動者ID |
| `triggered_at` | 発動日時 |
| `released_by` | 解除者ID |
| `released_at` | 解除日時 |
| `auto_release_at` | 自動解除日時 |
| `allowed_ips` | 許可されたIP（JSON） |
| `allowed_members` | 許可されたメンバー（JSON） |

### lockdown_history

ロックダウンの履歴を記録

| カラム | 説明 |
|--------|------|
| `action` | アクション（activated/deactivated/extended/modified） |
| `type` | ロックダウンタイプ |
| `reason` | 理由 |
| `performed_by` | 実行者ID |
| `ip_address` | IPアドレス |
| `details` | 詳細情報（JSON） |
| `performed_at` | 実行日時 |

## β版以降の予定

- 管理画面でのロックダウン管理UI
- 自動トリガー設定（ログイン失敗回数、不審なアクティビティ等）
- Slack/メール通知連携
- ロックダウン中のアクセス試行ログ
- 地理的ロックダウン（国/地域ベース）
