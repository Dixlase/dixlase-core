# プラグイン連携ガイド - Contracts & DTO

Dixlaseでは、コアを最小限に保ちながら、プラグイン間で安全に連携できるように **Contracts（契約）** と **DTO（Data Transfer Object）** を提供しています。

## 設計思想

- **コアは最小限**: セキュリティと基盤機能のみ
- **プラグインで拡張**: コンテンツ作成や機能追加はプラグインで実装
- **疎結合**: プラグイン同士は直接依存せず、契約を介して連携
- **型安全**: PHP 8.2+ の機能を活用した堅牢な設計

---

## 基本コンポーネント

### 1. LinkableInterface（契約）

リンク可能なコンテンツの最小契約です。

**場所**: `app/Contracts/PluginIntegration/LinkableInterface.php`

```php
interface LinkableInterface
{
    public function getId(): string;
    public function getTitle(): string;
    public function getUrl(): string;
    public function getType(): string;
    public function getSource(): string;
    public function getSourceTable(): ?string;
}
```

### 2. LinkableDTO（データ転送オブジェクト）

プラグイン間でコンテンツ情報を受け渡しする不変オブジェクトです。

**場所**: `app/DTO/PluginIntegration/LinkableDTO.php`

```php
$dto = new LinkableDTO(
    id: '01JCABCDEFGHIJKLMNOPQRSTUV',
    title: 'Dixlaseの使い方',
    url: 'https://example.com/blog/how-to-use-dixlase',
    type: 'post',
    source: 'dixlase-blog',
    sourceTable: 'blog_posts',
    locale: 'ja',
    meta: ['slug' => 'how-to-use-dixlase'],
);
```

### 3. SearchQueryDTO（検索クエリ）

検索条件を表現するDTOです。

**場所**: `app/DTO/PluginIntegration/SearchQueryDTO.php`

```php
$query = new SearchQueryDTO(
    q: 'Dixlase',
    page: 1,
    perPage: 20,
    filters: ['status' => 'published', 'locale' => 'ja'],
    sort: ['published_at' => 'desc'],
);
```

### 4. PaginatedResultDTO（ページネーション結果）

検索結果とページネーション情報を含むDTOです。

**場所**: `app/DTO/PluginIntegration/PaginatedResultDTO.php`

```php
$result = new PaginatedResultDTO(
    items: [$dto1, $dto2, $dto3],
    total: 100,
    page: 1,
    perPage: 20,
);
```

---

## source フィールドの命名規約

### コアの場合
```php
'source' => 'core'
'type' => 'page'    // 固定ページ
'type' => 'media'   // メディア
'type' => 'member'  // メンバー
```

### プラグインの場合
```php
'source' => 'dixlase-blog'    // ブログプラグイン
'source' => 'dixlase-pages'   // ページプラグイン
'source' => 'dixlase-inquiry' // 問い合わせプラグイン
'source' => 'dixlase-shop'    // ECプラグイン
```

**規約**:
- コア: `'core'` 固定
- プラグイン: `plugin.json` の `name` フィールドと一致させる

---

## 使用例

### 例1: ブログプラグインでの実装

```php
<?php

namespace DixlaseBlog\Services;

use App\DTO\PluginIntegration\LinkableDTO;
use App\DTO\PluginIntegration\SearchQueryDTO;
use App\DTO\PluginIntegration\PaginatedResultDTO;

class BlogLinkSource
{
    public function search(SearchQueryDTO $query): PaginatedResultDTO
    {
        $posts = $this->repo->searchPublished($query);
        
        $items = array_map(function($post) {
            return new LinkableDTO(
                id: $post->id,
                title: $post->title,
                url: route('blog.show', ['slug' => $post->slug]),
                type: 'post',
                source: 'dixlase-blog',
                sourceTable: 'blog_posts',
                locale: $post->locale,
                meta: [
                    'slug' => $post->slug,
                    'published_at' => $post->published_at,
                ],
            );
        }, $posts->items);
        
        return new PaginatedResultDTO(
            items: $items,
            total: $posts->total,
            page: $posts->page,
            perPage: $posts->perPage,
        );
    }
    
    public function findById(string $id): ?LinkableDTO
    {
        $post = $this->repo->findPublishedById($id);
        if (!$post) return null;
        
        return new LinkableDTO(
            id: $post->id,
            title: $post->title,
            url: route('blog.show', ['slug' => $post->slug]),
            type: 'post',
            source: 'dixlase-blog',
            sourceTable: 'blog_posts',
            locale: $post->locale,
            meta: ['slug' => $post->slug],
        );
    }
}
```

### 例2: メニュープラグインでの利用

```php
<?php

namespace DixlaseMenu\Services;

use App\DTO\PluginIntegration\LinkableDTO;
use App\DTO\PluginIntegration\SearchQueryDTO;

class MenuItemService
{
    public function addLinkFromBlog(string $blogPostId): void
    {
        // ブログプラグインから記事情報を取得
        $linkSource = app('dixlase-blog.link-source');
        $link = $linkSource->findById($blogPostId);
        
        if (!$link) {
            throw new \Exception('ブログ記事が見つかりません');
        }
        
        // メニューアイテムとして保存
        MenuItem::create([
            'title' => $link->title,
            'url' => $link->url,
            'linkable_type' => $link->type,
            'linkable_id' => $link->id,
            'source' => $link->source,
            'source_table' => $link->sourceTable,
        ]);
    }
}
```

### 例3: リクエストからSearchQueryDTOを生成

```php
<?php

namespace DixlaseMenu\Http\Controllers;

use App\DTO\PluginIntegration\SearchQueryDTO;
use Illuminate\Http\Request;

class LinkSourceController
{
    public function search(Request $request, string $sourceKey)
    {
        // リクエストからDTOを生成
        $query = SearchQueryDTO::fromRequest($request);
        
        // または手動で生成
        $query = new SearchQueryDTO(
            q: $request->query('q', ''),
            page: (int)$request->query('page', 1),
            perPage: (int)$request->query('per_page', 20),
            filters: [
                'status' => 'published',
                'locale' => app()->getLocale(),
            ],
            sort: ['published_at' => 'desc'],
        );
        
        $linkSource = $this->registry->byKey($sourceKey);
        return $linkSource->search($query);
    }
}
```

### 例4: LaravelページネータからPaginatedResultDTOを生成

```php
<?php

namespace DixlaseBlog\Services;

use App\DTO\PluginIntegration\LinkableDTO;
use App\DTO\PluginIntegration\PaginatedResultDTO;

class BlogRepository
{
    public function searchPublished(SearchQueryDTO $query): PaginatedResultDTO
    {
        $paginator = BlogPost::query()
            ->where('status', 'published')
            ->paginate($query->perPage, page: $query->page);
        
        // Laravelのページネータから変換
        return PaginatedResultDTO::fromPaginator(
            $paginator,
            fn($post) => new LinkableDTO(
                id: $post->id,
                title: $post->title,
                url: route('blog.show', ['slug' => $post->slug]),
                type: 'post',
                source: 'dixlase-blog',
                sourceTable: 'blog_posts',
                locale: $post->locale,
                meta: ['slug' => $post->slug],
            )
        );
    }
}
```

---

## JSON出力例

### ブログ記事
```json
{
  "id": "01JCABCDEFGHIJKLMNOPQRSTUV",
  "title": "Dixlaseの使い方",
  "url": "https://example.com/blog/how-to-use-dixlase",
  "type": "post",
  "source": "dixlase-blog",
  "source_table": "blog_posts",
  "locale": "ja",
  "meta": {
    "slug": "how-to-use-dixlase",
    "published_at": "2025-01-15T10:30:00+09:00"
  }
}
```

### ページネーション結果
```json
{
  "items": [
    {
      "id": "01JCABCDEFGHIJKLMNOPQRSTUV",
      "title": "Dixlaseの使い方",
      "url": "https://example.com/blog/how-to-use-dixlase",
      "type": "post",
      "source": "dixlase-blog",
      "source_table": "blog_posts",
      "locale": "ja",
      "meta": {
        "slug": "how-to-use-dixlase"
      }
    }
  ],
  "total": 100,
  "page": 1,
  "per_page": 20,
  "total_pages": 5,
  "has_next_page": true,
  "has_previous_page": false,
  "from": 1,
  "to": 20
}
```

---

## ベストプラクティス

### 1. 公開済みコンテンツのみ返す
```php
public function search(SearchQueryDTO $query): PaginatedResultDTO
{
    // 下書きや非公開は除外
    $posts = BlogPost::query()
        ->where('status', 'published')
        ->where('published_at', '<=', now())
        ->get();
    
    // ...
}
```

### 2. URL生成はルート名で
```php
// ❌ 悪い例: URLを直接生成
'url' => '/blog/' . $post->slug

// ✅ 良い例: ルート名を使用
'url' => route('blog.show', ['slug' => $post->slug])
```

### 3. 権限チェックを忘れずに
```php
public function search(SearchQueryDTO $query): PaginatedResultDTO
{
    // 閲覧権限がない項目は除外
    $posts = BlogPost::query()
        ->where('status', 'published')
        ->when(!auth()->user()?->can('view-draft'), function($q) {
            $q->where('status', '!=', 'draft');
        })
        ->get();
    
    // ...
}
```

### 4. sourceとsourceTableを必ず設定
```php
return new LinkableDTO(
    // ...
    source: 'dixlase-blog',        // プラグインスラッグ
    sourceTable: 'blog_posts',     // テーブル名（デバッグ用）
);
```

---

## トラブルシューティング

### Q: プラグイン削除時にメニューが壊れる

**A**: プラグイン削除前に警告を表示する

```php
// プラグイン削除前のチェック
$affectedMenus = MenuItem::where('source', 'dixlase-blog')->exists();

if ($affectedMenus) {
    throw new \Exception(
        'このプラグインを使用しているメニューが存在します。' .
        '先にメニューから削除してください。'
    );
}
```

### Q: データの整合性をチェックしたい

**A**: 定期メンテナンスコマンドを作成

```php
foreach ($menuItems as $item) {
    $exists = DB::table($item->source_table)
        ->where('id', $item->linkable_id)
        ->exists();
    
    if (!$exists) {
        Log::warning('Broken link detected', [
            'menu_item' => $item->id,
            'source' => $item->source,
            'table' => $item->source_table,
        ]);
    }
}
```

---

## 今後の拡張予定

- **イベント駆動アーキテクチャ**: プラグイン間のイベント通知
- **Hook/Filter システム**: UI拡張・データ加工
- **タグ付けシステム**: 横断的なタグ管理
- **コメントシステム**: 複数コンテンツタイプへのコメント
- **検索統合**: 全プラグインの横断検索

---

## 参考資料

- [LinkableInterface.php](../app/Contracts/PluginIntegration/LinkableInterface.php)
- [LinkableDTO.php](../app/DTO/PluginIntegration/LinkableDTO.php)
- [SearchQueryDTO.php](../app/DTO/PluginIntegration/SearchQueryDTO.php)
- [PaginatedResultDTO.php](../app/DTO/PluginIntegration/PaginatedResultDTO.php)
