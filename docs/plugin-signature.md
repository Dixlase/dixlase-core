# プラグイン署名システム

Dixlase のプラグイン署名システムは、プラグインの出所を保証し、改ざんを検知するための仕組みです。

## 概要

### 目的

1. **出所の保証**: プラグインが信頼できる開発者によって作成されたことを証明
2. **改ざん検知**: プラグインファイルが配布後に変更されていないことを確認
3. **信頼レベルの可視化**: 管理者がインストール前にプラグインの信頼性を判断

### 署名の流れ

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│  プラグイン開発  │ ──▶ │   署名コマンド   │ ──▶ │  署名付きZIP    │
│  plugin.json    │     │  秘密鍵で署名    │     │  signature.sig  │
└─────────────────┘     └─────────────────┘     └─────────────────┘
                                                        │
                                                        ▼
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│  インストール    │ ◀── │   署名検証      │ ◀── │  配布・ダウンロード │
│  許可/拒否      │     │  公開鍵で検証    │     │                 │
└─────────────────┘     └─────────────────┘     └─────────────────┘
```

---

## 署名タイプ

### 1. Official（公式）

exc-D inc. が開発・メンテナンスする公式プラグイン。

| 項目 | 値 |
|------|-----|
| キーIDプレフィックス | `dixlase-official` |
| バッジ | 🏆 公式（紫） |
| 信頼レベル | 最高 |

### 2. Verified（認証済み）

Dixlase マーケットプレイスで審査・承認されたプラグイン。

| 項目 | 値 |
|------|-----|
| キーIDプレフィックス | `dixlase-verified`, `marketplace` |
| バッジ | ✅ 認証済み（緑） |
| 信頼レベル | 高 |

### 3. Partner（パートナー）

公式パートナー企業が開発したプラグイン。

| 項目 | 値 |
|------|-----|
| キーIDプレフィックス | `partner-` |
| バッジ | 🤝 パートナー（青） |
| 信頼レベル | 高 |

### 4. Unsigned（未署名）

署名されていないプラグイン。リスクレベルが表示されます。

| 項目 | 値 |
|------|-----|
| バッジ | リスクレベルに応じて変化 |
| 信頼レベル | 不明 |

---

## ファイル構造

### プラグインディレクトリ

```
plugins/
└── MyPlugin/
    ├── plugin.json      # メタ情報 + 権限 + 署名メタ
    ├── signature.sig    # 署名ファイル
    ├── app/
    │   └── Providers/
    │       └── MyPluginServiceProvider.php
    ├── config/
    ├── resources/
    └── ...
```

### plugin.json の signing セクション

```json
{
  "name": "MyPlugin",
  "slug": "my-plugin",
  "version": "1.0.0",
  "permissions": { ... },
  "files": {
    "app/Providers/MyPluginServiceProvider.php": "sha256:abc123...",
    "config/admin.php": "sha256:def456...",
    "resources/views/index.blade.php": "sha256:ghi789..."
  },
  "signing": {
    "algo": "ed25519",
    "key_id": "dixlase-official-2025"
  }
}
```

| キー | 説明 |
|------|------|
| `files` | プラグイン内ファイルのSHA-256ハッシュ一覧 |
| `signing.algo` | 署名アルゴリズム（`ed25519` 推奨） |
| `signing.key_id` | 署名に使用した鍵のID |

### signature.sig ファイル

```json
{
  "algo": "ed25519",
  "key_id": "dixlase-official-2025",
  "signed_by": "exc-D inc.",
  "signed_at": "2025-12-01T00:00:00Z",
  "signature": "BASE64_ENCODED_SIGNATURE_OF_PLUGIN_JSON"
}
```

| キー | 説明 |
|------|------|
| `algo` | 署名アルゴリズム |
| `key_id` | 署名に使用した鍵のID |
| `signed_by` | 署名者名 |
| `signed_at` | 署名日時（ISO 8601形式） |
| `signature` | plugin.json に対する署名（Base64エンコード） |

---

## 署名の作成（将来実装）

### Artisan コマンド

```bash
# プラグインに署名
php artisan dls:plugin:sign my-plugin

# 署名者情報を指定
php artisan dls:plugin:sign my-plugin --signed-by="exc-D inc."

# 特定の鍵を使用
php artisan dls:plugin:sign my-plugin --key-id=dixlase-official-2025
```

### 署名プロセス

1. **ファイルハッシュ生成**: プラグイン内の全ファイルのSHA-256ハッシュを計算
2. **plugin.json 更新**: `files` セクションにハッシュ一覧を追加
3. **署名生成**: plugin.json の内容に対して秘密鍵で署名
4. **signature.sig 作成**: 署名情報をJSONファイルとして保存

### 署名コマンドの実装例（参考）

```php
// app/Console/Commands/SignPluginCommand.php

class SignPluginCommand extends Command
{
    protected $signature = 'dls:plugin:sign {plugin} {--signed-by=} {--key-id=}';
    protected $description = 'Sign a Dixlase plugin';

    public function handle()
    {
        $pluginSlug = $this->argument('plugin');
        $pluginPath = base_path("plugins/" . Str::studly($pluginSlug));
        
        // 1. ファイルハッシュを生成
        $files = $this->buildFileHashes($pluginPath);
        
        // 2. plugin.json を更新
        $pluginJson = $this->updatePluginJson($pluginPath, $files);
        
        // 3. 署名を生成
        $signature = $this->signContent(json_encode($pluginJson));
        
        // 4. signature.sig を作成
        $this->createSignatureFile($pluginPath, $signature);
        
        $this->info("Plugin signed successfully!");
    }
}
```

---

## 署名の検証（将来実装）

### 検証プロセス

1. **signature.sig の読み込み**: 署名ファイルを読み込む
2. **公開鍵の取得**: `key_id` に対応する公開鍵を取得
3. **署名検証**: plugin.json の内容と署名を検証
4. **ファイルハッシュ検証**: 実ファイルのハッシュと `files` セクションを比較

### 検証結果

| ステータス | 説明 |
|-----------|------|
| `valid` | 署名が有効、ファイルも改ざんなし |
| `invalid` | 署名が無効、または改ざんの可能性 |
| `unsigned` | 署名ファイルが存在しない |
| `pending_verification` | 署名ファイルあり、検証未実施 |

### SignatureVerifier インターフェース（参考）

```php
// app/Contracts/SignatureVerifier.php

interface SignatureVerifier
{
    /**
     * プラグインの署名を検証
     *
     * @param string $pluginSlug
     * @return SignatureResult
     */
    public function verify(string $pluginSlug): SignatureResult;
}

// app/Support/SignatureResult.php

class SignatureResult
{
    public function __construct(
        public string $status,      // valid, invalid, unsigned
        public ?string $type,       // official, verified, partner
        public ?string $signedBy,
        public ?string $signedAt,
        public ?string $keyId,
        public array $errors = []
    ) {}
    
    public function isValid(): bool
    {
        return $this->status === 'valid';
    }
    
    public function isSigned(): bool
    {
        return $this->status !== 'unsigned';
    }
}
```

---

## 鍵管理

### 鍵の種類

```
┌─────────────────────────────────────────────────────────┐
│                    Root Key（ルート鍵）                  │
│                    オフライン保管                        │
│                    マーケット鍵の発行に使用              │
└─────────────────────────────────────────────────────────┘
                            │
            ┌───────────────┼───────────────┐
            ▼               ▼               ▼
┌─────────────────┐ ┌─────────────────┐ ┌─────────────────┐
│  Official Key   │ │  Marketplace Key │ │  Partner Keys   │
│  公式プラグイン  │ │  マーケット審査  │ │  パートナー企業  │
└─────────────────┘ └─────────────────┘ └─────────────────┘
```

### 設定ファイル（参考）

```php
// config/dixlase_plugins.php

return [
    'signing' => [
        // 署名ポリシー: none, warn, require
        'policy' => env('DIXLASE_PLUGIN_SIGN_POLICY', 'warn'),
        
        // 信頼する公開鍵
        'trusted_keys' => [
            'dixlase-official-2025' => [
                'public_key' => env('DIXLASE_OFFICIAL_PUBLIC_KEY'),
                'label' => 'Dixlase Official',
                'type' => 'official',
            ],
            'marketplace-2025' => [
                'public_key' => env('DIXLASE_MARKETPLACE_PUBLIC_KEY'),
                'label' => 'Dixlase Marketplace',
                'type' => 'verified',
            ],
        ],
        
        // ローカル環境では未署名を許可
        'allow_unsigned_in_local' => env('APP_ENV') === 'local',
    ],
];
```

### 署名ポリシー

| ポリシー | 動作 |
|---------|------|
| `none` | 署名チェックを行わない |
| `warn` | 未署名・無効署名の場合は警告を表示（デフォルト） |
| `require` | 有効な署名がないプラグインのインストールを拒否 |

---

## セキュリティ考慮事項

### 秘密鍵の管理

- 秘密鍵は**絶対に**リポジトリにコミットしない
- 署名専用の隔離された環境で管理
- 定期的な鍵のローテーション

### 鍵の漏洩時

1. 漏洩した鍵IDを `trusted_keys` から削除
2. 新しい鍵ペアを生成
3. 影響を受けるプラグインを再署名
4. ユーザーに通知

### オフライン環境での運用

署名付きZIPを使用することで、オフライン環境でも署名検証が可能です：

1. オンライン環境で署名付きZIPをダウンロード
2. オフライン環境にZIPを持ち込み
3. 公開鍵のみで署名を検証
4. 検証OKならインストール

---

## 管理画面での表示

### バッジ表示

| 署名ステータス | バッジ | 説明 |
|---------------|--------|------|
| 公式 | 🏆 公式（紫） | exc-D inc. 公式プラグイン |
| 認証済み | ✅ 認証済み（緑） | マーケットで審査済み |
| パートナー | 🤝 パートナー（青） | 公式パートナー開発 |
| 署名無効 | ❌ 署名無効（赤） | 改ざんの可能性あり |
| 未署名 | リスクレベル表示 | 署名なし |

### 詳細モーダル

署名済みプラグインの詳細モーダルには以下が表示されます：

- **署名ステータス**: 公式/認証済み/パートナー
- **署名者**: 署名した組織・個人名
- **署名日時**: 署名が行われた日時
- **権限情報**: リスクレベルと権限カテゴリ

---

## 実装ロードマップ

### Phase 1: 基盤（✅ 完了）

- [x] `plugin.json` の `signing` セクション設計
- [x] `signature.sig` ファイルフォーマット設計
- [x] `PluginPermissionService::getSignatureInfo()` 実装
- [x] 管理画面での署名ステータス表示

### Phase 2: 署名コマンド（将来）

- [ ] `dls:plugin:sign` コマンド実装
- [ ] ファイルハッシュ生成機能
- [ ] Ed25519 署名生成機能

### Phase 3: 署名検証（将来）

- [ ] `SignatureVerifier` インターフェース実装
- [ ] 署名検証ロジック実装
- [ ] インストール時の署名チェック

### Phase 4: マーケット連携（将来）

- [ ] マーケットプレイス署名鍵管理
- [ ] 自動署名ワークフロー
- [ ] 鍵ローテーション機能

---

## 関連ドキュメント

- [プラグイン権限システム](./plugin-permissions.md)
- [プラグイン開発ガイド](./plugin-theme-route-autoloading.md)
