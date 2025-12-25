# プラグイン権限基盤ガイドライン

## 概要

Dixlaseのプラグイン・テーマ権限基盤は、拡張機能の**健全性（Health）**と**信頼度（Trust）**を分離して評価するシステムです。開発者に対して「危険」ではなく「状態をお知らせします」という優しいニュアンスで、透明性と安全性を両立します。

## 基本概念

### Health（健全性）と Trust（信頼度）の違い

| 概念 | 意味 | 評価対象 |
|------|------|----------|
| **Health（健全性）** | 中身の整合性・状態 | 宣言と実態の一致、署名の有効性、CSP適合性 |
| **Trust（信頼度）** | 出どころ・供給経路の確からしさ | 署名鍵の発行元、配布経路、作者の認証 |

**例:**
- Trust: Official / Health: NeedsAttention → 公式でも改ざん疑い
- Trust: Local / Health: Healthy → 自作でも整合性OK

---

## 健全性ステータス（PluginHealthStatus）

### ✅ 健全（Healthy）

**条件:**
- 宣言（plugin.json）と実態（スキャン）が一致
- 署名が有効（または開発モードで未署名許容）
- 権限外の操作・疑わしいAPIが検出されない
- ファイル配置が規約に沿っている
- CSP適合（または開発モード）

**スコア:** 90-100点

**表示例:**
```
健全性チェック：健全
宣言された権限と検出された利用状況に不一致はありません。
署名検証：OK
最終スキャン：2025-12-25 19:40（scanner v1.3.0）
```

---

### ⚠️ 注意（Advisory）

**条件:**
- 軽微な不足/過剰な権限宣言
- 軽い "逸脱" の可能性がある実装
- 署名がないが「開発モード」で許容
- スキャンが古い（scanner_versionが古い／未スキャン状態）
- CSP違反あり（標準モード）

**スコア:** 70-89点

**表示例:**
```
健全性チェック：注意（見直し推奨）
軽微な指摘が 2件 あります。動作を妨げるものではありませんが、
透明性のため確認を推奨します。

指摘:
- 権限の宣言不足：content.read（src/Services/PostService.php:82）
- 未署名：開発モードでは許可されています（本番配布時は署名を推奨）

推奨アクション:
- plugin.json の permission に content.read を追加するか、該当コードの利用範囲を見直してください。
- 本番環境で配布する場合は署名を付与してください。
```

---

### ❗ 要確認（NeedsAttention）

**条件:**
- 署名不一致 / 改ざん疑い
- 権限が実態と大きく不一致（未宣言で強い操作をしている）
- 明確に危険な振る舞いの検出
- CSP違反あり（厳格モード）

**スコア:** 0-69点、または致命的フラグ

**表示例:**
```
健全性チェック：要確認（有効化前に確認してください）
重要な指摘が 1件 あります。現在のポリシーでは、
この状態のまま有効化することは推奨されません。

指摘:
- 署名検証：不一致（改ざんの可能性）

推奨アクション:
- 配布元のZIPを再取得して再インストールしてください。
- 開発中の場合は署名生成・パッケージング工程を確認してください。
```

---

### ❓ 未確認（NotVerified）

**条件:**
- 検証に必要な情報が不足
- permission定義なし
- 署名なし
- スキャン未実行

**表示例:**
```
検証状態：未確認
このプラグインは検証に必要な情報が不足しています。

不足情報:
- スキャン：未実行
- 権限定義：未定義
- 署名：未署名

推奨アクション:
- 再スキャンを実行
- plugin.json に permission を定義
- 署名を付与
```

---

## 信頼度レベル（PluginTrustLevel）

| レベル | 説明 | 本番推奨 |
|--------|------|----------|
| **Official（公式）** | Dixlase公式による配布 | ✅ |
| **Verified（認証済み）** | 認証済みパブリッシャーによる配布 | ✅ |
| **Partner（パートナー）** | Dixlaseパートナーによる配布 | ✅ |
| **Community（コミュニティ）** | 未認証の配布者 | ⚠️ |
| **Local（ローカル）** | 手動インストール/ローカル開発 | ⚠️ |

---

## 検証状態（PluginVerificationStatus）

### 署名ステータス

| ステータス | 説明 |
|------------|------|
| `signature_valid` | 署名：OK |
| `signature_unsigned` | 署名：未署名 |
| `signature_invalid` | 署名：不一致（改ざんの可能性） |
| `signature_pending` | 署名：検証待ち |

### 権限定義ステータス

| ステータス | 説明 |
|------------|------|
| `permission_ok` | 権限定義：OK |
| `permission_undefined` | 権限定義：未定義 |
| `permission_mismatch` | 権限定義：不一致 |

### CSP適合性ステータス

| ステータス | 説明 |
|------------|------|
| `csp_ready` | CSP Ready - 完全対応 |
| `csp_compatible` | CSP互換 - nonce付きで動作 |
| `csp_inline_required` | インラインJS必須 - 厳格モードで動作不可 |
| `csp_not_checked` | CSP未検証 |

---

## 点数化ルール

### 初期スコア: 100点

### 減点ルール

| 項目 | 減点 |
|------|------|
| **署名関連** | |
| 未署名 | -5 |
| 署名不一致 | -50（致命的） |
| **権限関連** | |
| 軽微な未宣言権限 | -5 |
| 重大な未宣言権限 | -15（致命的） |
| 未使用の権限宣言 | -2 |
| 権限未定義 | -10 |
| **CSP関連** | |
| CSP違反（開発モード） | 0 |
| CSP違反（標準モード） | -5 |
| CSP違反（厳格モード） | -15 |
| インラインJS必須 | -10 |
| **スキャン関連** | |
| スキャン期限切れ | -5 |
| スキャン未実行 | -10 |
| **危険なAPI** | |
| exec/shell_exec等 | -30（致命的） |
| .env参照 | -20 |
| **ファイル配置** | |
| プラグイン領域外への参照 | -20 |

### スコアから健全性への変換

| スコア | 健全性 |
|--------|--------|
| 90-100 | Healthy（健全） |
| 70-89 | Advisory（注意） |
| 0-69 | NeedsAttention（要確認） |
| 致命的フラグあり | NeedsAttention（即座に） |

---

## plugin.json の拡張仕様

### 現在のフォーマット

```json
{
  "name": "Dixlase Pages",
  "package_name": "dixlase/dixlase-pages",
  "slug": "dixlase-pages",
  "version": "1.0.0",
  "description": {
    "ja": "固定ページ管理機能を提供するプラグインです。",
    "en": "This plugin provides static page management functionality."
  },
  "author": "exc-D inc.",
  "email": "office@exc-d.com",
  "url": "https://exc-d.com",
  "license": "GPL-3.0",
  "namespace": "Plugins\\DixlasePages",
  "type": "dixlase-plugin",
  "category": "content",
  "tags": ["pages", "content", "cms"],
  "requires": {
    "dixlase": ">=1.0.0",
    "php": ">=8.0"
  },
  "provides": {
    "admin_menu": true,
    "front_routes": true,
    "api_routes": false,
    "settings_page": true
  }
}
```

### 拡張フィールド（推奨）

```json
{
  "permissions": {
    "declared": [
      "content.pages.read",
      "content.pages.write",
      "admin.menu.register",
      "settings.plugin.read",
      "settings.plugin.write"
    ],
    "optional": [
      "mail.send"
    ],
    "notes": {
      "ja": [
        "mail.send は通知機能を有効にした場合のみ使用します。"
      ],
      "en": [
        "mail.send is only used when notification feature is enabled."
      ]
    }
  },

  "security": {
    "sandbox": {
      "storage_scope": "plugin",
      "storage_root": "plugins/dixlase-pages",
      "db_scope": "plugin_tables_only",
      "config_scope": "plugin_settings_only"
    },
    "capabilities": {
      "file_write": ["plugin_storage"],
      "file_read": ["plugin_storage"],
      "network_access": [],
      "exec_process": false
    }
  },

  "verification": {
    "signature": {
      "required": true,
      "file": "signature.sig",
      "algorithm": "ed25519",
      "public_key_id": "exc-d-official-2025"
    },
    "permission_policy": {
      "mode": "strict",
      "allow_undeclared": false
    }
  },

  "publisher": {
    "trust_level": "official",
    "publisher_id": "exc-d-inc",
    "website": "https://exc-d.com"
  },

  "csp": {
    "requires_inline_js": false,
    "requires_inline_css": false,
    "external_scripts": [],
    "external_styles": [
      "https://fonts.googleapis.com"
    ]
  }
}
```

### フィールド説明

#### permissions

| フィールド | 説明 |
|------------|------|
| `declared` | 宣言する権限（スキャン対象の基準） |
| `optional` | 機能ON時だけ使う権限（注意の誤判定を減らす） |
| `notes` | 権限使用の補足説明（多言語対応） |

#### security.sandbox

| フィールド | 説明 |
|------------|------|
| `storage_scope` | ストレージスコープ（plugin/shared/global） |
| `storage_root` | ストレージルートパス |
| `db_scope` | DBスコープ（plugin_tables_only/core_read/core_write） |
| `config_scope` | 設定スコープ（plugin_settings_only/core_read/core_write） |

#### security.capabilities

| フィールド | 説明 |
|------------|------|
| `file_write` | 書き込み可能な領域 |
| `file_read` | 読み取り可能な領域 |
| `network_access` | 外部通信先 |
| `exec_process` | プロセス実行の可否 |

#### verification

| フィールド | 説明 |
|------------|------|
| `signature.required` | 署名必須かどうか |
| `signature.file` | 署名ファイル名 |
| `signature.algorithm` | 署名アルゴリズム |
| `permission_policy.mode` | 権限ポリシーモード（strict/balanced/permissive） |
| `permission_policy.allow_undeclared` | 未宣言権限を許可するか |

#### publisher

| フィールド | 説明 |
|------------|------|
| `trust_level` | 信頼度レベル（official/verified/partner/community/local） |
| `publisher_id` | パブリッシャーID |
| `website` | パブリッシャーWebサイト |

#### csp

| フィールド | 説明 |
|------------|------|
| `requires_inline_js` | インラインJS必須かどうか |
| `requires_inline_css` | インラインCSS必須かどうか |
| `external_scripts` | 外部スクリプトURL |
| `external_styles` | 外部スタイルURL |

---

## 権限命名規約

### カテゴリ

| カテゴリ | 説明 |
|----------|------|
| `content.*` | コンテンツ関連 |
| `database.*` | データベース関連 |
| `storage.*` | ストレージ関連 |
| `settings.*` | 設定関連 |
| `members.*` | メンバー関連 |
| `mail.*` | メール関連 |
| `system.*` | システム関連 |
| `admin.*` | 管理画面関連 |

### 操作

| 操作 | 説明 |
|------|------|
| `.read` | 読み取り |
| `.write` | 書き込み |
| `.create` | 作成 |
| `.delete` | 削除 |
| `.register` | 登録 |

### 例

```
content.pages.read      # 固定ページの読み取り
content.pages.write     # 固定ページの書き込み
database.own_tables     # 専用テーブルへのアクセス
database.core_tables    # コアテーブルへのアクセス
storage.plugin_storage  # プラグイン専用ストレージ
storage.public_uploads  # 公開アップロード領域
settings.plugin.read    # プラグイン設定の読み取り
settings.core.read      # コア設定の読み取り
members.read            # メンバー情報の読み取り
members.write           # メンバー情報の書き込み
mail.send               # メール送信
mail.bulk_send          # 一括メール送信
system.register_middleware  # ミドルウェア登録
admin.menu.register     # 管理メニュー登録
```

---

## セキュリティ設定との連携

### プリセットモード

| モード | 署名 | 権限定義 | 最大健全性レベル | CSP |
|--------|------|----------|------------------|-----|
| **Strict（厳格）** | 必須 | 必須 | Healthy のみ | 厳格モード |
| **Balanced（バランス）** | 推奨 | 推奨 | Advisory まで | 標準モード |
| **Development（開発）** | 不要 | 不要 | NotVerified まで | 開発モード |

### CSPモードとの連携

| CSPモード | 動作 | プラグイン制限 |
|-----------|------|----------------|
| **開発モード** | 違反をログに記録、ブロックしない | なし |
| **標準モード** | nonce付きインラインのみ許可 | `requires_inline_js: true` は警告表示 |
| **厳格モード** | インライン完全禁止 | `requires_inline_js: true` は有効化不可 |

---

## 管理画面での表示

### プラグイン一覧のチップ

```
[健全性] 健全 / 注意 / 要確認 / 未確認
[信頼度] 公式 / 認証済み / パートナー / コミュニティ / ローカル
[検証]   署名：OK / 未署名 / 不一致
         権限定義：OK / 未定義 / 不一致
         CSP：Ready / 互換 / インラインJS必須
[スキャン] 最終スキャン：2025-12-25 19:40
```

### ブロック時のメッセージ

```
このプラグインは現在のセキュリティ設定では有効化できません

健全性チェックで「要確認」と判定されました。
セキュリティ設定で許可範囲を変更するか、
指摘事項を解消して再スキャンしてください。

[詳細を確認] [セキュリティ設定へ] [キャンセル]
```

---

## ベストプラクティス

### プラグイン開発者向け

1. **plugin.json に permissions を定義する**
   - 使用する権限を明示的に宣言
   - optional で条件付き権限を分離

2. **署名を付与する**
   - 本番配布時は必ず署名
   - 改ざん検知と信頼性向上

3. **CSP Ready を目指す**
   - `@cspNonce` ディレクティブを使用
   - インラインスクリプトを最小限に

4. **定期的にスキャンを実行**
   - コード変更後は再スキャン
   - 権限宣言と実態の一致を確認

### サイト管理者向け

1. **本番環境では Balanced 以上を使用**
   - Development モードは開発時のみ

2. **定期的にログを確認**
   - CSP違反ログ
   - 権限監査ログ

3. **信頼できるソースからのみインストール**
   - Official / Verified を優先
   - Community / Local は慎重に

---

## 関連ドキュメント

- [CSP使用ガイド](csp-usage.md)
- [プラグイン開発ガイド](plugin-development.md)
- [セキュリティ設定ガイド](security-settings.md)
