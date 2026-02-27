# CAPTCHA管理コマンドガイド

## 概要

Dixlaseには、CAPTCHAシステムを管理するための2つの主要なコマンドがあります：

1. **`dls:admin:captcha-failover`** - CAPTCHA設定とフェイルオーバー管理（通常運用）
2. **`dls:admin:captcha-bypass`** - 緊急時のCAPTCHAバイパス（災害復旧専用）

これらのコマンドは、CAPTCHAプロバイダーの障害時や緊急時に管理者がシステムにアクセスできるようにするための重要なツールです。

---

## 1. CAPTCHAフェイルオーバー管理コマンド

### コマンド: `dls:admin:captcha-failover`

CAPTCHAプロバイダーの状態確認、切り替え、フェイルオーバー設定を管理します。

### 使用シーン

- **通常運用時のプロバイダー管理**
- Google reCAPTCHAが遅い → Cloudflare Turnstileに切り替え
- プロバイダーの状態確認
- 自動フェイルオーバーの有効/無効設定

---

### 1.1 ステータス確認

現在のCAPTCHAプロバイダーの状態を確認します。

```bash
php artisan dls:admin:captcha-failover status
```

**出力例:**
```
[CAPTCHA Failover Status]

+------------------+----------------------+
| Setting          | Value                |
+------------------+----------------------+
| Primary Provider | Cloudflare Turnstile |
| Active Provider  | Cloudflare Turnstile |
| Failed Over      | No                   |
| Auto Failover    | ✅ Enabled           |
+------------------+----------------------+

[Configured Providers]
+--------------------------------+------------+---------+----------+----------+
| Provider                       | Configured | Enabled | Verified | Failures |
+--------------------------------+------------+---------+----------+----------+
| ❌ Google reCAPTCHA            | No         | No      | No       | 0        |
| ❌ Google reCAPTCHA Enterprise | No         | No      | No       | 0        |
| 🟢 Cloudflare Turnstile        | Yes        | No      | No       | 0        |
+--------------------------------+------------+---------+----------+----------+
```

**表示内容:**
- **Primary Provider**: 設定されているメインプロバイダー
- **Active Provider**: 現在使用中のプロバイダー
- **Failed Over**: フェイルオーバー中かどうか
- **Auto Failover**: 自動フェイルオーバーの有効/無効
- **Configured Providers**: 各プロバイダーの設定状態

**アイコンの意味:**
- 🟢 現在アクティブ
- 🟡 設定済み・有効・検証済み（切り替え可能）
- ⚪ 設定済みだが未検証
- ❌ 未設定

---

### 1.2 利用可能プロバイダー一覧

システムでサポートされているCAPTCHAプロバイダーの一覧を表示します。

```bash
php artisan dls:admin:captcha-failover providers
```

**出力例:**
```
[Available Providers]

  - google: Google reCAPTCHA
  - google_enterprise: Google reCAPTCHA Enterprise
  - turnstile: Cloudflare Turnstile
```

---

### 1.3 プロバイダー切り替え

CAPTCHAプロバイダーを別のプロバイダーに切り替えます。

#### 一時的な切り替え（推奨）

```bash
php artisan dls:admin:captcha-failover switch --provider=turnstile
```

一時的な切り替えは、次回のデフォルトリセットまで有効です。

#### 永続的な切り替え

```bash
php artisan dls:admin:captcha-failover switch --provider=turnstile --permanent
```

永続的な切り替えは、確認プロンプトが表示されます：

```
 Permanently switch to Cloudflare Turnstile? (yes/no) [no]:
 > yes

✅ Switched to Cloudflare Turnstile (permanent).
```

**注意事項:**
- 切り替え先のプロバイダーは、設定済み・有効・検証済みである必要があります
- 未設定のプロバイダーに切り替えようとするとエラーになります

**エラー例:**
```bash
php artisan dls:admin:captcha-failover switch --provider=google
```
```
Switch failed. Please verify the provider is configured, enabled, and verified.
```

---

### 1.4 デフォルトに戻す

一時的な切り替えを解除し、デフォルトのプロバイダーに戻します。

```bash
php artisan dls:admin:captcha-failover reset
```

**出力:**
```
✅ Reset to default provider.
```

---

### 1.5 自動フェイルオーバー設定

CAPTCHAプロバイダーの障害時に、自動的に別のプロバイダーに切り替える機能を設定します。

#### 自動フェイルオーバーを有効化

```bash
php artisan dls:admin:captcha-failover --auto-failover=on
```

**出力:**
```
✅ Auto failover set to Enabled.
```

#### 自動フェイルオーバーを無効化

```bash
php artisan dls:admin:captcha-failover --auto-failover=off
```

**出力:**
```
✅ Auto failover set to Disabled.
```

---

### 1.6 コマンドオプション一覧

| オプション | 説明 | デフォルト値 |
|-----------|------|------------|
| `action` | 実行するアクション（status / switch / reset / providers） | - |
| `--provider` | 切り替え先のプロバイダー名 | - |
| `--permanent` | 永続的な切り替えを行う | false |
| `--auto-failover` | 自動フェイルオーバーの有効/無効（on/off） | - |

---

## 2. CAPTCHA緊急バイパスコマンド（ブレークグラス）

### コマンド: `dls:admin:captcha-bypass`

**⚠️ 警告: このコマンドは緊急時専用です**

全てのCAPTCHAプロバイダーが障害で管理者がログインできない場合にのみ使用してください。

### 使用シーン

- **災害復旧専用**
- 全CAPTCHAプロバイダーが障害
- 管理者がログイン不可
- 緊急アクセスが必要

### セキュリティ上の注意

1. **使用は最小限に**: 年に1回あるかないかの緊急時のみ
2. **理由の記録必須**: 全ての使用は監査ログに記録されます
3. **時間制限**: 最大60分まで
4. **確認プロンプト**: 有効化時は必ず確認が求められます

---

### 2.1 バイパスステータス確認

現在のバイパス状態と履歴を確認します。

```bash
php artisan dls:admin:captcha-bypass status
```

**出力例（無効状態）:**
```
【CAPTCHA Bypass Status】

✅ Bypass is inactive (normal operation)

【Recent Bypass History】
+---------------------+------------------------+-------------+----------+
| Time                | Action                 | Scope       | Reason   |
+---------------------+------------------------+-------------+----------+
| 2026-01-02 00:35:40 | captcha_bypass_enabled | admin_login | 緊急対応 |
+---------------------+------------------------+-------------+----------+
```

**出力例（有効状態）:**
```
【CAPTCHA Bypass Status】

⚠️ Bypass is ACTIVE (security risk)
+-------------------+---------------------+
| Field             | Value               |
+-------------------+---------------------+
| Scope             | admin_login         |
| Reason            | プロバイダー全障害  |
| Expires At        | 2026-01-02 01:00:00 |
| Remaining Minutes | 45 minutes          |
| Enabled At        | 2026-01-02 00:15:00 |
+-------------------+---------------------+
```

---

### 2.2 バイパス有効化

CAPTCHA検証を一時的にバイパスします。

#### 基本的な使用方法（デフォルト: 10分間、admin_loginのみ）

```bash
php artisan dls:admin:captcha-bypass enable --reason="全プロバイダー障害"
```

#### オプションを指定した使用方法

```bash
php artisan dls:admin:captcha-bypass enable \
  --minutes=30 \
  --scope=all \
  --reason="Google/Cloudflare両方ダウン、緊急メンテナンス必要"
```

**確認プロンプト:**
```
⚠️ Warning: CAPTCHA bypass poses a security risk.

Settings: 30 minutes, scope: all, reason: Google/Cloudflare両方ダウン、緊急メンテナンス必要

 Enable CAPTCHA bypass? (yes/no) [no]:
 > yes

✅ CAPTCHA bypass enabled for 30 minutes (expires at 2026-01-02 01:00:00).
```

**監査ログ:**
バイパス有効化は自動的に監査ログに記録されます：
- アクション: `captcha_bypass_enabled`
- カテゴリ: `security`
- 重大度: `critical`
- コンテキスト: 時間、スコープ、理由、有効期限

---

### 2.3 バイパス無効化

有効なバイパスを手動で無効化します。

```bash
php artisan dls:admin:captcha-bypass disable
```

**出力（バイパスが有効な場合）:**
```
✅ CAPTCHA bypass has been disabled.
```

**出力（バイパスが既に無効な場合）:**
```
CAPTCHA bypass is not currently active.
```

**監査ログ:**
バイパス無効化も監査ログに記録されます：
- アクション: `captcha_bypass_disabled`
- カテゴリ: `security`
- 重大度: `warning`

---

### 2.4 コマンドオプション一覧

| オプション | 説明 | デフォルト値 | 制限 |
|-----------|------|------------|------|
| `action` | 実行するアクション（enable / disable / status） | status | - |
| `--minutes` | バイパス有効時間（分） | 10 | 最大60分 |
| `--scope` | バイパスのスコープ（admin_login / all） | admin_login | - |
| `--reason` | バイパス理由（enable時必須） | - | 必須 |

**スコープの説明:**
- `admin_login`: 管理画面ログインのみバイパス（推奨）
- `all`: 全てのCAPTCHA検証をバイパス（より危険）

---

## 3. 使用例とベストプラクティス

### 3.1 通常運用のシナリオ

#### シナリオ1: Google reCAPTCHAが遅い

```bash
# 1. 現在の状態を確認
php artisan dls:admin:captcha-failover status

# 2. Cloudflare Turnstileに一時切り替え
php artisan dls:admin:captcha-failover switch --provider=turnstile

# 3. 問題が解決したらデフォルトに戻す
php artisan dls:admin:captcha-failover reset
```

#### シナリオ2: 定期メンテナンスでプロバイダー変更

```bash
# 永続的にCloudflare Turnstileに切り替え
php artisan dls:admin:captcha-failover switch --provider=turnstile --permanent
```

---

### 3.2 緊急時のシナリオ

#### シナリオ3: 全CAPTCHAプロバイダーがダウン

```bash
# 1. 状態確認（念のため）
php artisan dls:admin:captcha-failover status

# 2. 緊急バイパスを有効化（最小時間で）
php artisan dls:admin:captcha-bypass enable \
  --minutes=15 \
  --scope=admin_login \
  --reason="Google/Cloudflare両方ダウン、緊急対応必要"

# 3. 管理画面にログインして対応

# 4. 対応完了後、バイパスを無効化
php artisan dls:admin:captcha-bypass disable

# 5. 履歴確認
php artisan dls:admin:captcha-bypass status
```

---

### 3.3 ベストプラクティス

#### フェイルオーバー管理

1. **定期的な状態確認**
   ```bash
   # 週次で実行
   php artisan dls:admin:captcha-failover status
   ```

2. **自動フェイルオーバーを有効化**
   ```bash
   php artisan dls:admin:captcha-failover --auto-failover=on
   ```

3. **複数プロバイダーの設定**
   - Google reCAPTCHA
   - Cloudflare Turnstile
   - 最低2つのプロバイダーを設定・検証しておく

#### バイパス管理

1. **使用は最小限に**
   - 年に1回あるかないかの緊急時のみ
   - 通常のプロバイダー切り替えで対応できないか検討

2. **最小権限の原則**
   - `--scope=admin_login`を使用（`all`は避ける）
   - `--minutes`は必要最小限に設定

3. **必ず理由を記録**
   - 詳細な理由を`--reason`に記載
   - 監査ログで後から追跡可能

4. **使用後は必ず無効化**
   - 自動期限切れを待たず、手動で無効化
   - 無効化を忘れないようにアラート設定

---

## 4. トラブルシューティング

### 4.1 プロバイダー切り替えが失敗する

**エラー:**
```
Switch failed. Please verify the provider is configured, enabled, and verified.
```

**原因と対処:**
1. プロバイダーが設定されていない
   - 管理画面 > セキュリティ設定 > CAPTCHA設定で設定
2. プロバイダーが有効化されていない
   - 管理画面で有効化
3. プロバイダーが検証されていない
   - 管理画面でテスト実行

### 4.2 バイパスが有効化できない

**エラー:**
```
Reason for enabling bypass is required.
```

**対処:**
```bash
# --reasonオプションを必ず指定
php artisan dls:admin:captcha-bypass enable --reason="緊急対応"
```

### 4.3 コマンドが見つからない

**エラー:**
```
Command "dls:admin:captcha" not found.
```

**対処:**
```bash
# 正しいコマンド名を使用
php artisan dls:admin:captcha-failover status

# または
php artisan dls:admin:captcha-bypass status
```

---

## 5. セキュリティ考慮事項

### 5.1 監査ログ

全てのCAPTCHA管理操作は監査ログに記録されます：

**記録される情報:**
- 実行日時
- 実行者（CLI経由の場合は`triggered_by: cli`）
- アクション（enable, disable, switch等）
- 詳細（プロバイダー名、理由、期間等）

**ログ確認方法:**
```bash
# 監査ログテーブルを確認
php artisan tinker
>>> \App\Models\AuditLog::where('action', 'like', 'captcha%')->latest()->get();
```

### 5.2 アクセス制御

**推奨事項:**
1. コマンド実行権限をSUPER_ADMINのみに制限
2. サーバーへのSSHアクセスを厳格に管理
3. 実行履歴を定期的にレビュー

### 5.3 通知設定

**推奨設定:**
1. バイパス有効化時にSlack/メール通知
2. 自動期限切れ前の警告通知
3. 異常なフェイルオーバー発生時の通知

---

## 6. よくある質問（FAQ）

### Q1: `captcha-failover`と`captcha-bypass`の違いは？

**A:** 
- **`captcha-failover`**: 通常運用時のプロバイダー管理（安全）
- **`captcha-bypass`**: 緊急時のCAPTCHA無効化（危険）

### Q2: バイパスの最大時間は？

**A:** 最大60分です。セキュリティ上、長時間のバイパスは推奨されません。

### Q3: バイパス中にログインできない場合は？

**A:** 
1. バイパスが本当に有効か確認: `php artisan dls:admin:captcha-bypass status`
2. スコープが正しいか確認（`admin_login` vs `all`）
3. キャッシュクリア: `php artisan cache:clear`

### Q4: 自動フェイルオーバーはどう動作する？

**A:** プロバイダーが連続して失敗すると、自動的に次の利用可能なプロバイダーに切り替わります。

### Q5: Docker環境での実行方法は？

**A:**
```bash
# Dockerコンテナ内で実行
docker exec dixlase-laravel.test-1 php artisan dls:admin:captcha-failover status
docker exec dixlase-laravel.test-1 php artisan dls:admin:captcha-bypass status
```

---

## 7. 関連ドキュメント

- [CAPTCHA実装ガイド](../settings/security/captcha-usage.md) - CAPTCHA機能の基本的な使い方
- [セキュリティ設定ガイド](./security-settings.md) - 管理画面でのCAPTCHA設定
- [監査ログガイド](./audit-logs.md) - 監査ログの確認方法

---

## 8. サポート

問題が発生した場合は、以下の情報を含めてサポートに連絡してください：

1. 実行したコマンド
2. エラーメッセージ
3. `php artisan dls:admin:captcha-failover status`の出力
4. `storage/logs/dixlase.log`の関連ログ
5. 監査ログの該当エントリ

---

**最終更新日**: 2026-01-02  
**バージョン**: Dixlase α版
