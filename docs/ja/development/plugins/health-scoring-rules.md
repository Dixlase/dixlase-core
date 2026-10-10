# プラグイン・テーマ 健全性スコアリングルール
Dixlase がプラグイン・テーマの安全性を評価する2つのスコアリングシステム、**リスクスコア** と **健全性スコア** の仕様です。

---

## 1. リスクスコア（権限ベース）

`plugin.json` / `theme.json` で宣言された権限を評価します。スキャン結果の確認理由や注意レベルの判定に使用されます。

### しきい値

| 合計スコア | リスクレベル |
|-----------|-------------|
| 0 – 2     | `low`（低）  |
| 3 – 6     | `medium`（中）|
| 7以上      | `high`（高） |

### プラグイン権限スコア

#### 高リスク権限（スコア 2以上）

| 権限                            | スコア | 説明                           |
|---------------------------------|--------|-------------------------------|
| `members.write`                 | +3     | メンバーデータの書き込み         |
| `members.create`                | +3     | 新規メンバーの作成              |
| `members.delete`                | +4     | メンバーの削除                  |
| `mail.bulk_send`                | +3     | メールの一括送信                |
| `storage.public_uploads`        | +2     | 公開ディレクトリへのアップロード  |
| `content.write_other_plugins`   | +2     | 他プラグインのコンテンツ書き込み  |

#### 低リスク権限（スコア 0）

| 権限                              | スコア | 説明                          |
|-----------------------------------|--------|-------------------------------|
| `database.own_tables`             | 0      | 専用テーブル                   |
| `database.core_tables_read`       | 0      | コアテーブルの読み取り          |
| `database.core_tables_write`      | 1      | コアテーブルへの書き込み        |
| `storage.own_directory`           | 0      | 専用ディレクトリ               |
| `storage.temp_files`              | 0      | 一時ファイル                   |
| `settings.read_core`              | 0      | コア設定の読み取り             |
| `settings.write_own`              | 0      | 自己設定の書き込み             |
| `members.read`                    | 0      | メンバーデータの読み取り        |
| `mail.send`                       | 0      | 個別メールの送信               |
| `content.read_other_plugins`      | 0      | 他プラグインのコンテンツ読取    |
| `system.register_shortcodes`      | 0      | ショートコードの登録            |
| `system.register_middleware`      | 0      | ミドルウェアの登録              |
| `system.register_commands`        | 0      | コマンドの登録                 |
| `system.register_blade_directives`| 0      | Blade指令の登録                |
| `system.modify_routes`            | 0      | ルートの変更                   |

#### 不一致ペナルティ

| 不一致タイプ           | 1件あたりのスコア | 説明                                    |
|----------------------|------------------|----------------------------------------|
| `undeclared_usage`   | +2               | JSONで未宣言の権限をコードで使用している    |
| `unused_declaration` | 0                | JSONで宣言した権限がコードで使用されていない  |

### テーマ権限スコア

テーマはプラグインとは異なる権限セットとスコアを使用します。

#### 高リスク権限（スコア 2以上）

| 権限                            | スコア | 説明                           |
|---------------------------------|--------|-------------------------------|
| `storage.public_uploads`        | +2     | 公開ディレクトリへのアップロード  |
| `assets.external_resources`     | +3     | 外部リソースの読み込み          |

#### 中リスク権限（スコア 1）

| 権限                                 | スコア | 説明                           |
|--------------------------------------|--------|-------------------------------|
| `system.register_commands`           | +1     | コマンドの登録                  |
| `system.register_blade_directives`   | +1     | Blade指令の登録                |
| `system.modify_routes`              | +1     | ルートの変更                   |
| `database.core_tables_write`        | +1     | コアテーブルへの書き込み        |

#### 低リスク権限（スコア 0）

| 権限                        | スコア | 説明                          |
|-----------------------------|--------|-------------------------------|
| `database.own_tables`       | 0      | 専用テーブル                   |
| `database.core_tables_read` | 0      | コアテーブルの読み取り          |
| `storage.own_directory`     | 0      | 専用ディレクトリ               |
| `storage.temp_files`        | 0      | 一時ファイル                   |
| `settings.read_core`        | 0      | コア設定の読み取り             |
| `settings.write_own`        | 0      | 自己設定の書き込み             |
| `assets.custom_css`         | 0      | カスタムCSS                   |
| `assets.custom_js`          | 0      | カスタムJS                    |
| `system.register_shortcodes`| 0      | ショートコードの登録            |
| `system.register_middleware`| 0      | ミドルウェアの登録              |

---

## 2. 健全性スコア（0～100点）

健全性スコアはプラグインの全体的な健全性を判定するシステムです。**基本点100点** から各基準に基づいて減点します。

ソース: `PluginHealthScorer`（`PluginHealthStatus::getDeductionRules()` を使用）

### 健全性ステータスのしきい値

| スコア範囲 | ステータス        | 表示ラベル  |
|-----------|------------------|------------|
| 90 – 100  | `Healthy`        | 良好       |
| 70 – 89   | `Advisory`       | 注意       |
| 0 – 69    | `NeedsAttention` | 要確認     |

一度もスキャンされていない拡張機能は採点されず、ステータスは `NotVerified` になります。

### 有効化時のアクション判定

判定は `ExtensionEnableActionResolver` が行い、次の順に確認します。

1. 有効なプリセットが署名を必須とし、拡張機能に検証済みの署名が無いときは **Blocked**（未署名・無効・期限切れ・不明な鍵・検証エラー・検証待ちはどれも不合格）
2. 健全性ステータスが、その種類の拡張機能にプリセットが許す上限（下の表）を超えるときは **Blocked**。ステータスの順は `Healthy` < `Advisory` < `NeedsAttention` < `NotVerified`
3. それ以外はスコアで決まる:

| 結果                       | アクション                 |
|---------------------------|--------------------------|
| 90以上                     | 許可（警告なし）            |
| 70 – 89                   | 警告ダイアログの表示が必要  |
| 70未満、または重大な問題あり | 確認承認が必要             |

| プリセット           | 署名必須   | 上限ステータス（プラグイン） | 上限ステータス（テーマ） |
|--------------------|-----------|--------------------------|----------------------|
| Strict             | はい       | `Healthy`                | `Healthy`            |
| Balanced（既定）    | いいえ     | `Advisory`               | `NeedsAttention`     |
| Development        | 常に不要   | 上限なし                   | 上限なし              |
| Custom             | 設定による | 設定による（既定 `Advisory`） | 設定による（既定 `Advisory`） |

そのため既定の Balanced では、ステータスが `NeedsAttention`（70 点未満か重大な問題あり）または `NotVerified` のプラグインは Blocked になり、`NeedsAttention` のテーマは確認承認が必要になります。

この判定が働くのは、管理画面での拡張機能のインストールと有効化、管理画面でのテーマの切り替え、更新コマンド（`dls:plugin:update` / `dls:theme:update`）です。更新コマンドは、新しい版が Blocked と判定されると前の版に戻します。

### 減点ルール

ルールキーは `PluginHealthScorer` が出す指摘の種類です。テーマは `ThemeHealthScorer` が同じ表を使い、その一部の確認だけを行います。

#### 署名

| ルールキー                         | 減点    | 説明                                    |
|----------------------------------|---------|----------------------------------------|
| `signature_unsigned`             | -10     | 署名されていない                         |
| `signature_invalid`              | **-50** | 署名が検証できない（ファイルの変更か鍵の違い）— 重大 |
| `signature_pending_verification` | -5      | 署名はあるが、まだ検証できていない          |
| `signature_unknown_key`          | -15     | コアが知らない鍵で署名されている            |
| `signature_expired`              | -20     | 署名の有効期限が切れている                  |
| `signature_error`                | -10     | 検証がエラーで失敗した                      |

#### 権限

| ルールキー                         | 減点    | 説明                                    |
|----------------------------------|---------|----------------------------------------|
| `permission_undeclared_minor`    | -5      | 宣言していない権限をコードが使っている      |
| `permission_undeclared_major`    | **-15** | 同じく、高リスクの権限（`database.core_tables_write`、`members.write`、`members.delete`、`system.modify_routes`）— 重大 |
| `permission_unused`              | -2      | 宣言されているがコードに見つからない権限（`_optional` の権限には適用しない） |
| `permission_undefined`           | -10     | マニフェストに `permissions` セクションが無い |

#### 宣言された高リスク権限（プラグイン）

| ルールキー                          | 減点 | 説明                                           |
|-----------------------------------|------|-----------------------------------------------|
| `risk_public_uploads_own_dir`     | -2   | `storage.own_directory` 付きの `storage.public_uploads` |
| `risk_public_uploads_no_own_dir`  | -4   | `storage.own_directory` 無しの `storage.public_uploads` |
| `risk_members_delete`             | -4   | `members.delete` を宣言                        |
| `risk_mail_bulk_send`             | -3   | `mail.bulk_send` を宣言                        |

#### CSP（コンテンツセキュリティポリシー）

| ルールキー                      | 減点    | 説明                                    |
|--------------------------------|---------|----------------------------------------|
| `csp_inline_js_required`       | -10     | インラインJavaScriptが必要               |
| `csp_inline_css_required`      | -5      | インラインCSSが必要                      |
| `csp_violation_standard`       | -5      | CSPモードが標準のときにインラインのコードが必要 |
| `csp_violation_strict`         | -15     | CSPモードが厳格のときにインラインのコードが必要 |

CSPモードが開発のときは、違反の減点はありません。

#### スキャン鮮度

| ルールキー              | 減点    | 説明                                    |
|------------------------|---------|----------------------------------------|
| `scan_outdated`        | -5      | 最後のスキャンが30日より前                 |
| `scan_not_performed`   | -10     | スキャンが未実施                          |

#### 危険なAPI

| ルールキー              | 減点    | 説明                                         |
|------------------------|---------|----------------------------------------------|
| `dangerous_api_exec`   | **-30** | プロセス実行（`exec`、`shell_exec` など）や `env()` の直接利用といった危険な API を検出 — 重大 |

#### 拡張機能 API のバージョン（`requires.dixlase_api`）

| ルールキー                   | 減点 | 説明                                  |
|----------------------------|------|--------------------------------------|
| `missing_api_version`      | -5   | API バージョンの制約が宣言されていない   |
| `incompatible_api_version` | -15  | 制約がこのコアに合わない               |
| `malformed_api_constraint` | -10  | 制約を解釈できない                     |

#### ライセンス（プラグイン）

| ルールキー              | 減点    | 説明                                    |
|------------------------|---------|----------------------------------------|
| `missing_license`      | -10     | `license` フィールドが無い               |
| `invalid_license_spdx` | -5      | 有効な SPDX 識別子ではない               |
| `unknown_license`      | -3      | 受け入れるライセンスの表に無い            |
| `license_refused`      | **-25** | 拒否リストにある — 重大                  |

この値はサイトごとに `config/licensing.php`（`health_deductions`）で上書きできます。

#### サプライチェーンメタデータ（プラグイン）

| ルールキー                     | 減点 | 説明                                                                          |
|--------------------------------|------|-------------------------------------------------------------------------------|
| `missing_author_id`            | -3   | `plugin.json` に `author_id` が無い                                            |
| `missing_authority_key_id`     | -3   | `plugin.json` に `authority_key_id` が無い                                     |

両フィールドはサプライチェーン攻撃防御フローで必須（
[プラグイン作者向けサプライチェーンメタデータ](supply-chain-metadata.md) と
[サプライチェーン防御データ層](../supply-chain.md) を参照）。これらが欠落
していると、比較対象となる「以前の正規所有者」が無いため、バージョン履歴の
変更検出フラグが機能しない（hijack 検出ができない）。

### 重大な問題（クリティカルイシュー）

以下の問題が検出された場合、スコアに関係なく `NeedsAttention`（要確認）になります:

| クリティカルイシューキー          | 説明                             |
|---------------------------------|----------------------------------|
| `signature_invalid`             | 署名検証の失敗                    |
| `dangerous_api_exec`            | 危険な API の検出                 |
| `permission_undeclared_major`   | 宣言していない高リスクの権限        |
| `license_refused`               | 拒否リストにあるライセンス          |

---

## 3. スコアリングフロー

```
┌─────────────────────────────┐
│  未スキャン → NotVerified    │
├─────────────────────────────┤
│  基本点: 100                │
├─────────────────────────────┤
│  1. 署名検証                │  → 減点適用
│  2. 権限整合性              │  → 減点適用
│  3. CSP適合性               │  → 減点適用
│  4. 危険API検出             │  → 減点適用
│  5. スキャン鮮度            │  → 減点適用
│  6. 宣言された高リスク権限   │  → 減点適用
│  7. サプライチェーンメタデータ│  → 減点適用
│  8. ライセンス              │  → 減点適用
│  9. API バージョン          │  → 減点適用
├─────────────────────────────┤
│  最終スコア = max(0, 合計)   │
│  + クリティカルイシュー判定  │
└─────────────────────────────┘
```

## 4. 関連ファイル

| ファイル | 説明 |
|---------|------|
| `app/Services/Plugin/PluginHealthScorer.php` | 健全性スコア計算 |
| `app/Enums/PluginHealthStatus.php` | ステータスしきい値と減点ルール |
| `app/Services/Extension/ExtensionEnableActionResolver.php` | 有効化時のアクション（Allowed / Warning / Acknowledge / Blocked） |
| `app/Enums/ExtensionSecurityPreset.php` | プリセットの既定値（署名の要否、上限ステータス） |
| `app/Services/Plugin/PluginPermissionService.php` | プラグインリスクスコア |
| `app/Services/Theme/ThemePermissionService.php` | テーマリスクスコア |
| `app/Contracts/Plugin/PluginPermissionServiceInterface.php` | サービスコントラクト |
