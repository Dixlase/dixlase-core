# キャッシュキー命名規約

Dixlase は `App\Support\Cache\CacheKey` という小さな Builder ヘルパーを提供しており、コア・プラグイン・テーマがすべて同じ形でキャッシュキーを組み立てられるようになっています。この規約に従うことで、プラグイン間のキー衝突を防ぎ、無効化責任の所在を明確化し、マルチサイトロードマップにも合わせられます。

## フォーマット

```
dixlase:{scope}:{owner}:{domain}:{key}
```

サイトスコープ（複合）形式：

```
dixlase:site:{site_id}:{scope}:{owner}:{domain}:{key}
```

| 部位 | 意味 | 例 |
|---|---|---|
| `dixlase` | プロジェクト固定 prefix。常にリテラル。 | `dixlase` |
| `scope` | キャッシュエントリの所有スコープ。 | `core` / `plugin` / `theme` / `site` |
| `owner` | scope 内の所有者識別子。プラグイン/テーマ slug、サイト id、または `core`。 | `core` / `dixlase-pages` / `dixlase-onepage` / `1` |
| `domain` | owner 内の機能ドメイン。 | `manifest` / `routes` / `view` / `query` / `permissions` |
| `key` | domain 内の個別キー。 | `member-42` / `posts-list-page-1` / `v1` |

`core` スコープは owner が暗黙なので、冗長な owner を省略して 4 セグメント形式 `dixlase:core:{domain}:{key}` を使います。

### 例

| ユースケース | キー |
|---|---|
| コアのルートリスト | `dixlase:core:routes:list` |
| プラグインのマニフェストキャッシュ（DixlasePages） | `dixlase:plugin:dixlase-pages:manifest:v1` |
| テーマのビューキャッシュ（DixlaseOnePage） | `dixlase:theme:dixlase-onepage:view:home` |
| サイトごとのナビキャッシュ | `dixlase:site:1:nav:public` |
| サイトごとのプラグインキャッシュ | `dixlase:site:1:plugin:dixlase-pages:manifest:v1` |
| サイトごとのテーマキャッシュ | `dixlase:site:1:theme:dixlase-onepage:view:home` |
| サイトごとのコアキャッシュ | `dixlase:site:1:core:routes:list` |

## タグ

キャッシュタグも同じ形式に従います：

```
dixlase:tag:{scope}:{owner}
dixlase:tag:site:{site_id}:{scope}:{owner}
```

| タグ | 意味 |
|---|---|
| `dixlase:tag:plugin:dixlase-pages` | DixlasePages プラグイン所有のキャッシュエントリをまとめて flush。 |
| `dixlase:tag:theme:dixlase-onepage` | DixlaseOnePage テーマ所有のキャッシュエントリをまとめて flush。 |
| `dixlase:tag:site:1:plugin:dixlase-pages` | サイト 1 における DixlasePages の per-site キャッシュを flush。 |

> タグ機能は Redis / Memcached / array driver でのみ利用可能です。`file`
> と `database` driver はタグ非対応です。タグを使う際は必ず
> `Cache::getStore() instanceof \Illuminate\Cache\TaggableStore` でガードして
> ください。Builder は文字列を生成するだけで、タグの使用を強制しません。

## Builder ヘルパー

キーを手書きする代わりに `App\Support\Cache\CacheKey` (`@api` クラス) を使ってください。

```php
use App\Support\Cache\CacheKey;
use Illuminate\Support\Facades\Cache;

// コアスコープ
$key = CacheKey::core('routes', 'list');
// dixlase:core:routes:list

// プラグインスコープ (slug は plugin.json から)
$key = CacheKey::plugin('dixlase-pages', 'manifest', 'v1');
// dixlase:plugin:dixlase-pages:manifest:v1

// テーマスコープ (slug は theme.json から)
$key = CacheKey::theme('dixlase-onepage', 'view', 'home');
// dixlase:theme:dixlase-onepage:view:home

// サイトスコープ (複合) — fluent builder
$key = CacheKey::site(1)->plugin('dixlase-pages', 'manifest', 'v1');
// dixlase:site:1:plugin:dixlase-pages:manifest:v1

$key = CacheKey::site(1)->key('nav', 'public');
// dixlase:site:1:nav:public

// タグ
$tag     = CacheKey::tag('plugin', 'dixlase-pages');
$siteTag = CacheKey::site(1)->tag('plugin', 'dixlase-pages');
```

## 規約

- **slug の取得元**: `plugin.json` / `theme.json` の slug をそのまま渡す。Builder
  は slug の自動解決をしません（意図的）。Builder は I/O を一切しない純粋な文字列
  生成のみです。
- **site id**: サイトスコープのキーを組むときは、`SiteContextInterface` で解決した
  整数または文字列の id を渡してください。`site_id` を `key` に埋め込まず、Builder
  が然るべき位置に置くようにします。
- **`key` 内のスラッシュ・スペース**: 使わないでください。英数字・ハイフン・ドット
  に留めるのが安全です。キャッシュ driver によってサニタイズが異なります。
- **TTL**: この文書のスコープ外。呼び出し側で設定してください。
- **マイグレーション**: 既存の `Cache::` 呼び出しを一括書き換えしないでください。
  各プラグイン作者が段階的に移行する想定です。キー名を切り替えると一度キャッシュが
  miss する点には注意してください。

## マルチサイトとの関係

裸の `CacheKey` クラスのメソッドは **サイト非依存** のキーを生成します。これは
`APP_ENV` のような本当にグローバルなフラグには正しい使い方です。コンテンツ
リスト・ルートツリー・パーミッションキャッシュ・テーマレンダリングなどサイトごと
に異なるものについては `CacheKey::site($id)->...` でラップしてください。v2+ で
複数サイトが見えるようになったときも、キャッシュが分離された状態が保たれます。

v0.1.0 ではサイトは 1 つだけ (`id=1`) なので Builder は常に動きます。プラグイン
作者が今すぐマルチサイト配管を入れる必要はありません — 「概念的にサイトごと」の
ものについてサイトスコープ形式を使えば十分です。
