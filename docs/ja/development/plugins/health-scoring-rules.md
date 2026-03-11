# プラグイン・テーマ 健全性スコアリングルール

> **[English version](../../../development/plugins/health-scoring-rules.md)**

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

### 有効化時のアクション判定

| スコア範囲       | 必要なアクション           |
|-----------------|--------------------------|
| 90以上           | 許可（警告なし）           |
| 70 – 89         | 警告ダイアログの表示が必要  |
| 70未満           | 確認承認が必要             |
| 重大な問題あり    | 確認承認が必要             |

### 減点ルール

#### 署名

| ルールキー                | 減点    | 説明                                    |
|--------------------------|---------|----------------------------------------|
| `signature.unsigned`     | -5      | プラグインが署名されていない              |
| `signature.unsigned_production` | -15 | 本番環境で未署名                       |
| `signature.invalid`      | **-50** | 署名が無効（改ざんの可能性）              |
| `signature.mismatch`     | **-50** | 署名とファイルが一致しない                |

#### 権限

| ルールキー                          | 減点    | 説明                                    |
|------------------------------------|---------|----------------------------------------|
| `permissions.undeclared_minor`     | -5      | 軽微な未宣言権限の使用                   |
| `permissions.undeclared_major`     | **-15** | 重大な未宣言権限の使用                   |
| `permissions.unused`               | -2      | 宣言されているがコードで未使用の権限       |
| `permissions.undefined`            | -10     | JSONに権限定義がない                     |

#### CSP（コンテンツセキュリティポリシー）

| ルールキー                      | 減点    | 説明                                    |
|--------------------------------|---------|----------------------------------------|
| `csp.violation_dev`            | 0       | CSP違反（開発環境のみ）                   |
| `csp.violation_standard`       | -5      | 標準モードでのCSP違反                     |
| `csp.violation_strict`         | -15     | 厳格モードでのCSP違反                     |
| `csp.inline_js_required`       | -10     | インラインJavaScriptが必要               |
| `csp.inline_css_required`      | -5      | インラインCSSが必要                      |
| `csp.external_resources`       | -3      | 外部リソースを使用                       |

#### スキャン鮮度

| ルールキー              | 減点    | 説明                                    |
|------------------------|---------|----------------------------------------|
| `scan.outdated`        | -5      | スキャンが30日以上前                      |
| `scan.not_performed`   | -10     | スキャンが未実施                          |

#### 危険なAPI

| ルールキー                   | 減点    | 説明                                         |
|-----------------------------|---------|----------------------------------------------|
| `dangerous_api.exec`        | **-30** | プロセス実行関数の使用（exec, shell_exec 等）   |
| `dangerous_api.env_access`  | -20     | 環境変数への直接アクセス                        |

#### ファイルスコープ

| ルールキー              | 減点    | 説明                                    |
|------------------------|---------|----------------------------------------|
| `file.outside_scope`   | -20     | プラグインディレクトリ外でのファイル操作    |

### 重大な問題（クリティカルイシュー）

以下の問題が検出された場合、スコアに関係なく即座に `NeedsAttention`（要確認）になります：

| クリティカルイシューキー          | 説明                             |
|---------------------------------|----------------------------------|
| `signature_invalid`             | 署名検証の失敗                    |
| `signature_mismatch`            | 署名とファイルの不一致            |
| `dangerous_api_exec`            | プロセス実行関数の使用            |
| `permission_undeclared_major`   | 重大な未宣言権限の使用            |

---

## 3. スコアリングフロー

```
┌─────────────────────────────┐
│  基本点: 100                │
├─────────────────────────────┤
│  1. 署名検証                │  → 減点適用
│  2. 権限整合性              │  → 減点適用
│  3. CSP適合性               │  → 減点適用
│  4. 危険API検出             │  → 減点適用
│  5. スキャン鮮度            │  → 減点適用
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
| `app/Services/Plugin/PluginPermissionService.php` | プラグインリスクスコア |
| `app/Services/Theme/ThemePermissionService.php` | テーマリスクスコア |
| `app/Contracts/Plugin/PluginPermissionServiceInterface.php` | サービスコントラクト |
