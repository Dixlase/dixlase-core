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

### 整合性の検証

```bash
# 標準の検証: 全日次署名 + 前回の検証位置以降のチェーン
php artisan audit:integrity verify

# 全件検証: 全日次署名 + チェーン全行
php artisan audit:integrity verify --all

# ID範囲を指定して検証
php artisan audit:integrity verify --from=1000 --to=2000

# 特定日の日次署名を検証（その日のチェーンも含む）
php artisan audit:integrity verify --date=2025-12-20
```

標準の検証が対象にするもの:

- **全日次署名（1日あたり O(1)）。** HMAC 署名を再計算し、件数と最終ハッシュを照合します。
  保持期間内の全日について、行の削除・挿入、末尾の書き換え、署名行の改ざんを検知します。
- **前回の検証位置以降のチェーン（O(行数)）。** すでに `valid` と記録された行は再ハッシュせず、
  その最後の行を起点として以降を検証します。

対象に**ならない**もの: 検証済みの行を `record_hash` を変えずにその場で書き換えた場合。
これはその行を再ハッシュしないと検知できないため、`verify --all` を定めた頻度で実行してください
（週次が妥当な既定です）。増分検証のたびにコマンドがその旨を表示します。

署名または行の検証に失敗した場合、あるいは以前の検証で改ざん検知済みの行が残っている場合、
コマンドは 0 以外の終了コードを返します。

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

## 管理画面

### サイトヘルス

ダッシュボードの **サイトヘルス**（詳細モード）に、`AuditLogIntegrityService::getHealth()`
に基づく **監査ログ整合性** の項目が表示されます:

| 状態 | バッジ | 意味 |
|------|--------|------|
| 改ざん検知 | 重大 | レコードまたは日次署名の検証に失敗している |
| チェーン停滞 | 警告 | 2 時間以上チェーンに連結されていない行がある — 毎時の `audit:integrity build` が動いていない |
| 署名遅延 | 警告 | 直近の完了日（ログあり）の署名が翌日 04:30 を過ぎても無い — 日次の `audit:integrity seal` が動いていない |
| 検証が古い | 推奨 | 連結から 30 日以上経っても一度も検証されていないエントリがある、または最も古い検証が 30 日以上前（増分検証では古い行は更新されないため `verify --all` を実行する）。前回の検証以降に連結された行は未検証で正常なので対象外 |
| OK | OK | チェーンと署名が最新（初日は OK 表示: 署名は完了した日にしか存在しない） |

これは cron 未設定に対する安全装置です。これが無いと、スケジューラが動いていないままチェーンが
静かに空のままになります。この状態は 5 分間キャッシュされ、`audit:integrity` を実行するたびに
クリアされます。

### 監査ログ詳細

監査ログ詳細画面に、各エントリのチェーン連番・レコードハッシュ・検証状態
（検証済み / 改ざん検知 / 未検証 / 未連結）・最終検証日時を表示します。いずれも読み取り専用で、
上記のコマンドが更新します。

## 推奨運用

### 定期タスク（Scheduler）

チェーン構築・署名・検証はコアが `routes/console.php` でスケジュール登録済みです。Laravel の
スケジューラ（cron から `php artisan schedule:run`）を動かす以外の設定は不要です:

| タスク | スケジュール |
|--------|--------------|
| `audit:integrity build` | 毎時 |
| `audit:integrity seal` | 毎日 03:30（03:00 のファイル整合性スキャンの後） |
| `audit:integrity verify` | 毎日 03:45（署名の後） |

また、Web インストーラの完了時にもチェーンを 1 回構築するため、インストール時のイベントは
最初の毎時実行を待たずに保護されます。

生成は常時有効で、ON/OFF はありません。レコードは後から遡って保護できないため、保護を
「誰かが実行を思い出すこと」に依存させないためです。検証は毎日実行します（差分の
`audit:integrity verify`。PCI DSS 10.4.1 が求める日次レビューに当たります。NIST AU-9 は頻度を
定めていません）。失敗すると、**セキュリティ → 整合性** を開ける管理者全員に、管理画面全体の
バナーが出ます。通知とメールサーバーを設定していれば、管理者向けの通知メールも送ります。
メールは失敗を最初に見つけたときに 1 回だけ送り、バナーは後の検証が通るまで残ります。
手動の `verify` や `verify --all` も同じようにバナーを出し、消します（範囲の一部だけを見る
`--date` や `--from`/`--to` の実行は除く）。定期実行は差分なので、`verify --all` も定期的に
実行してください（「整合性の検証」を参照）。

### セキュリティ考慮事項

1. **署名キーの保護**: 署名は `AUDIT_LOG_SECRET`（`config('app.audit_log_secret')`）で行い、未設定なら `APP_KEY` にフォールバックします。既定のままでは、チェーンと署名は**データベース上の**改ざんを検知しますが、`.env` まで読める攻撃者は両方を再計算できます。専用の `AUDIT_LOG_SECRET` を（できれば同一ホスト外に）設定すると、署名が証明できる範囲が広がります。ただし最初の署名が作られる前に決め、以後は変更しないでください（未設定のまま `APP_KEY` を変更する場合も同様）。鍵のローテーション（`key_version`）は未実装のため、旧鍵で署名した日次署名は検証に失敗し、サイトヘルスが重大表示になります
2. **定期検証**: 定めた頻度で `audit:integrity verify` を実行し、定期的に `verify --all` も実行する（「整合性の検証」を参照）
3. **アラート設定**: 通知先のメールアドレスとメールサーバーを設定し、毎日の検証の失敗をバナーに加えてメールでも受け取る
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

## ベータ後の予定

- 定期実行の検証の ON/OFF・頻度設定（検証のみ — 生成は常時有効のまま）
- 管理画面の「今すぐ検証」ボタン（キュー化が必要。同期のチェーン検証は行数に比例して重くなる）
- 書き込み時のインラインチェーン化（毎時バッチの最大 1 時間の空白を無くす）
- リアルタイム改ざん検知
- 外部タイムスタンプサービス連携
- 署名付きログのエクスポート／インポート
