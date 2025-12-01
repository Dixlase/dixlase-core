# プラグイン権限システム

Dixlase のプラグイン権限システムは、プラグインがアクセスできるリソースや機能を宣言的に定義し、管理者がインストール前にリスクを評価できるようにするための仕組みです。

## 概要

### 目的

1. **透明性**: プラグインが何にアクセスするかを明確にする
2. **セキュリティ**: 不正なアクセスを検知・防止する
3. **信頼性**: 署名によりプラグインの出所を保証する

### 構成要素

- `plugin.json` - 権限宣言ファイル
- `signature.sig` - 署名ファイル（オプション）
- `PluginPermissionService` - 権限チェックサービス

---

## plugin.json の権限定義

### 基本構造

```json
{
  "name": "MyPlugin",
  "slug": "my-plugin",
  "version": "1.0.0",
  "description": "プラグインの説明",
  "author": "作者名",
  "email": "author@example.com",
  "web": "https://example.com",
  "namespace": "Plugins\\MyPlugin",
  "providers": [
    "Plugins\\MyPlugin\\App\\Providers\\MyPluginServiceProvider"
  ],
  "requires": {
    "php": ">=8.2"
  },
  "permissions": {
    "database": { ... },
    "storage": { ... },
    "settings": { ... },
    "members": { ... },
    "mail": { ... },
    "content": { ... },
    "system": { ... }
  },
  "signing": {
    "algo": "ed25519",
    "key_id": "dixlase-official-2025"
  }
}
```

---

## 権限カテゴリ

### 1. database（データベース）

プラグインがアクセスするデータベーステーブルを定義します。

```json
"database": {
  "own_tables": true,
  "core_tables": [
    "users:read",
    "members:read"
  ]
}
```

| キー | 型 | デフォルト | 説明 |
|------|-----|---------|------|
| `own_tables` | boolean | `true` | プラグイン専用テーブルの作成・使用 |
| `core_tables` | array | `[]` | コアテーブルへのアクセス（`テーブル名:権限` 形式） |

**core_tables の権限形式:**
- `users:read` - 読み取りのみ
- `members:write` - 読み書き可能

---

### 2. storage（ストレージ）

ファイルシステムへのアクセス権限を定義します。

```json
"storage": {
  "own_directory": true,
  "public_uploads": false,
  "temp_files": true
}
```

| キー | 型 | デフォルト | 説明 |
|------|-----|---------|------|
| `own_directory` | boolean | `true` | `storage/app/plugins/{plugin}` への読み書き |
| `public_uploads` | boolean | `false` | `public/uploads` への書き込み |
| `temp_files` | boolean | `false` | 一時ファイルの作成 |

---

### 3. settings（設定）

システム設定へのアクセス権限を定義します。

```json
"settings": {
  "read_core": false,
  "write_own": true
}
```

| キー | 型 | デフォルト | 説明 |
|------|-----|---------|------|
| `read_core` | boolean | `false` | コア設定（BaseSetting等）の読み取り |
| `write_own` | boolean | `true` | プラグイン専用設定の読み書き |

---

### 4. members（メンバー）

管理者メンバー情報へのアクセス権限を定義します。

```json
"members": {
  "read": false,
  "write": false,
  "create": false,
  "delete": false
}
```

| キー | 型 | デフォルト | 説明 |
|------|-----|---------|------|
| `read` | boolean | `false` | メンバー情報の読み取り |
| `write` | boolean | `false` | メンバー情報の更新 |
| `create` | boolean | `false` | 新規メンバーの作成 |
| `delete` | boolean | `false` | メンバーの削除 |

⚠️ **注意**: `write`, `create`, `delete` は高リスク権限です。

---

### 5. mail（メール）

メール送信機能へのアクセス権限を定義します。

```json
"mail": {
  "send": true,
  "bulk_send": false
}
```

| キー | 型 | デフォルト | 説明 |
|------|-----|---------|------|
| `send` | boolean | `false` | 単一メールの送信 |
| `bulk_send` | boolean | `false` | 一括メール送信 |

⚠️ **注意**: `bulk_send` は高リスク権限です。

---

### 6. content（コンテンツ）

他のプラグインのコンテンツへのアクセス権限を定義します。

```json
"content": {
  "read_other_plugins": ["dixlase-pages", "dixlase-blog"],
  "write_other_plugins": []
}
```

| キー | 型 | デフォルト | 説明 |
|------|-----|---------|------|
| `read_other_plugins` | array | `[]` | 読み取り可能な他プラグインのスラッグ |
| `write_other_plugins` | array | `[]` | 書き込み可能な他プラグインのスラッグ |

---

### 7. system（システム）

システムレベルの機能登録権限を定義します。

```json
"system": {
  "register_shortcodes": true,
  "register_middleware": false,
  "register_commands": false,
  "register_blade_directives": false,
  "modify_routes": false
}
```

| キー | 型 | デフォルト | 説明 |
|------|-----|---------|------|
| `register_shortcodes` | boolean | `false` | ショートコードの登録 |
| `register_middleware` | boolean | `false` | ミドルウェアの登録 |
| `register_commands` | boolean | `false` | Artisan コマンドの登録 |
| `register_blade_directives` | boolean | `false` | Blade ディレクティブの登録 |
| `modify_routes` | boolean | `false` | ルートの変更 |

---

## リスクレベル

権限の組み合わせに基づいてリスクレベルが自動計算されます。

### 計算基準

| リスクレベル | スコア | 説明 |
|-------------|--------|------|
| **低リスク** | 0-1 | 基本的な権限のみ |
| **中リスク** | 2-4 | メール送信やコア設定読み取りなど |
| **高リスク** | 5+ | メンバー操作や一括メール送信など |

### スコア配分

**高リスク権限（+3〜4点）:**
- `members.write`: +3
- `members.create`: +3
- `members.delete`: +4
- `mail.bulk_send`: +3
- `storage.public_uploads`: +2
- `content.write_other_plugins`: +2（空でない場合）

**中リスク権限（+1点）:**
- `mail.send`: +1
- `settings.read_core`: +1
- `system.register_middleware`: +1
- `database.core_tables`: +1（空でない場合）

---

## 署名システム

### 署名タイプ

| タイプ | キーIDプレフィックス | 説明 |
|--------|---------------------|------|
| `official` | `dixlase-official` | exc-D inc. 公式プラグイン |
| `verified` | `dixlase-verified`, `marketplace` | マーケットで審査済み |
| `partner` | `partner-` | 公式パートナー開発 |

### plugin.json の signing セクション

```json
"signing": {
  "algo": "ed25519",
  "key_id": "dixlase-official-2025"
}
```

### signature.sig ファイル

```json
{
  "algo": "ed25519",
  "key_id": "dixlase-official-2025",
  "signed_by": "exc-D inc.",
  "signed_at": "2025-12-01T00:00:00Z",
  "signature": "BASE64_ENCODED_SIGNATURE"
}
```

---

## 使用方法

### 権限チェック

```php
use App\Facades\PluginPermission;

// 単一権限のチェック
if (PluginPermission::check('my-plugin', 'mail.send')) {
    // メール送信処理
}

// ヘルパー関数
if (plugin_can('my-plugin', 'database.own_tables')) {
    // テーブル操作
}

// 権限強制（違反時は例外）
plugin_enforce('my-plugin', 'storage.public_uploads', 'ファイルアップロード');
```

### コアテーブルアクセスチェック

```php
if (PluginPermission::canAccessCoreTable('my-plugin', 'users', 'read')) {
    // users テーブルの読み取り
}
```

### 他プラグインアクセスチェック

```php
if (PluginPermission::canAccessOtherPlugin('my-plugin', 'dixlase-pages', 'read')) {
    // dixlase-pages のコンテンツ読み取り
}
```

### 権限サマリー取得

```php
$summary = PluginPermission::getSummary('my-plugin');
// [
//     'has_permissions' => true,
//     'risk_level' => 'medium',
//     'categories' => ['database' => ['own_tables'], 'mail' => ['send']],
//     'signature' => ['status' => 'unsigned', ...]
// ]
```

---

## Artisan コマンド

### 権限確認

```bash
# プラグインの権限一覧を表示
php artisan dls:plugin:permissions dixlase-inquiry

# 特定の権限をチェック
php artisan dls:plugin:permissions dixlase-inquiry --check=mail.send

# JSON形式で出力
php artisan dls:plugin:permissions dixlase-inquiry --json
```

---

## 管理画面での表示

プラグイン管理画面では、各プラグインに以下の情報が表示されます：

### バッジ表示

| 状態 | バッジ | 色 |
|------|--------|-----|
| 署名あり（公式） | 🏆 公式 | 紫 |
| 署名あり（認証済み） | ✅ 認証済み | 緑 |
| 署名あり（パートナー） | 🤝 パートナー | 青 |
| 署名無効 | ❌ 署名無効 | 赤 |
| 未署名 + 低リスク | 🛡️ 低リスク | 緑 |
| 未署名 + 中リスク | ⚠️ 中リスク | 黄 |
| 未署名 + 高リスク | ⚠️ 高リスク | 赤 |
| 権限未定義 | ❓ 未定義 | グレー |

### 詳細モーダル

バッジをクリックすると、以下の情報を含むモーダルが表示されます：

1. **署名ステータス** - 署名の有無と署名者情報
2. **権限情報** - リスクレベルとカテゴリ別の権限一覧

---

## サンプル: 完全な plugin.json

```json
{
  "name": "DixlaseInquiry",
  "slug": "dixlase-inquiry",
  "version": "1.0.0",
  "description": "お問い合わせフォーム機能を提供するプラグイン。",
  "author": "exc-D inc.",
  "email": "info@exc-d.com",
  "web": "https://exc-d.com",
  "namespace": "Plugins\\DixlaseInquiry",
  "providers": [
    "Plugins\\DixlaseInquiry\\App\\Providers\\DixlaseInquiryServiceProvider"
  ],
  "requires": {
    "php": ">=8.2"
  },
  "provides": {
    "shortcodes": true
  },
  "permissions": {
    "database": {
      "own_tables": true,
      "core_tables": ["users:read"]
    },
    "storage": {
      "own_directory": true,
      "public_uploads": false,
      "temp_files": true
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
      "send": true,
      "bulk_send": false
    },
    "content": {
      "read_other_plugins": [],
      "write_other_plugins": []
    },
    "system": {
      "register_shortcodes": true,
      "register_middleware": false,
      "register_commands": false,
      "register_blade_directives": false,
      "modify_routes": false
    }
  },
  "signing": {
    "algo": "ed25519",
    "key_id": "dixlase-official-2025"
  }
}
```

---

## 関連ドキュメント

- [プラグイン開発ガイド](./plugin-theme-route-autoloading.md)
- [多言語プラグイン開発](./multilingual-plugin-development.md)
- [プラグイン統合契約](./plugin-integration-contracts.md)
