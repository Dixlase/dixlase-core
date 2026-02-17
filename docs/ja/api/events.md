# Dixlase Events API 仕様

## 概要

このドキュメントはDixlaseのEvents APIを定義します。イベントはプラグインがコアのコードベースを変更せずにコア機能にフックするための拡張ポイントを提供します。

## バージョン

- **仕様バージョン**: 1.0
- **ステータス**: 安定版

## 1. イベントシステムのアーキテクチャ

### 1.1 設計思想

- **疎結合**: コアがイベントを発火し、プラグインがリッスンする
- **予測可能なペイロード**: 各イベントにはドキュメント化されたペイロード構造がある
- **ライフサイクルカバレッジ**: before/after/failed 状態のイベントを提供
- **名前空間規約**: `dixlase.{category}.{action}`

### 1.2 イベントフロー

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   Core Action   │ ──▶ │   Fire Event    │ ──▶ │ Plugin Listener │
│  (e.g. backup)  │     │ (with payload)  │     │ (handle event)  │
└─────────────────┘     └─────────────────┘     └─────────────────┘
```

## 2. イベントカテゴリ

### 2.1 バックアップイベント

| イベント | 定数 | ペイロード |
|---------|------|-----------|
| バックアップ開始 | `BACKUP_STARTED` | `['type' => string, 'options' => array]` |
| バックアップ完了 | `BACKUP_COMPLETED` | `['type' => string, 'path' => string, 'size' => int, 'duration' => float]` |
| バックアップ失敗 | `BACKUP_FAILED` | `['type' => string, 'error' => string, 'exception' => Throwable\|null]` |
| クリーンアップ開始 | `BACKUP_CLEANUP_STARTED` | `['files' => array, 'retention_days' => int]` |
| クリーンアップ完了 | `BACKUP_CLEANUP_COMPLETED` | `['deleted_count' => int, 'freed_bytes' => int]` |
| リストア開始 | `BACKUP_RESTORE_STARTED` | `['path' => string, 'type' => string]` |
| リストア完了 | `BACKUP_RESTORE_COMPLETED` | `['path' => string, 'type' => string, 'duration' => float]` |
| リストア失敗 | `BACKUP_RESTORE_FAILED` | `['path' => string, 'error' => string]` |

### 2.2 デプロイイベント

| イベント | 定数 | ペイロード |
|---------|------|-----------|
| デプロイ前 | `DEPLOY_BEFORE` | `['environment' => string, 'targets' => array, 'options' => array]` |
| デプロイ後 | `DEPLOY_AFTER` | `['environment' => string, 'targets' => array, 'duration' => float]` |
| デプロイ失敗 | `DEPLOY_FAILED` | `['environment' => string, 'error' => string, 'exception' => Throwable\|null]` |
| 同期前 | `DEPLOY_SYNC_BEFORE` | `['environment' => string, 'target' => string, 'files' => array]` |
| 同期後 | `DEPLOY_SYNC_AFTER` | `['environment' => string, 'target' => string, 'synced_count' => int]` |
| データベース前 | `DEPLOY_DATABASE_BEFORE` | `['environment' => string, 'tables' => array]` |
| データベース後 | `DEPLOY_DATABASE_AFTER` | `['environment' => string, 'tables' => array, 'rows_affected' => int]` |

### 2.3 翻訳イベント

| イベント | 定数 | ペイロード |
|---------|------|-----------|
| ロケール変更 | `LOCALE_CHANGED` | `['locale' => string, 'previous' => string]` |
| モデル取得 | `TRANSLATION_MODEL_RETRIEVED` | `[Model $model]` |
| モデル保存中 | `TRANSLATION_MODEL_SAVING` | `[Model $model]` |
| モデル保存済み | `TRANSLATION_MODEL_SAVED` | `[Model $model]` |
| モデル削除済み | `TRANSLATION_MODEL_DELETED` | `[Model $model]` |
| フィールド更新 | `TRANSLATION_FIELD_UPDATED` | `[Model $model, string $field, mixed $value, string $locale]` |
| フィールド削除 | `TRANSLATION_FIELD_DELETED` | `[Model $model, string $field, string\|null $locale]` |

### 2.4 プラグインイベント

| イベント | 定数 | ペイロード |
|---------|------|-----------|
| インストール中 | `PLUGIN_INSTALLING` | `['plugin' => string, 'version' => string]` |
| インストール完了 | `PLUGIN_INSTALLED` | `['plugin' => string, 'version' => string]` |
| 有効化中 | `PLUGIN_ACTIVATING` | `['plugin' => string]` |
| 有効化完了 | `PLUGIN_ACTIVATED` | `['plugin' => string]` |
| 無効化中 | `PLUGIN_DEACTIVATING` | `['plugin' => string]` |
| 無効化完了 | `PLUGIN_DEACTIVATED` | `['plugin' => string]` |
| アンインストール中 | `PLUGIN_UNINSTALLING` | `['plugin' => string, 'delete_data' => bool]` |
| アンインストール完了 | `PLUGIN_UNINSTALLED` | `['plugin' => string]` |
| 更新中 | `PLUGIN_UPDATING` | `['plugin' => string, 'from_version' => string, 'to_version' => string]` |
| 更新完了 | `PLUGIN_UPDATED` | `['plugin' => string, 'from_version' => string, 'to_version' => string]` |

### 2.5 テーマイベント

| イベント | 定数 | ペイロード |
|---------|------|-----------|
| 有効化中 | `THEME_ACTIVATING` | `['theme' => string, 'previous' => string\|null]` |
| 有効化完了 | `THEME_ACTIVATED` | `['theme' => string, 'previous' => string\|null]` |

### 2.6 キャッシュイベント

| イベント | 定数 | ペイロード |
|---------|------|-----------|
| クリア中 | `CACHE_CLEARING` | `['type' => string]` |
| クリア完了 | `CACHE_CLEARED` | `['type' => string]` |

### 2.7 メンテナンスイベント

| イベント | 定数 | ペイロード |
|---------|------|-----------|
| 有効化 | `MAINTENANCE_ENABLED` | `['secret' => string\|null, 'retry' => int\|null]` |
| 無効化 | `MAINTENANCE_DISABLED` | `[]` |

### 2.8 セキュリティイベント

| イベント | 定数 | ペイロード |
|---------|------|-----------|
| 整合性スキャン開始 | `INTEGRITY_SCAN_STARTED` | `['scope' => string]` |
| 整合性スキャン完了 | `INTEGRITY_SCAN_COMPLETED` | `['scope' => string, 'status' => string, 'changes' => array]` |
| セキュリティアラート | `SECURITY_ALERT` | `['type' => string, 'details' => array]` |

## 3. 使い方

### 3.1 イベントのリッスン

```php
use App\Events\DixlaseEvents;
use Illuminate\Support\Facades\Event;

// 方法1: Event ファサードを使用
Event::listen(DixlaseEvents::BACKUP_COMPLETED, function ($payload) {
    Log::info('Backup completed', $payload);

    // 通知を送信
    Notification::send($admins, new BackupCompletedNotification($payload));
});

// 方法2: EventServiceProvider で定義
protected $listen = [
    DixlaseEvents::BACKUP_COMPLETED => [
        SendBackupNotification::class,
        UploadToCloudStorage::class,
    ],
];

// 方法3: サブスクライバークラスを使用
class BackupEventSubscriber
{
    public function handleStarted($payload)
    {
        // ...
    }

    public function handleCompleted($payload)
    {
        // ...
    }

    public function subscribe($events)
    {
        $events->listen(DixlaseEvents::BACKUP_STARTED, [self::class, 'handleStarted']);
        $events->listen(DixlaseEvents::BACKUP_COMPLETED, [self::class, 'handleCompleted']);
    }
}
```

### 3.2 イベントの発火（コア/プラグイン開発者向け）

```php
use App\Events\DixlaseEvents;

// バックアップ開始イベントを発火
event(DixlaseEvents::BACKUP_STARTED, [
    'type' => 'full',
    'options' => ['include_uploads' => true],
]);

// バックアップ完了後
event(DixlaseEvents::BACKUP_COMPLETED, [
    'type' => 'full',
    'path' => '/backups/backup-2025-01-01.zip',
    'size' => 1024000,
    'duration' => 45.5,
]);
```

### 3.3 イベントリストの取得

```php
use App\Events\DixlaseEvents;

// 全イベントを取得
$allEvents = DixlaseEvents::all();

// カテゴリ別にイベントを取得
$backupEvents = DixlaseEvents::byCategory('backup');
$deployEvents = DixlaseEvents::byCategory('deploy');
$translationEvents = DixlaseEvents::byCategory('translation');
```

## 4. ベストプラクティス

### 4.1 プラグイン開発者向け

1. 文字列リテラルではなく、**常に定数を使用**してください
2. リスナーチェーンの中断を防ぐため、**例外を適切にハンドリング**してください
3. **リスナーは軽量に保ち**、重い処理はキューに入れてください
4. デバッグのために**重要なイベントをログ**してください

### 4.2 イベントリスナーの例

```php
namespace MyPlugin\Listeners;

use App\Events\DixlaseEvents;
use Illuminate\Contracts\Queue\ShouldQueue;

class UploadBackupToS3 implements ShouldQueue
{
    public function handle($payload)
    {
        try {
            $path = $payload['path'];

            // S3にアップロード
            Storage::disk('s3')->put(
                'backups/' . basename($path),
                file_get_contents($path)
            );

            Log::info('Backup uploaded to S3', ['path' => $path]);
        } catch (\Exception $e) {
            Log::error('Failed to upload backup to S3', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
```

### 4.3 条件付きリスニング

```php
Event::listen(DixlaseEvents::BACKUP_COMPLETED, function ($payload) {
    // フルバックアップのみを処理
    if ($payload['type'] !== 'full') {
        return;
    }

    // 処理を実行...
});
```

## 5. Webhook 連携

イベントを使用してWebhookをトリガーできます：

```php
use App\Support\DixlaseSigner;

Event::listen(DixlaseEvents::BACKUP_COMPLETED, function ($payload) {
    $webhookUrl = config('backup.webhook_url');
    $secret = config('backup.webhook_secret');

    if (!$webhookUrl) {
        return;
    }

    $body = json_encode([
        'event' => 'backup.completed',
        'data' => $payload,
        'timestamp' => time(),
    ]);

    $headers = DixlaseSigner::signWebhook(
        parse_url($webhookUrl, PHP_URL_PATH),
        $body,
        $secret
    );

    Http::withHeaders($headers)->post($webhookUrl, $payload);
});
```

## 6. イベントのテスト

```php
use App\Events\DixlaseEvents;
use Illuminate\Support\Facades\Event;

class BackupTest extends TestCase
{
    public function test_backup_fires_events()
    {
        Event::fake([
            DixlaseEvents::BACKUP_STARTED,
            DixlaseEvents::BACKUP_COMPLETED,
        ]);

        // バックアップを実行
        $this->artisan('backup:run');

        // イベントが発火されたことを検証
        Event::assertDispatched(DixlaseEvents::BACKUP_STARTED);
        Event::assertDispatched(DixlaseEvents::BACKUP_COMPLETED, function ($event, $payload) {
            return $payload['type'] === 'full';
        });
    }
}
```

## 7. 将来のイベント

以下のイベントは今後のリリースで追加予定です：

### 7.1 EC（EC API 実装時）

- `dixlase.order.created`
- `dixlase.order.paid`
- `dixlase.order.shipped`
- `dixlase.order.completed`
- `dixlase.order.cancelled`
- `dixlase.cart.updated`
- `dixlase.payment.processed`
- `dixlase.payment.failed`

### 7.2 ユーザー管理（User Plugin 実装時）

- `dixlase.user.registered`
- `dixlase.user.verified`
- `dixlase.user.login`
- `dixlase.user.logout`
- `dixlase.user.password.reset`

---

**ドキュメントバージョン**: 1.0.0
**最終更新日**: 2025-01-01
**著者**: Dixlase Development Team
