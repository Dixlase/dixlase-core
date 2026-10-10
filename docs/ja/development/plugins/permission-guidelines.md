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

主な減点（ルールキー付きの全体の表は [健全性スコアリングルール](health-scoring-rules.md) にあります）:

| 項目 | 減点 |
|------|------|
| **署名関連** | |
| 未署名 | -10 |
| 無効な署名（ファイルの変更か鍵の違い） | -50（致命的） |
| **権限関連** | |
| 軽微な未宣言権限 | -5 |
| 重大な未宣言権限 | -15（致命的） |
| 未使用の権限宣言 | -2 |
| 権限未定義 | -10 |
| **CSP関連** | |
| インラインのコードが必要（CSP標準モード） | -5 |
| インラインのコードが必要（CSP厳格モード） | -15 |
| インラインJS必須 | -10 |
| インラインCSS必須 | -5 |
| **スキャン関連** | |
| スキャンが30日より前 | -5 |
| スキャン未実行 | -10 |
| **危険なAPI** | |
| 危険な API の検出（`exec`/`shell_exec`、`env()` の直接利用など） | -30（致命的） |
| **ライセンス** | |
| 拒否リストにあるライセンス | -25（致命的） |

### スコアから健全性への変換

| スコア | 健全性 |
|--------|--------|
| 90-100 | Healthy（健全） |
| 70-89 | Advisory（注意） |
| 0-69 | NeedsAttention（要確認） |
| 致命的フラグあり | NeedsAttention（即座に） |

---

## ハイブリッド設計（plugin.json と config の役割分担）

プラグインの設定は2つの層に分離されています:

### plugin.json（マニフェスト - 静的宣言）

インストール前に知りたい情報。JSONで記述。

| セクション | 用途 |
|-----------|------|
| メタデータ | name, slug, version, description, author, license |
| 依存関係 | requires |
| 機能宣言 | provides (admin_menu, front_routes 等) |
| 権限宣言 | permissions (プラグインがシステムの何に触れるか) |
| 構成宣言 | declares (どの config ファイルを提供しているか) |

### config/（運用設定 - PHPベース）

ランタイム動作の制御。PHP enum参照や複雑なデータ構造が必要。

| ファイル | 用途 |
|---------|------|
| `admin/navigation.php` | メニュー構成 |
| `admin/roles.php` | メンバーロールのデフォルト値（MemberRole enum参照） |
| `admin/database-cleanup.php` | クリーンアップ対象テーブル |

**roles / database-cleanup を PHP config で維持する理由:**
- PHP enum参照 (`MemberRole::EDITOR->value`) が必要
- 複雑なデータ構造 (`additional_conditions` 等)
- 既存の読み込み基盤 (`PluginLoaderTrait`, `PermissionRegistry`, `DatabaseCleanupService`) が稼働中

---

## 2種類の「権限」の区別

| 種類 | 定義場所 | 方向 | 例 |
|------|---------|------|-----|
| **プラグイン権限** | `plugin.json` permissions | プラグイン → システム | 「メンバーデータを読める」 |
| **メンバーロール権限** | `config/roles.php` | メンバー → プラグイン | 「編集者以上がページ管理を使える」 |

この2つを混同しないこと。プラグイン権限は「このプラグインがシステムの何にアクセスするか」の宣言であり、メンバーロール権限は「誰がこのプラグインの機能を使えるか」の制御です。

---

## plugin.json の拡張仕様

### 基本フォーマット

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
  "email": "info@dixlase.org",
  "url": "https://exc-d.com",
  "license": "GPL-3.0",
  "namespace": "Plugins\\DixlasePages",
  "type": "dixlase-plugin",
  "category": "content",
  "tags": ["pages", "content", "cms"],
  "requires": {
    "dixlase": ">=1.0.0",
    "php": ">=8.3"
  },
  "provides": {
    "admin_menu": true,
    "front_routes": true,
    "api_routes": false,
    "settings_page": true
  }
}
```

### permissions セクション（必須）

カテゴリ別構造で、全項目を明示的に `true`/`false` で記載します。

```json
{
  "permissions": {
    "database": {
      "own_tables": true,
      "core_tables_read": [],
      "core_tables_write": []
    },
    "storage": {
      "own_directory": true,
      "public_uploads": false,
      "temp_files": false
    },
    "settings": {
      "read_core": true,
      "write_own": true
    },
    "members": {
      "read": false,
      "write": false,
      "create": false,
      "delete": false
    },
    "mail": {
      "send": false,
      "bulk_send": false
    },
    "content": {
      "read_other_plugins": [],
      "write_other_plugins": []
    },
    "system": {
      "register_shortcodes": false,
      "register_middleware": false,
      "register_commands": false,
      "register_blade_directives": false,
      "modify_routes": false
    },
    "_optional": ["mail.send"],
    "_notes": {
      "ja": "mail.send は通知機能を有効にした場合のみ使用します。",
      "en": "mail.send is only used when notification feature is enabled."
    }
  }
}
```

#### permissions フィールド説明

| カテゴリ | フィールド | 型 | 説明 |
|---------|-----------|------|------|
| `database` | `own_tables` | bool | プラグイン専用テーブルの使用 |
| `database` | `core_tables_read` | array | 読み取りするコアテーブル（例: `["members", "settings"]`） |
| `database` | `core_tables_write` | array | 書き込みするコアテーブル（例: `["members"]`） |
| `storage` | `own_directory` | bool | プラグイン専用ストレージの使用 |
| `storage` | `public_uploads` | bool | 公開アップロード領域へのアクセス |
| `storage` | `temp_files` | bool | 一時ファイルの使用 |
| `settings` | `read_core` | bool | コア設定の読み取り |
| `settings` | `write_own` | bool | プラグイン設定の書き込み |
| `members` | `read` | bool | メンバー情報の読み取り |
| `members` | `write` | bool | メンバー情報の書き込み |
| `members` | `create` | bool | メンバーの作成 |
| `members` | `delete` | bool | メンバーの削除 |
| `mail` | `send` | bool | メール送信 |
| `mail` | `bulk_send` | bool | 一括メール送信 |
| `content` | `read_other_plugins` | array | 読み取りアクセスする他プラグインのスラッグ |
| `content` | `write_other_plugins` | array | 書き込みアクセスする他プラグインのスラッグ |
| `system` | `register_shortcodes` | bool | ショートコードの登録 |
| `system` | `register_middleware` | bool | ミドルウェアの登録 |
| `system` | `register_commands` | bool | Artisan コマンドの登録 |
| `system` | `register_blade_directives` | bool | Blade ディレクティブの登録 |
| `system` | `modify_routes` | bool | ルートの変更 |
| - | `_optional` | array | スキャナーヒント: 未検出でもペナルティなし |
| - | `_notes` | object | 権限使用理由の説明（`ja`/`en` バイリンガル） |

### 実行時に権限が果たす役割

`permissions` セクションは**宣言**です。コアはこれを次の用途に使います。

- 静的解析: スキャナーが宣言とコードの動きを比べる
- 健全性スコア: 宣言していない権限や使っていない権限を減点する
- 管理画面の表示: 拡張機能ごとの権限とリスクの概要

コアは、この宣言にもとづいてプラグインのコードの動きを制限**しません**。プラグインはコアと同じ PHP プロセスで動き、PHP でできることは何でも呼べます。コアが実行時に宣言を確認するのは、コア自身がプラグインの機能を解決する次の場所だけです。

| 場所 | 確認する権限 | 宣言が無いとき |
|------|-------------|---------------|
| `PluginServiceResolver` が `MailCapableInterface` の実装を解決するとき | `mail.send` | その機能は解決されない |
| 個人データのエクスポート（`UserPrivacyExporter`） | `privacy.export` | そのプラグインのエクスポーターは飛ばされる |
| 個人データの削除（`UserPrivacyEraser`） | `privacy.delete` | そのプラグインの削除処理は飛ばされる |

プライバシーの権限は [プライバシー](privacy.md) を参照してください。

### declares セクション（必須）

プラグインが提供する config ファイルと構成要素を宣言します。

```json
{
  "declares": {
    "configs": {
      "roles": true,
      "database_cleanup": false,
      "navigation": true
    },
    "contracts": [],
    "migrations": true,
    "commands": false,
    "middleware": false
  }
}
```

#### declares フィールド説明

| フィールド | 型 | 説明 |
|-----------|------|------|
| `configs.roles` | bool | `config/admin/roles.php` を提供するか |
| `configs.database_cleanup` | bool | `config/admin/database-cleanup.php` を提供するか |
| `configs.navigation` | bool | `config/admin/navigation.php` を提供するか |
| `contracts` | array | 実装する Contract インターフェースの配列 |
| `migrations` | bool | マイグレーションを提供するか |
| `commands` | bool | Artisan コマンドを提供するか |
| `middleware` | bool | ミドルウェアを提供するか |

**整合性チェック:**
- 宣言あり + ファイルなし → 健全性減点(-5)
- ファイルあり + 宣言なし → 健全性減点(-2)

### 予約フィールド（コアは読まない）

以下のフィールドは**予約**です。現在コアのどこもこれらを読まないため、宣言しても効果はありません。ストレージ・データベース・設定・ファイル・ネットワーク・プロセスへのアクセスを制限することはなく、`permission_policy` も未宣言の権限の扱いを変えません。名前を別の用途に使わないために載せているだけです。

実際の `csp` マニフェストブロックは別の形です（[CSPガイド](../../operations/security/csp-guide.md) を参照）。ここに示した `csp` のキーも読まれません。

```json
{
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

#### security.sandbox（予約・未実装）

| フィールド | 説明 |
|------------|------|
| `storage_scope` | ストレージスコープ（plugin/shared/global） |
| `storage_root` | ストレージルートパス |
| `db_scope` | DBスコープ（plugin_tables_only/core_read/core_write） |
| `config_scope` | 設定スコープ（plugin_settings_only/core_read/core_write） |

#### security.capabilities（予約・未実装）

| フィールド | 説明 |
|------------|------|
| `file_write` | 書き込み可能な領域 |
| `file_read` | 読み取り可能な領域 |
| `network_access` | 外部通信先 |
| `exec_process` | プロセス実行の可否 |

#### verification（予約・未実装）

| フィールド | 説明 |
|------------|------|
| `signature.required` | 署名必須かどうか |
| `signature.file` | 署名ファイル名 |
| `signature.algorithm` | 署名アルゴリズム |
| `permission_policy.mode` | 権限ポリシーモード（strict/balanced/permissive） |
| `permission_policy.allow_undeclared` | 未宣言権限を許可するか |

#### publisher（予約・読まれない）

| フィールド | 説明 |
|------------|------|
| `trust_level` | 信頼度レベル（official/verified/partner/community/local） |
| `publisher_id` | パブリッシャーID |
| `website` | パブリッシャーWebサイト |

#### csp（上のキーは読まれない）

| フィールド | 説明 |
|------------|------|
| `requires_inline_js` | インラインJS必須かどうか |
| `requires_inline_css` | インラインCSS必須かどうか |
| `external_scripts` | 外部スクリプトURL |
| `external_styles` | 外部スタイルURL |

#### 受け付ける CSP の値

拡張機能が足す CSP の値は、マニフェストの `csp`、`CspPolicyProvider`、
`csp_add_directive()`、`RegistersCspPolicy` のどこから来たものでも、ポリシーに入る前に
コアが確かめる(`App\Services\Csp\CspSourceValidator`、#494)。サイト全体のポリシーを
ゆるめる値は捨て、警告としてログに書き、マニフェストの値は管理画面の拡張機能のカードにも
出す。管理者自身の CSP の設定には影響しない。

| 受け付けるもの | 例 |
|----------------|----|
| ホスト(http・https・ws・wss。スキームは省略可) | `https://cdn.example.com`、`cdn.example.com`、`wss://ws.example.com:8443` |
| 登録可能なドメインのサブドメインのワイルドカード | `https://*.example.com` |
| `'self'` | |
| `'nonce'` のプレースホルダーと `'sha256-…'` / `'sha384-…'` / `'sha512-…'` のハッシュ | script-src・script-src-elem・style-src・style-src-elem だけ |
| `data:` | img-src・font-src・media-src だけ |
| `blob:` | img-src・media-src・worker-src だけ |

受け付けないもの: `'unsafe-inline'`、`'unsafe-eval'`、`'unsafe-hashes'`、
`'wasm-unsafe-eval'`、`'strict-dynamic'`(CSP のモードで決まる)、`'none'`、
固定の `'nonce-…'`、`*` だけ、`https://*`、`*.com`、script-src の `https:` や `data:` の
ようなスキームだけの値、空白・`;`・`,` を含む値。

拡張機能が値を足せるディレクティブは script-src、script-src-elem、style-src、
style-src-elem、img-src、font-src、connect-src、media-src、frame-src、child-src、
worker-src、manifest-src、form-action。それ以外(`frame-ancestors`、`base-uri`、
`object-src`、`default-src`、`script-src-attr`、`report-uri` など)は受け付けない。

---

## 権限カテゴリ

permissions のカテゴリ別構造に対応するスキャン対象:

| カテゴリ | スキャン対象 | 説明 |
|----------|-------------|------|
| `database.own_tables` | マイグレーション、Schema::create | プラグイン専用テーブル |
| `database.core_tables_read` | コアモデルの use/参照 | コアテーブルの読み取り |
| `database.core_tables_write` | コアモデルの save/create/update/delete | コアテーブルへの書き込み |
| `storage.own_directory` | Storage facade の使用 | プラグイン専用ストレージ |
| `storage.public_uploads` | public ディスクへの書き込み | 公開アップロード領域 |
| `storage.temp_files` | temp ファイル操作 | 一時ファイル |
| `settings.read_core` | config() でコア設定を読む | コア設定の読み取り |
| `settings.write_own` | プラグイン設定の保存 | プラグイン設定の書き込み |
| `members.read/write/create/delete` | Member モデルの操作 | メンバー関連操作 |
| `mail.send` | Mail facade, Mailable | メール送信 |
| `mail.bulk_send` | バッチメール, キュー | 一括メール送信 |
| `content.read_other_plugins` | 他プラグインモデルの参照 | 他プラグインデータの読み取り |
| `content.write_other_plugins` | 他プラグインモデルの更新 | 他プラグインデータの書き込み |
| `system.register_*` | ServiceProvider での登録 | システム拡張ポイント |
| `system.modify_routes` | ルート定義の変更 | ルート変更 |

---

## セキュリティ設定との連携

### プリセットモード

| モード | 署名 | 上限の健全性ステータス（プラグイン / テーマ） |
|--------|------|------------------------------------------|
| **Strict（厳格）** | 必須（検証済みの署名） | Healthy / Healthy |
| **Balanced（バランス、既定）** | 不要 | Advisory / NeedsAttention |
| **Development（開発）** | 常に不要 | 上限なし |
| **Custom（カスタム）** | 設定による | 設定による |

拡張機能が Blocked になるのは、プリセットが署名を必須とし検証済みの署名が無いとき、または健全性ステータスがプリセットの上限を超えるときだけです。それ以外は、スコアに応じて許可、警告、確認承認のいずれかになります。`permissions` セクションが無いこと自体では止まらず、10 点の減点になります。[健全性スコアリングルール](health-scoring-rules.md#有効化時のアクション判定) を参照してください。

この確認が働くのは、管理画面での拡張機能のインストールと有効化、管理画面でのテーマの切り替え、更新コマンドです。

### CSPモードとの連携

| CSPモード | 動作 | インラインのコードが必要な拡張機能への影響 |
|-----------|------|----------------|
| **開発モード** | 違反をログに記録、ブロックしない | 減点なし |
| **標準モード** | nonce付きインラインのみ許可 | 健全性 -5 点 |
| **厳格モード** | インライン完全禁止 | 健全性 -15 点 |

CSPモードが有効化に影響するのは、健全性スコアを通じてだけです。

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
