# URL の多言語化

Dixlase はフロントエンドの URL に **path-prefix 方式** を採用しています: `/ja/about`, `/en/about`。管理画面 (`/admin/...`) には locale prefix は付きません — 運用者はプロフィール設定で選んだ言語で操作します。

このドキュメントは URL 戦略、locale 解決順、プラグイン/テーマが利用する統合ポイントを説明します。

## TL;DR

- **フロント URL**: `/{locale}/<path>` (例: `/ja/about`, `/en/posts`)
- **管理画面 URL**: `/admin/<path>` — locale prefix なし
- **locale なしフロント URL** (例: `/about`): `/{Site.primary_locale}/about` へ 302 リダイレクト
- **フロント解決順**: URL > Cookie `dixlase_locale` > Accept-Language > `Site.primary_locale` > `en`
- **管理画面解決順**: `member.locale` > `Site.primary_locale` > Accept-Language > `en`
- **未翻訳コンテンツ**: `MissingTranslationHandler` Contract 経由で `/{Site.primary_locale}/<path>` へ 302 (プラグインで上書き可能)
- **サポート locale (v0.1.0)**: `ja`, `en` のみ。動的拡張は v0.2 以降

## なぜ path prefix (案 A) なのか

検討した候補:

| 戦略 | URL 例 | 長所 | 短所 |
|---|---|---|---|
| **A. Path prefix** | `/ja/about` | SEO 認識◎、SSL 単一、シェア性◎ | URL がやや長い |
| B. Subdomain | `ja.example.com` | 完全分離、CDN 分離可 | DNS / SSL を locale 数だけ用意 |
| C. TLD | `example.jp` | 国別ターゲティング◎ | 運用負荷最大 |

案 A は Laravel コミュニティのデファクトで、運用コストがゼロ、CMS の典型用途に十分。B/C が必要なサイトは、需要が見えた時点でプラグイン/テーマで上に被せられます。

## Locale 解決

### フロント (`SetFrontLocale` middleware)

解決順、最初に当たったものを採用:

1. **URL prefix** — `/ja/...` → `ja`
2. **Cookie `dixlase_locale`** — 言語スイッチャーで明示的に切替えた時のみ書き込まれる
3. **`Accept-Language` ヘッダー** — リスト中で最初にサポート対象に該当した locale
4. **`Site.primary_locale`** — `/admin/settings/base/site` で設定されるサイト既定言語
5. **`config('app.fallback_locale')`** — 既定 `en`

解決された locale は `App::setLocale()` で適用され、`URL::defaults(['locale' => $resolved])` に登録されるため、以後の `route()` 呼び出しは自動で locale を引き継ぎます。

### 管理画面 (`SetAdminLocale` middleware)

管理画面の URL には locale prefix がありません。middleware の解決順:

1. **`member.locale`** — 運用者のプロフィール設定
2. **`Site.primary_locale`** — プロフィール未設定時
3. **`Accept-Language`** — 未認証画面 (ログイン、パスワードリセット)
4. **`config('app.fallback_locale')`** — `en`

### Cookie 書き込みポリシー

`dixlase_locale` Cookie は **言語スイッチャー endpoint (`POST /locale/switch`)** からのみ書き込まれます。`SetFrontLocale` では書きません。これにより:

- `/ja/about` を一度開いただけでは、その後 locale なしアクセスで日本語に固定されない
- スイッチャーで明示的に選択した時だけ、その選択が 1 年保持される

## URL 戦略の詳細

### locale なし URL は redirect

`/about` への直接アクセス (locale prefix なし) は曖昧として扱います。catch-all ルートが `Site.primary_locale` から target locale を決定し、`/{locale}/about` へ 302 リダイレクトします。301 でなく 302 を使うのは、v2 で戦略変更があってもキャッシュ汚染を残さないため。

### 未翻訳ページ

ルートは存在するが現在 locale で翻訳が無い場合、`MissingTranslationHandler` Contract が挙動を決定します。コア既定実装は `/{Site.primary_locale}/<path>` へ 302 リダイレクトします。

プラグイン (DixlaseI18n、DixlaseRedirects 等) は binding を差し替えて挙動を変更できます:

```php
// プラグインの ServiceProvider 内
$this->app->bind(
    \App\Contracts\I18n\MissingTranslationHandler::class,
    \Plugins\DixlaseI18n\Services\CustomMissingTranslationHandler::class,
);
```

注意: これが発火するのは **「ルートはマッチしたが現在 locale のコンテンツが無い」** ケースのみ。どのルートにもマッチしない URL は通常通り 404 が返ります。

### 静的アセット・API ルート

以下のルートは locale group の **外側** にあります:

- `/assets/{type}/{file}` — テーマ/管理画面/プラグインの静的アセット
- `/csp-report` — CSP 違反レポート endpoint
- `/install/...` — インストーラ (UI 言語は `Accept-Language` で決定)
- `/api/v1/...` — JSON API (クライアントが必要なら `Accept-Language` を送信)
- `/locale/switch` — 言語スイッチャー endpoint
- `/admin/...` — 管理画面

## プラグイン / テーマ統合

### フロントルートは自動でラップされる

`PluginHelper::loadEnabledWebRoutes()` で読み込まれるプラグインの web ルート、およびテーマのフロントルートは、自動的に locale group 内に登録されます。プラグイン/テーマ作者は通常通りルートを書けます:

```php
// プラグインの routes/web.php
Route::get('/posts/{slug}', [PostController::class, 'show'])->name('posts.show');
```

このルートは `/ja/posts/hello` と `/en/posts/hello` の両方からアクセス可能になり、`route('posts.show', ['slug' => 'hello'])` は現在の locale を含む URL を返します。

### locale 対応 URL の生成

```php
// 現在の locale を自動使用
route('posts.show', ['slug' => 'hello']);

// 明示指定
route('posts.show', ['locale' => 'ja', 'slug' => 'hello']);

// 同じ URL の別 locale 版を取得 (言語スイッチャーリンク用)
\App\Helpers\LocaleHelper::switchLocaleUrl('ja');
```

### `LocalizedUrlProvider` Contract

多言語コンテンツを公開するプラグインは `App\Contracts\I18n\LocalizedUrlProvider` を実装することで、現在のリクエストに対する代替言語 URL を露出できます。DixlaseSEO はこの Contract を使って `<link rel="alternate" hreflang="...">` タグを生成します。

```php
interface LocalizedUrlProvider
{
    /** @return array<string, string>  例: ['ja' => '/ja/posts/hello', 'en' => '/en/posts/hello'] */
    public function getAlternateUrls(\Illuminate\Http\Request $request): array;
}
```

## Site.primary_locale

「サイト既定言語」の正規データは `sites.primary_locale` 列です。`SiteContext` 経由で読みます:

```php
use App\Facades\SiteContext;

$default = SiteContext::currentSite()->primary_locale;
```

`SiteSetting` の `'locale'` キーは後方互換のためにこの列を shadow しています (`CoreSettingDefinitions.php`) が、新規コードは `Site.primary_locale` を直接読むこと。shadow は将来の minor リリースで deprecate される可能性があります。

`/admin/settings/base/site` の管理画面更新は、`Site.primary_locale`、legacy `SiteSetting('locale')` shadow、`.env` (`APP_LOCALE`, `APP_FALLBACK_LOCALE`) を atomic に更新します。

## マルチサイトとの統合

v0.1.0 では primary site のみで URL は単純に `/{locale}/<path>` です。`Site.path_prefix` 列 (v2 で URL prefix によるサイト識別、例: `/blog` → site_id 2) は primary site では `null` のため、衝突は発生しません。

v2 で multi-site path-prefix ルーティングが入った時の URL 順序は:

```
/{locale}/{path_prefix}/{path}     ← 戦略: locale 最外
```

Locale 解決はサイト解決の前に行われます: `SetFrontLocale` が locale prefix を剥がし、その後 `ResolveSiteContext` が残りの path を `sites.path_prefix` と照合します。完全分離が必要なサイトは subdomain パターン (`acme.example.com/ja/...`) を使うこともでき、その場合 locale と site は完全に直交します。

マルチサイト全体のアーキテクチャは `docs/ja/development/multisite.md` を参照。

## v0.1.0 では出荷しないもの

これらは v0.1.0 の明示的な non-goal で、v2 または多言語プラグイン側に回します:

- 翻訳済み CMS コンテンツ (`Pages`, `Menus`, `Inquiry` の翻訳)
- 翻訳エディタ UI / translation memory / 用語集
- `<link rel="alternate" hreflang>` タグ生成 (DixlaseSEO 側)
- 言語スイッチャー Blade コンポーネント (テーマ または DixlaseI18n)
- Carbon locale 自動切替 (既定以上のもの)
- 数値・通貨フォーマットの地域化
- RTL CSS / 論理プロパティ
- 動的 locale 一覧 (de, fr, zh, ...) — v0.1.0 は `ja|en` のみ

Contract と middleware の土台はコアにあるため、これらの機能は URL レイアウトを変更せずに後付けできます。

## 参照

- `App\Helpers\LocaleHelper` — locale 一覧、ヘルパー、スイッチ URL ビルダー
- `App\Http\Middleware\SetFrontLocale` — フロント locale 解決
- `App\Http\Middleware\SetAdminLocale` — 管理画面 locale 解決
- `App\Contracts\I18n\LocalizedUrlProvider` — 代替 URL 露出 (SEO / hreflang)
- `App\Contracts\I18n\MissingTranslationHandler` — 未翻訳コンテンツのフォールバックポリシー
- `App\Services\I18n\DefaultMissingTranslationHandler` — コア既定実装
- `App\Models\Site::primary_locale` — サイトごとの正規既定言語
- `docs/ja/development/multisite.md` — マルチサイトアーキテクチャ
- `PLUGIN-API.md` — Plugin API 公開エントリ
