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
