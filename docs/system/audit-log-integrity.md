# 監査ログ整合性（ハッシュチェーン）

## 概要

Dixlaseの監査ログは、ブロックチェーンに似たハッシュチェーン技術を使用して改ざん耐性を実現しています。各ログレコードは前のレコードのハッシュを含み、連鎖的に整合性を保証します。

## 仕組み

### ハッシュチェーン

```
[Log 1] ──hash──> [Log 2] ──hash──> [Log 3] ──hash──> ...
   │                 │                 │
   └─ genesis        └─ hash(Log 1)    └─ hash(Log 2)
```

各ログレコードには以下のフィールドが追加されます：

| フィールド | 説明 |
|-----------|------|
| `record_hash` | このレコードのSHA-256ハッシュ（64文字） |
| `previous_hash` | 前レコードのハッシュ（最初は'genesis'） |
| `chain_sequence` | チェーン内の連番 |
| `hash_algorithm` | 使用アルゴリズム（sha256） |
| `verification_status` | 検証結果（valid/invalid/null） |
| `last_verified_at` | 最終検証日時 |

### ハッシュ計算対象

```php
$data = implode('|', [
    $this->id,
    $this->occurred_at->toIso8601String(),
    $this->severity,
    $this->outcome,
    $this->category,
    $this->action,
    $this->actor_type,
    $this->actor_id,
    $this->target_type,
    $this->target_id,
    $this->ip_address,
    json_encode($this->context),
    $previousHash,  // 前レコードのハッシュ
]);

$hash = hash('sha256', $data);
```

## 日次署名（Daily Seal）

毎日のログを「封印」して、その日のログ全体の整合性を保証します。

### 日次署名テーブル

| フィールド | 説明 |
|-----------|------|
| `seal_date` | 対象日 |
| `first_log_id` | その日の最初のログID |
| `last_log_id` | その日の最後のログID |
| `log_count` | ログ件数 |
| `final_hash` | 最終ログのハッシュ |
| `daily_signature` | HMAC-SHA256署名 |
| `key_version` | 署名キーのバージョン |

## コマンド

### ハッシュチェーン構築

```bash
# 未処理のログにハッシュチェーンを設定
php artisan audit:integrity build

# 処理件数を制限
php artisan audit:integrity build --limit=500
```

### ハッシュチェーン検証

```bash
# 全チェーンを検証
php artisan audit:integrity verify

# ID範囲を指定して検証
php artisan audit:integrity verify --from=1000 --to=2000

# 特定日の日次署名を検証
php artisan audit:integrity verify --date=2025-12-20
```

### 日次署名作成

```bash
# 過去7日分の日次署名を作成
php artisan audit:integrity seal

# 日数を指定
php artisan audit:integrity seal --days=30

# 特定日の署名を作成
php artisan audit:integrity seal --date=2025-12-20
```

### 統計表示

```bash
php artisan audit:integrity stats
```

出力例：
```
監査ログ整合性統計

+---------------------------+--------+
| 項目                      | 値     |
+---------------------------+--------+
| 総ログ数                  | 15420  |
| ハッシュチェーン設定済み  | 15420  |
| ハッシュチェーン未設定    | 0      |
| 検証済み                  | 15420  |
| 改ざん検知                | 0      |
| 未検証                    | 0      |
+---------------------------+--------+

日次署名統計（過去30日間）

+------------------+-------+
| 項目             | 値    |
+------------------+-------+
| 総署名数         | 30    |
| 正常な署名       | 30    |
| 異常な署名       | 0     |
| 署名済みログ数   | 15420 |
+------------------+-------+
```

## プログラムからの使用

### ハッシュチェーン付きでログを記録

```php
use App\Models\AuditLog;

// 通常のログ記録（ハッシュチェーンなし）
AuditLog::log([
    'category' => AuditLog::CATEGORY_AUTH,
    'action' => AuditLog::ACTION_LOGIN,
    'actor' => $member,
]);

// ハッシュチェーン付きでログを記録
AuditLog::logWithHashChain([
    'category' => AuditLog::CATEGORY_SECURITY,
    'action' => AuditLog::ACTION_SETTINGS_UPDATED,
    'actor' => $member,
    'context' => ['changes' => $changes],
]);
```

### 整合性検証サービス

```php
use App\Services\AuditLogIntegrityService;

$service = app(AuditLogIntegrityService::class);

// チェーン検証
$result = $service->verifyChain();
if (!$result['is_valid']) {
    // 改ざん検知
    foreach ($result['errors'] as $error) {
        Log::alert('Audit log tampered', $error);
    }
}

// 日次署名作成
$seal = $service->createDailySeal(now()->subDay());

// 日次署名検証
$result = $service->verifyDailySeal(now()->subDay());

// 統計取得
$stats = $service->getStats();
```

### 改ざん検知時の対応

```php
// 改ざんが検知されたログを取得
$tamperedLogs = AuditLog::tampered()->get();

foreach ($tamperedLogs as $log) {
    // アラート送信
    SystemNotificationService::send(
        'Audit Log Tampering Detected',
        "Log ID {$log->id} has been tampered with.",
        'critical'
    );
}
```

## 推奨運用

### 定期タスク（Scheduler）

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    // 毎時: 未処理ログにハッシュチェーンを設定
    $schedule->command('audit:integrity build --limit=1000')
        ->hourly();

    // 毎日深夜: 前日の日次署名を作成
    $schedule->command('audit:integrity seal --days=1')
        ->dailyAt('00:30');

    // 毎週: 過去7日分のチェーンを検証
    $schedule->command('audit:integrity verify')
        ->weekly();
}
```

### セキュリティ考慮事項

1. **署名キーの保護**: `APP_KEY`または専用の`AUDIT_LOG_SECRET`を安全に管理
2. **定期検証**: 週次または日次でチェーン全体を検証
3. **アラート設定**: 改ざん検知時に即座に通知
4. **バックアップ**: 日次署名テーブルも含めてバックアップ
5. **アクセス制限**: 監査ログテーブルへの直接アクセスを制限

## 技術仕様

### ハッシュアルゴリズム

- **レコードハッシュ**: SHA-256
- **日次署名**: HMAC-SHA256

### 検証エラーコード

| コード | 説明 |
|--------|------|
| `hash_mismatch` | レコードのハッシュが一致しない |
| `chain_broken` | 前レコードへのリンクが切れている |
| `sequence_gap` | シーケンス番号に欠番がある |
| `invalid_genesis` | 最初のレコードが不正 |

### パフォーマンス

- ハッシュ計算: 約0.1ms/レコード
- チェーン検証: 約1,000レコード/秒
- 日次署名作成: 約0.5秒/日

## β版以降の予定

- 管理画面での整合性ダッシュボード
- リアルタイム改ざん検知
- 外部タイムスタンプサービス連携
- ログのエクスポート・インポート（署名付き）
