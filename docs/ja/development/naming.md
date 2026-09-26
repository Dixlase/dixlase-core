# 公開識別子の命名規約

> **対象読者**: Core コントリビューター、プラグイン作者、テーマ作者、API キーを発行する連携先。
>
> **目的**: Dixlase はいくつかの種類の文字列識別子（API スコープ、permission キー、イベント名、Webhook event type、監査ログ action、プラグインケイパビリティ等）を公開しており、それらは下流の利用者にハードコードされる。一度世に出た識別子は安全に改名できない（互換ポリシーは [PLUGIN-API.md](../../../PLUGIN-API.md) を参照）。本ドキュメントは**新規**識別子の命名規約を凍結し、公開面が拡張されても一貫性が保たれるようにするためのもの。
>
> **状態**: 安定（Stable）。本ドキュメントの変更はプラグイン API と同じ semver ポリシーに従う。

## なぜ重要か

識別子の改名は、旧名をハードコードしたすべての利用者をサイレントに壊す。利用者には以下が含まれる:

- `plugin.json` で `permission` キーを参照する**プラグイン**
- イベント / Webhook を名前で購読する**プラグイン**
- 配信時の `event` フィールドでマッチングする**外部システム**
- 失効までスコープ文字列を凍結保持する**発行済み API キー**
- `action` 文字列を保存している**監査ログレコード**

…これらの理由から、識別子名は**実装詳細ではなく公開 API の一部**として扱う。新規識別子は下記の規約に従う必要がある。既存の識別子は grandfathered（[既存の例外](#既存の例外grandfathered)を参照）。

## クイックリファレンス

| 識別子の種類 | フォーマット | 例 |
|---|---|---|
| [API スコープ](#api-スコープ) | `<verb>:<resource>` | `read:content` `write:members` |
| [Permission キー（メニュー）](#permission-キー) | `<area>[.<subarea>].<action>` | `front.index` `settings.base.admin` |
| [イベント名](#イベント名) | `dixlase.<category>.<action>`（過去形） | `dixlase.backup.completed` |
| [Webhook event type](#webhook-event-type) | イベント名と同一 | `dixlase.backup.completed` |
| [監査ログ action](#監査ログ-action) | フラット `<resource>_<action>` snake_case | `password_changed` `login_failed` |
| [プラグインケイパビリティ](#プラグインケイパビリティ) | kebab-case 機能ラベル | `seo-meta` `linkable` |
| [プラグイン permission カテゴリ](#プラグイン-permission-カテゴリ) | 単語 1 つ（lower-case） | `database` `storage` `members` |
| [キャッシュキー](#キャッシュキー) | [cache-key-convention.md](cache-key-convention.md) を参照 | `dixlase:core:routes:list` |
| [設定キー（env）](#設定キー) | `SCREAMING_SNAKE_CASE` | `TRUSTED_PROXIES` `AUDIT_LOG_RETENTION_DAYS` |
| [設定キー（PHP パス）](#設定キー) | `lower.dot.path` | `security.audit_log.retention_days` |

---

## API スコープ

API キーが保持する OAuth 風の権限トークン。キーに凍結保存されるため、改名は発行済み全キーを無効化する。

**フォーマット**: `<verb>:<resource>`

- `<verb>` は `read` または `write`（write は同一リソースの read を含意）。
- `<resource>` は名詞、複数語なら lower_snake_case、コレクションリソースは複数形。
- 定数は `App\Models\ApiKey` に `SCOPE_<VERB>_<RESOURCE>` として定義。

**例**

```php
ApiKey::SCOPE_READ_CONTENT       // 'read:content'
ApiKey::SCOPE_WRITE_TRANSLATIONS // 'write:translations'
ApiKey::SCOPE_READ_AUDIT_LOG     // 'read:audit_log'  （仮想例）
```

**将来の verb**: `write` より強い操作（`delete`、`admin` 等）が必要になった場合は、`write` を拡張するのではなく新しいトップレベル verb として導入する。

---

## Permission キー

`PermissionRegistry::getEffective()` と admin のロール権限 UI を支える階層型 permission。プラグインは `plugin.json` および `permissions` 宣言から参照する。

**フォーマット**: `<area>[.<subarea>][.<deeper>].<action>`

- 各セグメントは lower_snake_case。
- パスは admin ナビ構造（`config/admin.php`）を反映する。
- 末尾セグメントはアクション or サブページ名（`index`、`edit`、`settings`、`password` 等）。
- 複数語のアクションはネストした dot ではなく snake_case を使う: `members.create_edit`（**`members.create.edit` ではない**）。
- プラグイン側の permission キーはプラグインスラグで名前空間を切る: `<plugin_slug>.<area>.<action>`。

**例**

```
front.index
front.edit
front.settings
media.upload
members.create_edit
members.roles
settings.base.admin
settings.security.password
```

**なぜセグメント内で snake_case を使い、ネスト dot を使わないか**: この体系では dot は**メニュー階層**を表すために予約されている。アクション内に dot を使うと「サブエリアである」と「複数語のアクションである」が区別できなくなり、深さの推論ができなくなる。

---

## イベント名

`event(...)` で発火され、`App\Events\DixlaseEvents` に定数として保持される名前。

**フォーマット**: `dixlase.<category>[.<subcategory>].<action>`

- すべて lower-case、dot 区切り。
- `dixlase.` プレフィックスはコア・プラグイン発火イベントすべてに**必須**。プレフィックス無しの名前は Laravel フレームワーク内部用に予約。
- `<action>` は完了状態には過去形（`created`、`installed`、`failed`）、"before" 状態には現在進行形（`-ing`）（`installing`、`creating`）。
- 複数語のアクションは snake_case ではなくさらに dot を使う: `dixlase.url.slug.changed`（**`dixlase.url.slug_changed` ではない**）。
- プラグインイベントは名前空間を切る: `dixlase.<plugin_slug>.<category>.<action>`（例 `dixlase.dixlase_pages.page.published`）。

**ライフサイクルのペア**

"before" と "after" の両方を発火する場合、以下の動詞対を使う:

| 段階 | 動詞形 | 例 |
|---|---|---|
| 開始直前 | `-ing` | `dixlase.plugin.installing` |
| 成功完了 | 過去形 | `dixlase.plugin.installed` |
| 失敗 | `failed` 接尾 | `dixlase.plugin.installation.failed` |

**例**

```php
DixlaseEvents::BACKUP_COMPLETED  // 'dixlase.backup.completed'
DixlaseEvents::PLUGIN_INSTALLING // 'dixlase.plugin.installing'
DixlaseEvents::AUDIT_LOG_CREATED // 'dixlase.audit.log.created'
```

定義済みイベントカタログは [api-reference/events.md](api-reference/events.md) を参照。

---

## Webhook event type

外部 Webhook 配信は `event` フィールドに**発火イベント名と同一の文字列**を載せる。Webhook 専用の名前空間は無い。

```json
{
  "event": "dixlase.backup.completed",
  "data": { ... },
  "timestamp": 1730000000
}
```

新規イベントを設計する際は、外部連携先が Webhook で購読する可能性があるかを考慮する。その場合 payload は JSON シリアライズ可能で、schema-version ポリシー（[api-reference/events.md](api-reference/events.md) 参照）に従って安定している必要がある。

---

## 監査ログ action

`dls_audit_logs.action` 列に保存され、SIEM / 監査 UI のフィルタでマッチングされる文字列。定数は `App\Models\AuditLog` に `ACTION_<NAME>` として定義。

**フォーマット**: フラット `<resource_or_event>` または `<resource>_<action>`、lower_snake_case。

- 監査ログ action は dot 区切り**ではない**。SQL の `LIKE` フィルタと CSV エクスポートに最適化されたフラットなキー空間。
- 単一概念は単一トークン: `login`、`logout`。
- リソース + アクション: `password_changed`、`two_fa_enabled`、`passkey_revoked`。
- 結果修飾は接尾辞: `login_failed`、`passkey_auth_failed`。

**なぜイベント名と異なるスタイルか**: 監査 action は運用者が読み・grep する DB 永続値。dot 区切りは SQL フィルタが書きづらい（`action LIKE 'login.%'` vs `action LIKE 'login_%'` — どちらも動くが、運用者は監査フィールドにはアンダースコア区切りを期待する）。1 つの列でスタイルが混在するとインデックス局所性も損なう。

**例**

```php
AuditLog::ACTION_LOGIN              // 'login'
AuditLog::ACTION_PASSWORD_CHANGED   // 'password_changed'
AuditLog::ACTION_TWO_FA_ENABLED     // 'two_fa_enabled'
AuditLog::ACTION_PASSKEY_REGISTERED // 'passkey_registered'
```

---

## プラグインケイパビリティ

`plugin.json` の `capabilities` 配列で宣言される機能フラグ。コア側がプラグインの提供機能（SEO メタ、リンク等）を発見するために使う。

**フォーマット**: kebab-case 機能ラベル（名前空間なし）。

- lower-case、ハイフン区切り。
- 名前は**プラグイン自身**ではなく、プラグインが参加する**機能契約**を表す。
- 定義はコア側で行う。プラグインはケイパビリティを列挙してオプトインする。
- 新規ケイパビリティ文字列の追加はコア変更であって、個別プラグインによる追加ではない。

**例**

```json
{
  "capabilities": [
    "seo-meta",
    "linkable"
  ]
}
```

---

## プラグイン permission カテゴリ

`plugin.json` の `permissions` オブジェクトのトップレベルキー。プラグインがアクセスを要求するリソース種別を分類する。

**フォーマット**: 単語 1 つ、lower-case。

- 1 単語、区切り無し。
- コア定義の閉じた集合: `database`、`storage`、`settings`、`members`、`mail`、`content`、`system`。
- 新カテゴリの追加はコア変更（プラグイン変更ではない）。

**例**

```json
{
  "permissions": {
    "database": { "own_tables": true, "core_tables": [] },
    "storage":  { "own_directory": true, "public_uploads": false },
    "settings": { "read_core": true, "write_own": false }
  }
}
```

---

## キャッシュキー

文字列を手書きするのではなく `App\Support\Cache\CacheKey` を使う。

専用ドキュメント: **[cache-key-convention.md](cache-key-convention.md)**

要約: `dixlase:{scope}:{owner}:{domain}:{key}`、サイトスコープ形式 `dixlase:site:{site_id}:{scope}:…`。本ドキュメントとキャッシュキー規約を意図的に分けているのは、キャッシュキーには別の関心事（TTL、サイトスコープ、タグによる無効化）があるため。

---

## 設定キー

設定値は等価な 2 形式を持つ: `.env` 形式（`env()` で読む）と PHP 配列パス形式（`config()` で読む）。

**`.env` 形式**: `SCREAMING_SNAKE_CASE`

```
TRUSTED_PROXIES=10.0.0.0/8
AUDIT_LOG_RETENTION_DAYS=365
HSTS_MAX_AGE=300
```

**PHP パス形式**: `lower.dot.path`

```php
config('security.audit_log.retention_days')
config('trustedproxy.proxies')
```

**ペアリング規約**: `.env` キーは慣例的に単一の `config()` パスに対応し、対応する `config/<file>.php` で宣言する:

```php
// config/security.php
return [
    'audit_log' => [
        'retention_days' => env('AUDIT_LOG_RETENTION_DAYS', 365),
    ],
];
```

---

## 既存の例外（grandfathered）

以下の識別子は v0.1.0 に存在し、上記の規約から外れている。後方互換のためそのまま残す（改名は破壊的変更になる）。

| 識別子 | 種類 | 逸脱内容 | 状態 |
|---|---|---|---|
| `translation.model.retrieved` 等 | Event | `dixlase.` プレフィックス無し | Grandfathered。新規の翻訳イベントは `dixlase.translation.<action>` を使う。 |
| `dixlase.url.slug_changed` | Event | アクションセグメント内で snake_case（dot ではない） | Grandfathered。新規同等品は `dixlase.url.slug.changed`。 |
| `dixlase.url.admin_url_changed` | Event | 同上 | Grandfathered。 |

上記の「新規同等品」はエイリアスとしては**追加しない** — キー空間を分割してしまうため。逸脱事項は、習慣として伝播することを防ぐためここに記録するのみ。

---

## 本ドキュメントへの追記タイミング

新しい識別子種類を追加する場合:

1. 最初に識別子を導入する PR の**前に**命名フォーマットを決める。
2. 本ドキュメントを更新する PR を先に（または同じコミット内で）開く。
3. レビュアーが検証できるよう、PR description でこのセクションを参照する。

既存種類の新規識別子を追加する場合:

1. 本ドキュメントの規約に従う。
2. 逸脱が避けられない場合（レガシー連携、サードパーティスキーマ等）、理由を添えて [既存の例外](#既存の例外grandfathered) に行を追加する。

既存識別子の改名:

1. ほぼ許可されない — 全下流利用者にとって破壊的変更になるため。
2. やむを得ない場合（例: セキュリティ理由）は [PLUGIN-API.md](../../../PLUGIN-API.md) の deprecation ポリシーに従う: 1 メジャー版の間は新旧両方の名前を出荷し、その後旧名を削除。

---

## 関連

- [PLUGIN-API.md](../../../PLUGIN-API.md) — 公開 API の互換ポリシー
- [api-reference/events.md](api-reference/events.md) — 定義済みイベントカタログ
- [api-reference/webhooks.md](api-reference/webhooks.md) — Webhook 配信契約
- [api-reference/versioning.md](api-reference/versioning.md) — REST API バージョニングポリシー
- [cache-key-convention.md](cache-key-convention.md) — キャッシュキーフォーマット
