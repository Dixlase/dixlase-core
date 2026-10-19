# 監査ログ整合性（ハッシュチェーン）

## 概要

Dixlase の監査ログは、改ざんを**検知**するための仕組みを 2 つ重ねています。改ざんを防ぐものではなく、また何かを保証するものでもありません。

- **行ごとのハッシュチェーン（鍵なし）。** 各行は、13 項目と前の行のハッシュに対する SHA-256（`record_hash`）を持ちます。鍵を使わないので、行を書き換えたうえでハッシュを計算し直せば、チェーン単体では辻褄が合ってしまいます。
- **日ごとの日次封印（鍵付き）。** 完了した 1 日ごとに、件数・最初と最後の行・最後の行のハッシュに対する HMAC-SHA256 を記録します。鍵は `AUDIT_LOG_SECRET`、未設定なら `APP_KEY` です。チェーンを計算し直しても、この鍵を知らなければ封印と合わなくなります。

どちらも監査ログと同じデータベースに置かれます。新しい行は、毎時の `audit:integrity build` がチェーンにつなぐまで保護されません。封印は毎日自動で作られ、検証は毎日スケジュールで行われます。すべての行を計算し直す全件検証は手動で実行します。この仕組みが証明できる範囲は「セキュリティ考慮事項」を参照してください。

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

## 日次封印（Daily Seal）

完了した 1 日ごとに、その日のログを日次封印で押さえます。行の削除・挿入や、日の末尾の書き換えは、封印と合わなくなることで検知されます。

### 日次封印テーブル

| フィールド | 説明 |
|-----------|------|
| `seal_date` | 対象日 |
| `first_log_id` | その日の最初のログID |
| `last_log_id` | その日の最後のログID |
| `log_count` | ログ件数 |
| `final_hash` | 最終ログのハッシュ |
| `daily_signature` | 封印の HMAC-SHA256 |
| `key_version` | 封印の鍵のバージョン |

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
# 標準の検証: 全日次封印 + 前回の検証位置以降のチェーン
php artisan audit:integrity verify

# 全件検証: 全日次封印 + チェーン全行
php artisan audit:integrity verify --all

# ID範囲を指定して検証
php artisan audit:integrity verify --from=1000 --to=2000

# 特定日の日次封印を検証（その日のチェーンも含む）
php artisan audit:integrity verify --date=2025-12-20
```

標準の検証が対象にするもの:

- **全日次封印（1日あたり O(1)）。** 封印の HMAC を再計算し、件数と最終ハッシュを照合します。
  保持期間内の全日について、行の削除・挿入、末尾の書き換え、封印の行の改ざんを検知します。
- **前回の検証位置以降のチェーン（O(行数)）。** すでに `valid` と記録された行は再ハッシュせず、
  その最後の行を起点として以降を検証します。

対象に**ならない**もの: 検証済みの行を `record_hash` を変えずにその場で書き換えた場合。
これはその行を再ハッシュしないと検知できないため、`verify --all` を定めた頻度で実行してください
（週次が妥当な既定です）。増分検証のたびにコマンドがその旨を表示します。

日次封印または行の検証に失敗した場合、あるいは以前の検証で改ざん検知済みの行が残っている場合、
コマンドは 0 以外の終了コードを返します。

### 日次封印作成

```bash
# 過去7日分の日次封印を作成
php artisan audit:integrity seal

# 日数を指定
php artisan audit:integrity seal --days=30

# 特定日の日次封印を作成
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

日次封印統計（過去30日間）

+------------------+-------+
| 項目             | 値    |
+------------------+-------+
| 日次封印の総数   | 30    |
| 正常な日次封印   | 30    |
| 異常な日次封印   | 0     |
| 封印済みログ数   | 15420 |
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

// 日次封印作成
$seal = $service->createDailySeal(now()->subDay());

// 日次封印検証
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
    app(SystemNotificationService::class)->sendAdminNotification(
        'Audit Log Tampering Detected',
        "Log ID {$log->id} has been tampered with.",
        ['audit_log_id' => $log->id]
    );
}
```

## 管理画面

### サイトヘルス

ダッシュボードの **サイトヘルス**（詳細モード）に、`AuditLogIntegrityService::getHealth()`
に基づく **監査ログ整合性** の項目が表示されます:

| 状態 | バッジ | 意味 |
|------|--------|------|
| 改ざん検知 | 重大 | レコードまたは日次封印の検証に失敗している |
| チェーン停滞 | 警告 | 2 時間以上チェーンに連結されていない行がある — 毎時の `audit:integrity build` が動いていない |
| 封印の遅れ | 警告 | 直近の完了日（ログあり）の日次封印が翌日 04:30 を過ぎても無い — 日次の `audit:integrity seal` が動いていない |
| 検証が古い | 推奨 | 連結から 30 日以上経っても一度も検証されていないエントリがある、または最も古い検証が 30 日以上前（増分検証では古い行は更新されないため `verify --all` を実行する）。前回の検証以降に連結された行は未検証で正常なので対象外 |
| OK | OK | チェーンと日次封印が最新（初日は OK 表示: 日次封印は完了した日にしか存在しない） |

これは cron 未設定に対する安全装置です。これが無いと、スケジューラが動いていないままチェーンが
静かに空のままになります。この状態は 5 分間キャッシュされ、`audit:integrity` を実行するたびに
クリアされます。

### 監査ログ詳細

監査ログ詳細画面に、各エントリのチェーン連番・レコードハッシュ・検証状態
（検証済み / 改ざん検知 / 未検証 / 未連結）・最終検証日時を表示します。いずれも読み取り専用で、
上記のコマンドが更新します。

## 推奨運用

### 定期タスク（Scheduler）

チェーン構築・封印・検証はコアが `routes/console.php` でスケジュール登録済みです。Laravel の
スケジューラ（cron から `php artisan schedule:run`）を動かす以外の設定は不要です:

| タスク | スケジュール |
|--------|--------------|
| `audit:integrity build` | 毎時 |
| `audit:integrity seal` | 毎日 03:30（03:00 のファイル整合性スキャンの後） |
| `audit:integrity verify` | 毎日 03:45（封印の後） |

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

1. **封印の鍵の保護**: 日次封印は `AUDIT_LOG_SECRET`（`config('app.audit_log_secret')`）で行い、未設定なら `APP_KEY` にフォールバックします。既定のままでは、チェーンと封印は**データベース上の**改ざんを検知しますが、`.env` まで読める攻撃者は両方を再計算できます。専用の `AUDIT_LOG_SECRET` を（できれば同一ホスト外に）設定すると、封印が証明できる範囲が広がります。ただし最初の封印が作られる前に決め、以後は変更しないでください（未設定のまま `APP_KEY` を変更する場合も同様）。鍵のローテーション（`key_version`）は未実装のため、旧鍵で作った日次封印は検証に失敗し、サイトヘルスが重大表示になります
2. **定期検証**: 定めた頻度で `audit:integrity verify` を実行し、定期的に `verify --all` も実行する（「整合性の検証」を参照）
3. **アラート設定**: 通知先のメールアドレスとメールサーバーを設定し、毎日の検証の失敗をバナーに加えてメールでも受け取る
4. **バックアップ**: 日次封印テーブルも含めてバックアップ
5. **アクセス制限**: 監査ログテーブルへの直接アクセスを制限

## 技術仕様

### ハッシュアルゴリズム

- **レコードハッシュ**: SHA-256
- **日次封印**: HMAC-SHA256

### 検証エラーコード

| コード | 説明 |
|--------|------|
| `hash_mismatch` | レコードのハッシュが一致しない |
| `chain_broken` | 前レコードへのリンクが切れている |
| `sequence_gap` | シーケンス番号に欠番がある |
| `invalid_genesis` | 最初のレコードが不正 |
| `redaction_unrecorded` | 消去済みの印があるのに、それを説明する消去の記録が無い |

### 消去済みのレコード（個人データの消去）

メンバーの個人データを消去すると（プライバシー → 消去）、監査ログの行は残したまま
`ip_address` を空にし、ほかの識別情報を置き換えます。`ip_address` はレコードの
ハッシュの対象なので、消去した行は自分のハッシュと一致しなくなり、消した値が無い以上
ハッシュを計算し直すこともできません。

そこで消去は自身を記録します。運用者と本人（id のみ）を示す `privacy_data_erased`
の行を 1 行書き、匿名化した各行に `redacted_at` と `redaction_id`（その行の
`request_id` と同じ値）を付けます。消去の行を書けなかった場合は消去そのものを拒み、
何も変更しません。

検証は消去済みの行を次のように扱います。

- 行自身のハッシュは確かめません。チェーン上の位置は引き続き確かめます。次の行の
  `previous_hash` と日次封印がその `record_hash` を押さえているので、行を消したり
  並べ替えたりすれば気づきます。
- 消去の行が存在し、その行の `redaction_id` を持ち、その行のアクターを対象として示し、
  その行より後にあることが必要です。満たさない場合は `redaction_unrecorded` で失敗します。
- 受け入れた行は **消去済み**（検証ステータス `redacted`）として数え、正常とも改ざんとも
  しません。改ざんの警告も出しません。

ハッシュの対象外の項目（`user_agent` / `actor_name` / `target_label`）は、もともと
ハッシュで守られていません。上の「ハッシュ計算対象」を参照してください。

この仕組みより前に匿名化された行には `redacted_at` が無く、引き続き `hash_mismatch`
で失敗します。どの消去が変えたかを示す記録が無いため、遡って埋めません。

### パフォーマンス

- ハッシュ計算: 約0.1ms/レコード
- チェーン検証: 約1,000レコード/秒
- 日次封印作成: 約0.5秒/日

## ベータ後の予定

- 定期実行の検証の ON/OFF・頻度設定（検証のみ — 生成は常時有効のまま）
- 管理画面の「今すぐ検証」ボタン（キュー化が必要。同期のチェーン検証は行数に比例して重くなる）
- 書き込み時のインラインチェーン化（毎時バッチの最大 1 時間の空白を無くす）
- リアルタイム改ざん検知
- 外部タイムスタンプサービス連携
- 署名付きログのエクスポート／インポート
