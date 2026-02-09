# Dixlase Webhook Specification

## Overview

Dixlaseは外部システムへのWebhook送信機能を提供します。イベント発生時に登録されたエンドポイントへHTTP POSTリクエストを送信し、リアルタイムな連携を実現します。

## Version

- **Specification Version**: v1
- **Status**: Alpha

## 1. アーキテクチャ

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│  Dixlase Event  │────▶│ WebhookDispatcher│────▶│   Queue (Job)   │
│  (DixlaseEvents)│     │                  │     │  (SendWebhook)  │
└─────────────────┘     └──────────────────┘     └────────┬────────┘
                                                          │
                                                          ▼
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│ External System │◀────│   HTTP POST      │◀────│  DixlaseSigner  │
│   (Endpoint)    │     │  (with signature)│     │  (HMAC-SHA256)  │
└─────────────────┘     └──────────────────┘     └─────────────────┘
```

## 2. 基本的な使い方

### 2.1 Facadeを使用した送信

```php
use App\Facades\Webhook;

// 非同期送信（キュー経由）
Webhook::dispatch('dixlase.backup.completed', [
    'path' => '/backups/backup_20251221.zip',
    'size' => 1024000,
]);

// 同期送信（即座に送信）
Webhook::dispatchSync('order.created', [
    'order_id' => 123,
    'total' => 9800,
]);
```

### 2.2 WebhookDispatcherを直接使用

```php
use App\Services\WebhookDispatcher;

// 非同期送信
WebhookDispatcher::dispatch('event.name', $payload);

// 特定のWebhookに送信
WebhookDispatcher::dispatchTo($webhook, 'event.name', $payload);
```

### 2.3 プラグインからの使用

```php
use App\Facades\Webhook;
use App\Events\DixlaseEvents;

// プラグインのイベントを発火（自動的にWebhookも送信される）
event(DixlaseEvents::PLUGIN_ACTIVATED, ['plugin' => 'MyPlugin']);

// カスタムイベントを送信
Webhook::dispatch('myplugin.order.shipped', [
    'order_id' => $order->id,
    'tracking_number' => $trackingNumber,
]);
```

## 3. Webhookの登録

### 3.1 データベースに直接登録

```php
use App\Models\Webhook;

$webhook = Webhook::create([
    'name' => 'Slack通知',
    'url' => 'https://hooks.slack.com/services/xxx',
    'secret' => Webhook::generateSecret(),
    'events' => ['dixlase.backup.completed', 'dixlase.backup.failed'],
    'is_active' => true,
    'environment' => 'live',
    'timeout' => 30,
    'retry_count' => 3,
    'headers' => [
        'X-Custom-Header' => 'value',
    ],
]);
```

### 3.2 全イベントを購読

```php
$webhook = Webhook::create([
    'name' => '全イベント監視',
    'url' => 'https://example.com/webhook',
    'secret' => Webhook::generateSecret(),
    'events' => null, // null または ['*'] で全イベント購読
]);
```

## 4. リクエスト形式

### 4.1 HTTPヘッダー

| ヘッダー | 説明 | 例 |
|---------|------|-----|
| `Content-Type` | コンテンツタイプ | `application/json` |
| `User-Agent` | ユーザーエージェント | `Dixlase-Webhook/1.0` |
| `X-Dixlase-Event` | イベント名 | `dixlase.backup.completed` |
| `X-Dixlase-Delivery` | 配信ID | `12345` |
| `X-Dixlase-Event-Id` | イベントID（冪等性用UUID） | `550e8400-e29b-41d4-a716-446655440000` |
| `X-Dixlase-Nonce` | ノンス（リプレイ防止用） | `a1b2c3d4...` (64文字) |
| `X-Dixlase-Timestamp` | タイムスタンプ | `1734700800` |
| `X-Dixlase-Signature` | 署名 | `v1=abc123...` |

### 4.2 リクエストボディ

```json
{
    "event": "dixlase.backup.completed",
    "timestamp": 1734700800,
    "data": {
        "path": "/backups/backup_20251221.zip",
        "size": 1024000,
        "duration": 45.2
    }
}
```

## 5. 署名検証

Webhookリクエストには`X-Dixlase-Signature`ヘッダーが含まれます。受信側はこの署名を検証してリクエストの正当性を確認できます。

### 5.1 署名検証の実装例（PHP）

```php
function verifyWebhookSignature(
    string $payload,
    string $signature,
    string $timestamp,
    string $secret
): bool {
    // タイムスタンプの有効期限チェック（5分以内）
    if (abs(time() - (int)$timestamp) > 300) {
        return false;
    }

    // 署名文字列の構築
    $bodyHash = hash('sha256', $payload);
    $signingString = implode("\n", [
        $timestamp,
        'POST',
        parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
        $bodyHash,
    ]);

    // 署名の計算
    $expectedSignature = 'v1=' . hash_hmac('sha256', $signingString, $secret);

    // 定数時間比較
    return hash_equals($expectedSignature, $signature);
}

// 使用例
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_DIXLASE_SIGNATURE'] ?? '';
$timestamp = $_SERVER['HTTP_X_DIXLASE_TIMESTAMP'] ?? '';
$secret = 'whsec_your_webhook_secret';

if (!verifyWebhookSignature($payload, $signature, $timestamp, $secret)) {
    http_response_code(401);
    exit('Invalid signature');
}

// 署名検証成功、ペイロードを処理
$data = json_decode($payload, true);
```

### 5.2 署名検証の実装例（Node.js）

```javascript
const crypto = require('crypto');

function verifyWebhookSignature(payload, signature, timestamp, secret) {
    // タイムスタンプチェック
    const now = Math.floor(Date.now() / 1000);
    if (Math.abs(now - parseInt(timestamp)) > 300) {
        return false;
    }

    // 署名計算
    const bodyHash = crypto.createHash('sha256').update(payload).digest('hex');
    const signingString = [timestamp, 'POST', '/webhook', bodyHash].join('\n');
    const expectedSignature = 'v1=' + crypto
        .createHmac('sha256', secret)
        .update(signingString)
        .digest('hex');

    // 定数時間比較
    return crypto.timingSafeEqual(
        Buffer.from(expectedSignature),
        Buffer.from(signature)
    );
}
```

## 6. リトライ機能

### 6.1 リトライポリシー

配信に失敗した場合、指数バックオフでリトライします：

| 試行回数 | 待機時間 |
|---------|---------|
| 1回目 | 即座 |
| 2回目 | 1分後 |
| 3回目 | 5分後 |
| 4回目 | 30分後 |

### 6.2 リトライ処理コマンド

```bash
# 保留中のリトライを処理
php artisan webhooks:retry
```

### 6.3 スケジューラーへの登録

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    $schedule->command('webhooks:retry')->everyMinute();
}
```

## 7. 配信ステータス

| ステータス | 説明 |
|-----------|------|
| `pending` | 配信待ち |
| `success` | 配信成功（2xx応答） |
| `failed` | 配信失敗（リトライ上限到達） |
| `retrying` | リトライ待ち |

## 8. 利用可能なイベント

`DixlaseEvents`クラスで定義されている全イベントがWebhook送信対象です：

### バックアップイベント
- `dixlase.backup.started`
- `dixlase.backup.completed`
- `dixlase.backup.failed`
- `dixlase.backup.cleanup.started`
- `dixlase.backup.cleanup.completed`

### デプロイイベント
- `dixlase.deploy.before`
- `dixlase.deploy.after`
- `dixlase.deploy.failed`

### プラグインイベント
- `dixlase.plugin.installed`
- `dixlase.plugin.activated`
- `dixlase.plugin.deactivated`
- `dixlase.plugin.uninstalled`

### セキュリティイベント
- `dixlase.integrity.scan.completed`
- `dixlase.security.alert`

### その他
- `dixlase.cache.cleared`
- `dixlase.maintenance.enabled`
- `dixlase.maintenance.disabled`

## 9. 統計情報の取得

```php
use App\Facades\Webhook;

// 全体の統計
$stats = Webhook::getStats();
// [
//     'total' => 100,
//     'successful' => 95,
//     'failed' => 3,
//     'pending' => 2,
//     'success_rate' => 95.0,
// ]

// 特定Webhookの統計
$stats = Webhook::getStats($webhookId, days: 30);
```

## 10. 冪等性（Idempotency）

Webhookは同じイベントが複数回配信される可能性があります。受信側は`X-Dixlase-Event-Id`を使用して重複処理を防止できます。

### 10.1 イベントIDの仕組み

- 各Webhook配信には一意のUUID v4形式の`event_id`が付与されます
- 同じイベントのリトライでは同じ`event_id`が使用されます
- 手動リトライでは同じ`event_id`で新しい`nonce`が生成されます

### 10.2 受信側での冪等性実装例（PHP）

```php
// イベントIDを取得
$eventId = $_SERVER['HTTP_X_DIXLASE_EVENT_ID'] ?? '';

// 既に処理済みかチェック
if (ProcessedWebhook::where('event_id', $eventId)->exists()) {
    // 既に処理済み - 200を返して終了
    http_response_code(200);
    exit('Already processed');
}

// 処理を実行
processWebhook($payload);

// 処理済みとして記録
ProcessedWebhook::create(['event_id' => $eventId, 'processed_at' => now()]);
```

### 10.3 リプレイ防止

`X-Dixlase-Nonce`ヘッダーは各配信で一意の値が生成されます。タイムスタンプと組み合わせることで、リプレイ攻撃を防止できます。

```php
// nonceの検証（オプション）
$nonce = $_SERVER['HTTP_X_DIXLASE_NONCE'] ?? '';
$timestamp = $_SERVER['HTTP_X_DIXLASE_TIMESTAMP'] ?? '';

// 使用済みnonceをチェック（5分以内のものを保持）
if (UsedNonce::where('nonce', $nonce)->exists()) {
    http_response_code(401);
    exit('Nonce already used');
}

// nonceを記録
UsedNonce::create(['nonce' => $nonce, 'timestamp' => $timestamp]);
```

## 11. デッドレター（Dead Letter）

最大リトライ回数に達しても配信に失敗したWebhookは「デッドレター」として記録されます。

### 11.1 デッドレターの仕組み

1. 配信が失敗し、リトライ上限に達する
2. `webhook_dead_letters`テーブルに詳細が記録される
3. 管理者に通知が送信される（設定時）
4. 管理画面から手動リトライが可能

### 11.2 デッドレターコマンド

```bash
# 統計を表示
php artisan webhooks:dead-letters --stats

# 未通知のデッドレターの通知を送信
php artisan webhooks:dead-letters --notify

# 古いデッドレターをクリーンアップ（90日以上前）
php artisan webhooks:dead-letters --cleanup --days=90
```

### 11.3 デッドレターテーブル構造

| カラム | 説明 |
|--------|------|
| `event_id` | イベントID（冪等性追跡用） |
| `event` | イベント名 |
| `payload` | 元のペイロード |
| `last_error` | 最後のエラーメッセージ |
| `total_attempts` | 総試行回数 |
| `attempt_log` | 各試行の詳細ログ（JSON） |
| `notified` | 通知済みフラグ |
| `manually_retried` | 手動リトライ済みフラグ |

## 12. ベストプラクティス

### 受信側の実装

1. **署名を必ず検証する** - 不正なリクエストを拒否
2. **タイムスタンプを確認する** - リプレイ攻撃を防止
3. **イベントIDで冪等性を確保する** - 同じイベントが複数回届いても問題ないように
4. **nonceを検証する**（オプション） - より強固なリプレイ防止
5. **迅速に応答する** - 5秒以内に200を返す
6. **非同期で処理する** - 重い処理はキューに入れる

### 送信側（Dixlase）

1. **適切なイベントを選択する** - 必要なイベントのみ購読
2. **シークレットを安全に保管する** - 環境変数や暗号化ストレージを使用
3. **エラーログを監視する** - 配信失敗を検知

## 13. β版以降の予定機能

- Webhook管理画面UI
- 配信ログの閲覧・検索
- 手動再送信機能
- Webhookテスト送信
- イベントフィルタリング（条件付き送信）
- レート制限設定

---

**Document Version**: 1.0.0  
**Last Updated**: 2025-12-21  
**Author**: Dixlase Development Team
