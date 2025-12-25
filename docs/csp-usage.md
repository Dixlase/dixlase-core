# Content Security Policy (CSP) 使用ガイド

## 概要

DixlaseはContent Security Policy (CSP)を実装しており、XSS攻撃やデータ漏洩のリスクを軽減します。CSPはブラウザに対してどのリソースを読み込み・実行してよいかを指示するセキュリティ機能です。

## 基本的な仕組み

### Nonce方式

Dixlaseはnonce（使い捨てトークン）方式を採用しています。リクエストごとに一意のnonceが生成され、許可されたインラインスクリプトのみが実行されます。

```html
<!-- nonceが付与されたスクリプトのみ実行される -->
<script nonce="abc123...">
    // このスクリプトは実行される
</script>

<!-- nonceがないスクリプトはブロックされる -->
<script>
    // このスクリプトはブロックされる
</script>
```

## Bladeテンプレートでの使用

### @cspNonce ディレクティブ

インラインスクリプトにnonce属性を追加する最も簡単な方法です。

```blade
<script @cspNonce>
    // インラインスクリプト
    console.log('Hello, World!');
</script>
```

### @cspNonceValue ディレクティブ

nonce値のみを出力します。

```blade
<script nonce="@cspNonceValue">
    // インラインスクリプト
</script>
```

### ヘルパー関数

```php
// nonce値を取得
$nonce = csp_nonce();

// nonce属性を取得（nonce="xxx" 形式）
$attr = csp_nonce_attr();

// CSPが有効かどうか
$enabled = csp_is_enabled();

// CSPモードを取得（'enforce' または 'report-only'）
$mode = csp_get_mode();
```

## プラグイン・テーマでの使用

### CspPolicyProviderインターフェース

プラグインやテーマが外部リソースを必要とする場合、`CspPolicyProvider`インターフェースを実装してCSPディレクティブを追加できます。

```php
<?php

namespace Plugins\MyPlugin\App\Providers;

use App\Contracts\CspPolicyProvider;
use Illuminate\Support\ServiceProvider;

class MyPluginServiceProvider extends ServiceProvider implements CspPolicyProvider
{
    public function boot(): void
    {
        // プラグインのCSPポリシーを登録
        app(\App\Services\Csp\CspPolicyRegistry::class)
            ->registerProvider('my-plugin', $this);
    }

    public function getCspDirectives(): array
    {
        return [
            'script-src' => ['https://cdn.example.com'],
            'style-src' => ['https://fonts.googleapis.com'],
            'connect-src' => ['https://api.example.com'],
            'img-src' => ['https://images.example.com'],
        ];
    }
}
```

### 動的にディレクティブを追加

コントローラーやBladeテンプレートから動的にディレクティブを追加することもできます。

```php
// コントローラーで
csp_add_script_src('https://cdn.example.com');
csp_add_style_src('https://fonts.googleapis.com');
csp_add_connect_src('https://api.example.com');

// 汎用的な追加
csp_add_directive('frame-src', ['https://youtube.com', 'https://vimeo.com']);
```

## 管理画面での設定

### セキュリティ設定

管理画面の「全体設定 > セキュリティ設定」からCSPを設定できます。

- **CSPを有効にする**: CSPヘッダーの付与を有効/無効にします
- **CSPモード**: 
  - レポートモード: 違反を記録するのみでブロックしない
  - 強制モード: 違反をブロックする
- **違反をログに記録**: CSP違反をログファイルに記録します
- **信頼済みドメイン**: 外部リソースの読み込みを許可するドメイン
- **カスタムディレクティブ**: JSON形式で高度な設定を指定

### ログの確認

CSP違反ログは「全体設定 > システム > ログ」の「CSP違反」タブで確認できます。

## 設定ファイル

`config/csp.php`でデフォルト設定を変更できます。

```php
return [
    // CSP有効/無効（DBの設定が優先）
    'enabled' => env('CSP_ENABLED', true),

    // CSPモード（DBの設定が優先）
    'mode' => env('CSP_MODE', 'report-only'),

    // レポートURI
    'report_uri' => '/csp-report',

    // nonce長（バイト数）
    'nonce_length' => 16,

    // デフォルトディレクティブ
    'directives' => [
        'default-src' => ["'self'"],
        'script-src' => ["'self'", "'nonce'", "'strict-dynamic'"],
        'style-src' => ["'self'", "'nonce'", "'unsafe-inline'"],
        // ...
    ],

    // 信頼済みドメイン
    'trusted_domains' => [
        'https://fonts.googleapis.com',
        'https://fonts.gstatic.com',
        'https://fonts.bunny.net',
    ],

    // 除外パス
    'excluded_paths' => [
        '/csp-report',
        '/api/*',
    ],
];
```

## トラブルシューティング

### インラインスクリプトがブロックされる

1. `@cspNonce`ディレクティブを追加してください
2. または、スクリプトを外部ファイルに移動してください

### 外部リソースがブロックされる

1. 管理画面の「信頼済みドメイン」に該当ドメインを追加してください
2. または、プラグインで`CspPolicyProvider`を実装してください

### 開発中に問題が発生する

1. CSPモードを「レポートモード」に設定してください
2. ブラウザのコンソールで違反レポートを確認してください
3. 必要なディレクティブを追加してください

### CSPを一時的に無効にする

管理画面の「セキュリティ設定」でCSPを無効にするか、`.env`ファイルで設定できます。

```env
CSP_ENABLED=false
```

## ベストプラクティス

1. **本番環境では強制モードを使用**: 開発中はレポートモードで問題を特定し、本番環境では強制モードに切り替えてください

2. **インラインスクリプトを最小限に**: 可能な限り外部スクリプトファイルを使用してください

3. **信頼済みドメインは最小限に**: 必要なドメインのみを許可してください

4. **定期的にログを確認**: CSP違反ログを確認し、問題を早期に発見してください

5. **プラグイン開発時はCspPolicyProviderを実装**: 外部リソースが必要な場合は、適切にCSPディレクティブを宣言してください

---

## CSPモードとプラグイン・テーマの連携

### CSPモードの詳細

Dixlaseには3つのCSPモードがあり、それぞれプラグイン・テーマの動作に影響します。

#### 開発モード（Development）

プラグイン・テーマ開発時に最適なモードです。

- **動作**: すべてのスクリプトが動作し、違反はログに記録されます
- **インラインJS・CSS**: onclick等すべて許可
- **CSPヘッダー**: Report-Onlyモードで違反を記録
- **プラグイン制限**: なし
- **推奨**: 開発完了後は標準モードへの移行を推奨

```php
// 開発モードでの違反ログ確認
Log::channel('csp')->info('CSP violation detected', $violation);
```

#### 標準モード（Standard）

nonce付きインラインのみ許可し、セキュリティと互換性のバランスを取ります。

- **動作**: nonce付きインラインスクリプトのみ実行
- **インラインJS・CSS**: 素の`<script>`タグはブロック、`@dixScript`等のヘルパー経由ならOK
- **CSPヘッダー**: 強制モード
- **プラグイン制限**: `requires_inline_js: true`のプラグインは警告表示
- **推奨**: ほとんどのプラグイン・テーマが動作

```blade
{{-- 標準モードで動作するスクリプト --}}
<script @cspNonce>
    console.log('This works in standard mode');
</script>

{{-- 標準モードでブロックされるスクリプト --}}
<script>
    console.log('This is blocked in standard mode');
</script>
```

#### 厳格モード（Strict）

最高レベルのセキュリティを提供します。

- **動作**: インラインスクリプトを一切許可しません
- **インラインJS・CSS**: 完全禁止
- **CSPヘッダー**: 強制モード（最も厳格な設定）
- **プラグイン制限**: 
  - CSP Readyプラグイン・テーマのみ動作
  - `requires_inline_js: true`のプラグインは**有効化不可**
- **推奨**: 高セキュリティが求められる環境

### CSP無効時の違反チェック

**重要**: CSPが無効になっていても、Dixlaseはプラグイン・テーマのCSP適合性をチェックし、管理画面に表示します。

これにより：
- CSPを有効化する前に問題を把握できる
- 将来のCSP有効化に向けた準備ができる
- プラグイン開発者に改善の機会を提供できる

```
CSP違反が検出されました（3件の違反）

CSPは現在無効ですが、有効化した場合に問題が発生する可能性があります。
```

### プラグインのCSP適合性レベル

| レベル | 説明 | 開発モード | 標準モード | 厳格モード |
|--------|------|------------|------------|------------|
| **CSP Ready** | 完全対応 | ✅ 動作 | ✅ 動作 | ✅ 動作 |
| **CSP Compatible** | nonce付きで動作 | ✅ 動作 | ✅ 動作 | ⚠️ 警告 |
| **Inline Required** | インラインJS必須 | ✅ 動作 | ⚠️ 警告 | ❌ 有効化不可 |
| **Not Checked** | 未検証 | ✅ 動作 | ⚠️ 警告 | ⚠️ 警告 |

### plugin.json でのCSP宣言

プラグインのCSP要件を`plugin.json`で宣言できます：

```json
{
  "csp": {
    "requires_inline_js": false,
    "requires_inline_css": false,
    "external_scripts": [
      "https://cdn.example.com/script.js"
    ],
    "external_styles": [
      "https://fonts.googleapis.com"
    ]
  }
}
```

| フィールド | 説明 |
|------------|------|
| `requires_inline_js` | インラインJavaScriptが必須かどうか |
| `requires_inline_css` | インラインCSSが必須かどうか |
| `external_scripts` | 必要な外部スクリプトURL |
| `external_styles` | 必要な外部スタイルURL |

### CSP Readyプラグインの開発

CSP Readyなプラグインを開発するためのガイドライン：

#### 1. インラインスクリプトを避ける

```blade
{{-- ❌ 避けるべき --}}
<button onclick="doSomething()">Click</button>

{{-- ✅ 推奨 --}}
<button id="myButton">Click</button>
<script @cspNonce>
    document.getElementById('myButton').addEventListener('click', doSomething);
</script>
```

#### 2. @cspNonceディレクティブを使用

```blade
{{-- ✅ 推奨 --}}
<script @cspNonce>
    // Your code here
</script>

<style @cspNonce>
    /* Your styles here */
</style>
```

#### 3. 外部スクリプトはCspPolicyProviderで宣言

```php
class MyPluginServiceProvider extends ServiceProvider implements CspPolicyProvider
{
    public function getCspDirectives(): array
    {
        return [
            'script-src' => ['https://cdn.example.com'],
            'style-src' => ['https://fonts.googleapis.com'],
        ];
    }
}
```

#### 4. 動的スクリプト生成を避ける

```javascript
// ❌ 避けるべき
element.innerHTML = '<script>alert("XSS")</script>';

// ✅ 推奨
const script = document.createElement('script');
script.textContent = 'console.log("Safe")';
document.body.appendChild(script);
```

### 健全性チェックとの連携

CSP適合性は、プラグインの健全性（Health）評価に影響します：

| CSPモード | CSP違反時の影響 |
|-----------|-----------------|
| 開発モード | 健全性に影響なし（情報表示のみ） |
| 標準モード | 健全性「注意」（-5点） |
| 厳格モード | 健全性「要確認」（-15点） |

詳細は[プラグイン権限基盤ガイドライン](plugin-permission-guidelines.md)を参照してください。

### CSP違反ログの確認

CSP違反はログに記録され、管理画面から確認できます：

1. **管理画面**: 全体設定 > システム > ログ > CSP違反
2. **ログファイル**: `storage/logs/csp.log`

```php
// CSP違反レポートの例
[
    'document-uri' => 'https://example.com/admin/dashboard',
    'violated-directive' => 'script-src',
    'blocked-uri' => 'inline',
    'source-file' => 'https://example.com/plugins/my-plugin/script.js',
    'line-number' => 42,
]
```

### プラグイン別CSP違反の追跡

Dixlaseは、CSP違反を発生元のプラグイン・テーマに紐づけて記録します：

```php
// CSP違反テーブル（dls_csp_violations）
[
    'source_type' => 'plugin',
    'source_slug' => 'my-plugin',
    'directive' => 'script-src',
    'blocked_uri' => 'inline',
    'count' => 5,
    'first_seen_at' => '2025-12-25 10:00:00',
    'last_seen_at' => '2025-12-25 19:40:00',
]
```

これにより：
- どのプラグインがCSP違反を起こしているか特定できる
- プラグイン開発者に具体的なフィードバックを提供できる
- 健全性評価に反映できる

---

## 関連ドキュメント

- [プラグイン権限基盤ガイドライン](plugin-permission-guidelines.md)
- [セキュリティ設定ガイド](security-settings.md)
