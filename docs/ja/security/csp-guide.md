# Content Security Policy (CSP) 完全ガイド

## 目次

1. [概要](#概要)
2. [CSPモード仕様](#cspモード仕様)
3. [セーフモード](#セーフモード)
4. [基本的な使い方](#基本的な使い方)
5. [コマンドラインツール](#コマンドラインツール)
6. [プラグイン・テーマ開発](#プラグインテーマ開発)
7. [Dixlase初期化規約](#dixlase初期化規約)
8. [トラブルシューティング](#トラブルシューティング)
9. [FAQ](#faq)

---

## 概要

DixlaseはContent Security Policy (CSP)を実装しており、XSS攻撃やデータ漏洩のリスクを軽減します。CSPはブラウザに対してどのリソースを読み込み・実行してよいかを指示するセキュリティ機能です。

### セキュリティポリシー

Dixlaseは段階的なCSP実装を採用しています：

- ✅ `unsafe-inline`は排除（nonce方式）
- ⚠️ `unsafe-eval`は限定的に許可（Alpine.js使用のため）

**なぜunsafe-evalを許可しているのか？**

Alpine.js v3を使用するため、標準モードでは`unsafe-eval`を限定的に許可しています。これは以下の理由によります：

1. Alpine.jsの動的式評価機能を活用
2. サードパーティプラグインとの互換性確保
3. 開発者体験の向上

将来のバージョン（v2.0以降）でAlpine.js CSP Buildへの移行を検討し、`unsafe-eval`の完全排除を目指します。新規コードは **[Alpine.js CSP互換コーディングルール](alpine-csp-coding-rules.md)** に従って記述してください。

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

---

## CSPモード仕様

Dixlaseは3つのCSPモードを提供し、開発体験とセキュリティのバランスを柔軟に調整できます。

### モード一覧

| モード | 適用方式 | インライン実行 | onclick等 | unsafe-eval | プラグイン互換性 | 用途 |
|--------|----------|---------------|-----------|-------------|-----------------|------|
| **Development** | Report-Only | 許可 | 許可 | 許可 | 最大 | 開発・デバッグ |
| **Standard** | 強制 | nonceヘルパーのみ | 警告 | 許可（Alpine.js用） | 高 | 本番推奨 |
| **Strict** | 強制 | 完全禁止 | 禁止 | 禁止 | CSP Readyのみ | 最大セキュリティ |

---

### 1. Development Mode（開発モード）

#### 目的
テーマ/プラグイン開発時の体験を最優先。すべて動作するが、将来の問題を可視化。

#### CSP適用方式
- **Report-Only**（ブロックせず記録のみ）
- 拒否ドメインも警告のみ（設定で強制ブロック可）

#### 許可範囲

**スクリプト**
- ✅ 外部スクリプト（`'self'` + 宣言済みドメイン）
- ✅ インライン実行コード（`unsafe-inline`）
- ✅ 属性イベント（`onclick` 等）
- ✅ `unsafe-eval`（Vite/HMR対応）
- ✅ 生の `<script>...</script>`（nonceなし）

**スタイル**
- ✅ インラインCSS（`unsafe-inline`）
- ✅ 外部CSS

**その他**
- ✅ `type="application/json"` のJSON埋め込み
- ✅ `data-*` 属性
- ✅ すべてのプラグイン（`requires_inline_js: true` も動作）

#### 期待される体験
- すべて動作する
- CSP違反は管理画面に記録される
- 「このプラグインは標準/厳格で壊れる可能性」を事前に把握できる

---

### 2. Standard Mode（標準モード）- 本番推奨

#### 目的
本番運用のデフォルト。互換性を落としすぎず、XSS耐性を実用レベルまで上げる。

#### CSP適用方式
- **強制（ブロック）**
- `Report-To/Report-URI` も併用（ブロック＋報告）

#### 許可範囲

**スクリプト**
- ✅ 外部スクリプト（`'self'` + 宣言済みドメイン）
- ✅ インライン実行コード：**ヘルパー経由（nonce付与）のみ**
  - `@dixScript ... @enddixScript`
  - `Dixlase::script()`
- ❌ 生の `<script>...</script>`（nonceなし）
- ⚠️ 属性イベント（`onclick` 等）：**警告（移行期は動作許可）**
- ⚠️ `unsafe-eval`：**Alpine.jsのため限定的に許可**
  - Alpine.js v3の動的式評価機能を使用するため
  - 将来のバージョンでAlpine.js CSP Buildへの移行を検討
- 🔄 `strict-dynamic`：任意（互換性のためデフォルトOFF）

**スタイル**
- ✅ インラインCSS：ヘルパー経由（nonce） or hash
- ✅ 外部CSS（宣言済みドメイン）
- ❌ 無制限な `unsafe-inline` は使わない

**データ受け渡し**
- ✅ `type="application/json"` のJSON埋め込み
- ✅ `data-*` 属性

**iframe / object 等**
- ✅ `object-src 'none'`（推奨）
- ✅ `base-uri 'self'`（推奨）
- ✅ `frame-ancestors`：管理画面は `'none'`（クリックジャッキング対策）

#### 期待される体験
- 多くのプラグインが動作する
- インラインを使う場合でも「Dixlase流の書き方」に寄せれば安全に動く
- onclick等は将来的に非推奨（警告が出る）

---

### 3. Strict Mode（厳格モード）

> ⚠️ **注意：厳格モードは現在未実装です**
>
> 厳格モードは将来のバージョンでの実装を検討中です。
> 現在は開発モードと標準モードのみが利用可能です。

#### 目的
CSP Readyなテーマ/プラグインのみで、最大限の防御を実現。
管理画面も含めてインライン実行を不要化し、レビュー・監査・改ざん検知と相性最大化。

#### CSP適用方式
- **強制（ブロック）**
- `Report-To` も併用
- Dixlase側ルールで「インライン実行コードが存在したら拒否」も行う（CMSポリシー）

#### 許可範囲

**スクリプト**
- ✅ 外部スクリプトのみ（`'self'` + 宣言済みドメイン）
- ✅ CSPの起点スクリプト（ブートローダー）
  - `bootstrap.js` のようなコア統制下の外部JS
- ❌ インライン実行コード（**nonce付きであっても禁止**：Dixlaseポリシー）
- ❌ 属性イベント（`onclick` 等）
- ❌ `unsafe-eval`
- ✅ `strict-dynamic` をON（コアが起点を握る前提）

**"インライン"で許可されるのは「実行しないものだけ」**
- ✅ `<script type="application/json">`（設定・初期データの受け渡し）
- ✅ `data-*` 属性（宣言的初期化）
- ❌ 最小限の `<style>` も原則禁止（可能なら外部CSSへ）

**管理画面の追加ガード（厳格のみ）**
- ✅ `frame-ancestors 'none'`
- ✅ `form-action 'self'`
- ✅ `object-src 'none'`
- ✅ `base-uri 'none'`
- ✅ `upgrade-insecure-requests`（可能なら）
- 🔮 将来的に `require-trusted-types-for 'script'` も検討

**プラグイン・テーマの互換性ルール**
- ❌ `requires_inline_js: true` → **有効化不可**
- ❌ plugin.jsonに外部依存の宣言がない（または動的挿入） → **警告 or 不可**
- ⚠️ ブロックリスト照合でヒット → 設定に従い警告/ブロック

#### 期待される体験
- 壊れるプラグインは最初から弾く
- その代わり **CSP Readyの世界では事故が起きにくい**
- 「インラインを書かなくてもUIが作れる」ように、コアがブート規約を提供する

---

### モード別の許可される記述 早見表

| 項目 | Development | Standard | Strict |
|------|-------------|----------|--------|
| **CSP適用** | Report-Only（推奨） | 強制 | 強制 |
| **インラインJS** | 許可 | **nonceヘルパーのみ** | **禁止（nonceでも）** |
| **onclick等** | 許可 | 原則禁止（移行期は警告可） | 禁止 |
| **外部JS** | 許可 | 許可（宣言必須） | 許可（宣言必須） |
| **unsafe-eval** | 許可（必要な場合） | 禁止 | 禁止 |
| **strict-dynamic** | 任意 | 任意（推奨寄り） | 推奨（ON前提） |
| **設定受け渡し（JSON script）** | 許可 | 許可 | 許可 |
| **data-*初期化** | 許可 | 許可 | 推奨 |
| **管理画面まで統一** | 任意 | 推奨 | 前提 |

---

## セーフモード

### 概要

CSP設定を変更して管理画面にアクセスできなくなった場合に備えて、**セーフモード**機能を提供しています。セーフモードでは、CSPヘッダーが一時的に無効化され、管理画面に安全にアクセスできます。

### セーフモードの発動条件

以下のいずれかの条件でセーフモードが自動的に有効になります：

1. **CSP設定保存後の確認タイムアウト**
   - CSP設定を保存すると、10秒間の確認モーダルが表示されます
   - 10秒以内に「この設定を使う」ボタンを押さないと、自動的に前の設定にロールバックされます
   - ロールバック後、セーフモードが有効になります

2. **手動でのロールバック**
   - 確認モーダルで「元に戻す」ボタンを押すと、前の設定にロールバックされ、セーフモードが有効になります

### セーフモードバナー

セーフモードが有効な場合、管理画面の上部に**赤いバナー**が表示されます：

```
⚠️ CSPセーフモードが有効です
CSP設定で問題が発生したため、一時的にCSPを無効化しています。
セキュリティリスクがあるため、設定を完了するか必ずセーフモードを解除してください。

[CSP設定へ]  [セーフモード解除]
```

### セーフモードの解除方法

#### 方法1: 管理画面から解除

1. バナーの「セーフモード解除」ボタンをクリック
2. または、「CSP設定へ」から設定を修正して保存

#### 方法2: コマンドラインから解除

```bash
php artisan csp:disable-safe-mode
```

### セーフモードの仕組み

- セーフモードはセッションベースで動作します
- ログインユーザーごとに独立して管理されます
- セーフモード中は、`CspBuilder`がCSPヘッダーの出力をスキップします
- セーフモードを解除するまで、CSPは無効のままです

### 注意事項

⚠️ **セーフモードは一時的な救済措置です**
- セーフモード中はCSPが無効化されており、XSS攻撃のリスクが高まります
- 設定を修正したら、必ずセーフモードを解除してください
- セーフモードは管理画面のみで有効です（フロントエンドには影響しません）

---

## 基本的な使い方

### Bladeテンプレートでの使用

#### @cspNonce ディレクティブ

インラインスクリプトにnonce属性を追加する最も簡単な方法です。

```blade
<script @cspNonce>
    // インラインスクリプト
    console.log('Hello, World!');
</script>
```

#### @cspNonceValue ディレクティブ

nonce値のみを出力します。

```blade
<script nonce="@cspNonceValue">
    // インラインスクリプト
</script>
```

#### ヘルパー関数

```php
// nonce値を取得
$nonce = csp_nonce();

// nonce属性を取得（nonce="xxx" 形式）
$attr = csp_nonce_attr();

// CSPが有効かどうか
$enabled = csp_is_enabled();

// CSPモードを取得
$mode = csp_get_mode();
```

### 動的にディレクティブを追加

コントローラーやBladeテンプレートから動的にディレクティブを追加できます。

```php
// コントローラーで
csp_add_script_src('https://cdn.example.com');
csp_add_style_src('https://fonts.googleapis.com');
csp_add_connect_src('https://api.example.com');

// 汎用的な追加
csp_add_directive('frame-src', ['https://youtube.com', 'https://vimeo.com']);
```

---

## Dixlase初期化規約

厳格モードでは、インライン実行コードを書かずにUIを初期化できる仕組みをコアが提供します。

### 1. ウィジェット登録

**HTML（テンプレート）**
```html
<div data-dix-widget="gallery"
     data-dix-props='{"autoplay":true,"speed":400}'>
</div>
```

**JavaScript（プラグイン）**
```javascript
Dixlase.widgets.register("gallery", (el, props) => {
  // el: 対象DOM
  // props: data-dix-props から復元された設定
  mountGallery(el, props);
});
```

### 2. アクション登録（イベント委譲）

**HTML**
```html
<button data-dix-action="contact.submit">送信</button>
```

**JavaScript**
```javascript
Dixlase.actions.register("contact.submit", (ctx) => {
  ctx.form.requestSubmit();
});
```

### 3. ページ固有の初期化

**HTML**
```html
<body data-dix-page="admin.dashboard">
```

**JavaScript**
```javascript
Dixlase.pages.register("admin.dashboard", () => {
  bootDashboard();
});
```

### 4. 設定データの読み込み

**JSON script方式**
```html
<script type="application/json" id="app-config">
  {"theme": "dark", "lang": "ja"}
</script>
```

```javascript
const config = Dixlase.config.load('app-config');
```

**data-*方式**
```html
<div id="widget" data-dix-config-theme="dark"></div>
```

```javascript
const theme = Dixlase.config.get(element, 'theme');
```

---

## コマンドラインツール

### CSP設定の確認

現在のCSP設定を確認します：

```bash
php artisan csp:status
```

**出力例：**
```
CSP Status
==========
Enabled: Yes
Mode: Standard (1)
Log Violations: Yes
Exclude Dev Tools: Yes
Safe Mode: No

Trusted Domains:
  - https://cdn.example.com
  - https://fonts.googleapis.com

Blocklist Categories:
  - tracking (15 domains)
  - ads (23 domains)
```

### CSPモードの変更

コマンドラインからCSPモードを変更できます：

```bash
# 開発モードに変更
php artisan csp:mode development

# 標準モードに変更
php artisan csp:mode standard

# 厳格モードに変更
php artisan csp:mode strict
```

### CSPの有効化/無効化

```bash
# CSPを有効化
php artisan csp:enable

# CSPを無効化
php artisan csp:disable
```

### セーフモードの管理

```bash
# セーフモードを有効化
php artisan csp:enable-safe-mode

# セーフモードを無効化
php artisan csp:disable-safe-mode

# セーフモードの状態を確認
php artisan csp:status
```

### CSP違反ログの確認

CSP違反ログを表示します：

```bash
# 最新の違反ログを表示
php artisan csp:violations

# 最新10件を表示
php artisan csp:violations --limit=10

# 特定のディレクティブのみ表示
php artisan csp:violations --directive=script-src
```

### CSPキャッシュのクリア

CSP設定のキャッシュをクリアします：

```bash
php artisan csp:clear
```

### 緊急時のCSP無効化

管理画面にアクセスできない場合、`.env`ファイルで直接無効化できます：

```env
CSP_ENABLED=false
```

または、コマンドラインから：

```bash
php artisan csp:disable --force
```

---

## プラグイン・テーマ開発

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

### plugin.json でのCSP宣言

プラグインのCSP要件を`plugin.json`で宣言できます：

```json
{
  "name": "DixlaseGallery",
  "csp": {
    "requires_inline_js": false,
    "requires_inline_css": false,
    "external_scripts": [
      "https://cdn.example.com/gallery.js"
    ],
    "external_styles": [
      "https://cdn.example.com/gallery.css"
    ],
    "connect_src": [
      "https://api.example.com"
    ]
  },
  "assets": {
    "scripts": [
      "resources/js/gallery.js"
    ],
    "styles": [
      "resources/css/gallery.css"
    ]
  }
}
```

### CSP Ready チェックリスト

- [ ] インライン実行コードを使用していない
- [ ] onclick等の属性イベントを使用していない
- [ ] 外部依存をすべてplugin.jsonに宣言している
- [ ] 初期化は `Dixlase.widgets.register()` 等を使用
- [ ] 設定は `data-*` または `type="application/json"` で受け渡し
- [ ] `requires_inline_js: false` を明示

### CSP Readyプラグインの開発

#### 1. インラインスクリプトを避ける

```blade
{{-- ❌ 避けるべき --}}
<button onclick="doSomething()">Click</button>

{{-- ✅ 推奨（標準モード） --}}
<button id="myButton">Click</button>
<script @cspNonce>
    document.getElementById('myButton').addEventListener('click', doSomething);
</script>

{{-- ✅ 推奨（厳格モード） --}}
<button data-dix-action="my-plugin.do-something">Click</button>
```

#### 2. 外部スクリプトはCspPolicyProviderで宣言

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

#### 3. 動的スクリプト生成を避ける

```javascript
// ❌ 避けるべき
element.innerHTML = '<script>alert("XSS")</script>';

// ✅ 推奨
const script = document.createElement('script');
script.textContent = 'console.log("Safe")';
document.body.appendChild(script);

// ✅ より安全（Dixlaseユーティリティ使用）
Dixlase.utils.setText(element, 'Safe text');
```

---

## 管理画面での設定

### セキュリティ設定

管理画面の「全体設定 > セキュリティ設定」からCSPを設定できます。

- **CSPを有効にする**: CSPヘッダーの付与を有効/無効にします
- **CSPモード**:
  - 開発モード: 違反を記録するのみでブロックしない
  - 標準モード: nonce付きインラインのみ許可
  - 厳格モード: インライン完全禁止
- **違反をログに記録**: CSP違反をログファイルに記録します
- **信頼済みドメイン**: 外部リソースの読み込みを許可するドメイン
- **カスタムディレクティブ**: JSON形式で高度な設定を指定

### ログの確認

CSP違反ログは「全体設定 > システム > ログ」の「CSP違反」タブで確認できます。

---

## トラブルシューティング

### インラインスクリプトがブロックされる

1. `@cspNonce`ディレクティブを追加してください
2. または、スクリプトを外部ファイルに移動してください
3. 厳格モードの場合は、`Dixlase.widgets.register()` 等を使用してください

### 外部リソースがブロックされる

1. 管理画面の「信頼済みドメイン」に該当ドメインを追加してください
2. または、プラグインで`CspPolicyProvider`を実装してください
3. plugin.jsonに外部依存を宣言してください

### 開発中に問題が発生する

1. CSPモードを「開発モード」に設定してください
2. ブラウザのコンソールで違反レポートを確認してください
3. 必要なディレクティブを追加してください

### CSPを一時的に無効にする

#### 方法1: セーフモードを使用（推奨）

CSP設定を変更して問題が発生した場合、セーフモードが自動的に有効になります。手動で有効化する場合：

```bash
php artisan csp:enable-safe-mode
```

#### 方法2: 管理画面から無効化

管理画面の「全体設定 > セキュリティ設定 > CSP設定」で「CSPを有効にする」をオフにします。

#### 方法3: コマンドラインから無効化

```bash
php artisan csp:disable
```

#### 方法4: .envファイルで無効化

```env
CSP_ENABLED=false
```

#### 方法5: 緊急時の強制無効化

管理画面にアクセスできない場合：

```bash
php artisan csp:disable --force
```

---

## FAQ

### Q1. 標準モードでnonce付きインラインを許可するとセキュリティは下がる？
**A:** 正しく使えば下がりません。nonceは「このレスポンスでのみ有効な実行許可」であり、攻撃者は事前に知ることができません。ただし以下の条件を守る必要があります：
- nonceはレスポンスごとにランダム
- JSからnonceを取得できない
- インラインJS内で危険API（innerHTML、eval等）を多用しない

### Q2. 厳格モードにするとメンテナンス性が下がる？
**A:** 短期的には学習コストが増えますが、中長期では明確に上がります。理由：
- インラインJS依存が消える
- ロジックと表示が分離される
- CSP違反で悩まされない
- 将来のstrict-dynamic / Trusted Typesにも対応しやすい

### Q3. onclick等はなぜ問題？
**A:** CSP的には `script-src-attr 'unsafe-inline'` が必要になり、XSS攻撃の経路になりやすいためです。イベント委譲（`data-dix-action`）を使えば、この問題を回避できます。

### Q4. 既存のプラグインを厳格モードに対応させるには？
**A:** 以下の手順で移行できます：
1. インラインスクリプトを外部JSファイルに移動
2. `Dixlase.widgets.register()` で初期化関数を登録
3. onclick等を `data-dix-action` に置き換え
4. plugin.jsonに `requires_inline_js: false` を明示

**実装例：認証画面のCSP対応**

Dixlaseの二段階認証画面は、CSP厳格モード対応の参考実装として以下のように外部スクリプト化されています：

```html
<!-- Before: インラインスクリプト -->
<script @cspNonce>
document.addEventListener('DOMContentLoaded', function() {
    // 200行以上のインラインコード...
});
</script>

<!-- After: 外部スクリプト化 -->
@push('scripts')
<script src="{{ asset('build/assets/components/two-fa/js/email-challenge.js') }}" @cspNonce></script>
<script @cspNonce>
document.addEventListener('DOMContentLoaded', function() {
    window.initEmailChallenge({
        resendAction: '{{ $resendAction }}',
        csrfToken: '{{ csrf_token() }}',
        codeLength: {{ $codeLength }},
        translations: { /* ... */ }
    });
});
</script>
@endpush
```

**対応ファイル：**
- `resources/src/components/two-fa/js/email-challenge.js` - メール認証
- `resources/src/components/two-fa/js/passkey-challenge.js` - パスキー認証
- `resources/src/components/two-fa/js/recovery-code-challenge.js` - 回復コード認証

これらのファイルは`vite.config.js`でビルド対象に含まれ、CSP厳格モードでも動作します。

### Q5. セーフモードとは何ですか？
**A:** CSP設定を変更して管理画面にアクセスできなくなった場合の救済措置です。セーフモード中はCSPが一時的に無効化され、安全に設定を修正できます。ただし、セキュリティリスクがあるため、設定を修正したら必ず解除してください。

### Q6. CSP設定を保存後、10秒以内に確認しないとどうなる？
**A:** 自動的に前の設定にロールバックされ、セーフモードが有効になります。これにより、誤った設定で管理画面にアクセスできなくなることを防ぎます。

### Q7. コマンドラインからCSP設定を変更できますか？
**A:** はい、`php artisan csp:mode [development|standard|strict]` コマンドでモードを変更できます。また、`php artisan csp:enable` / `php artisan csp:disable` でCSPの有効/無効を切り替えられます。

---

## 移行戦略

### フェーズ1: 開発モード（現在）
- すべてのプラグインが動作
- CSP違反を記録し、問題箇所を特定

### フェーズ2: 標準モードへ移行
- ヘルパー経由のインラインに書き換え
- onclick等を段階的に削除
- プラグインの互換性確認

### フェーズ2.5: Alpine CSP互換コーディングルールの適用（現在）
- 新規コードをAlpine CSP Build互換パターンで記述
- `Alpine.data()`ベースのコンポーネント設計
- 詳細は **[Alpine.js CSP互換コーディングルール](alpine-csp-coding-rules.md)** を参照

### フェーズ3: Alpine CSP Buildへの移行（将来）
- `@alpinejs/csp`パッケージへの切り替え
- 既存コンポーネントのCSP互換への段階的書き換え
- `unsafe-eval`の完全排除

### フェーズ4: 厳格モード（将来）
- コア・公式プラグインをCSP Ready化
- 外部JSのみで完結する設計
- 最大セキュリティ環境の実現

---

## 参考リンク

- [CSP Level 3 仕様](https://www.w3.org/TR/CSP3/)
- [Google CSP Evaluator](https://csp-evaluator.withgoogle.com/)
- [MDN: Content Security Policy](https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP)
- [Alpine.js CSP互換コーディングルール](alpine-csp-coding-rules.md)
- [プラグイン権限基盤ガイドライン](plugin-permission-guidelines.md)
- [セキュリティ設定ガイド](security-settings.md)
