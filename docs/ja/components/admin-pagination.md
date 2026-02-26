# ページネーションコンポーネント

管理画面・フロントページ共用のレスポンシブページネーションコンポーネントです。

## 基本的な使用方法

```blade
@include('components.pagination', ['pagination' => $pagination])
```

## プロパティ

| プロパティ | 型 | デフォルト値 | 説明 |
|-----------|---|-------------|-----|
| `pagination` | array | 必須 | ページネーション情報 |
| `route` | string | `'admin.settings.systems.logs'` | ルート名 |
| `routeParams` | array | `[]` | ルートパラメータ |
| `mobilePageRange` | int | `1` | スマホでの表示ページ範囲 |
| `desktopPageRange` | int | `2` | デスクトップでの表示ページ範囲 |

## ページネーション配列の構造

```php
$pagination = [
    'current_page' => 1,        // 現在のページ
    'last_page' => 10,          // 最後のページ
    'prev_page' => null,        // 前のページ（nullの場合は無効）
    'next_page' => 2,           // 次のページ（nullの場合は無効）
];
```

## 使用例

### 1. 基本的な使用（ログページ）

```blade
@include('components.pagination', [
    'pagination' => $pagination,
    'route' => 'admin.settings.systems.logs',
    'routeParams' => ['type' => $logType]
])
```

### 2. メンバー一覧ページ

```blade
@include('components.pagination', [
    'pagination' => $pagination,
    'route' => 'admin.members.index',
    'routeParams' => ['search' => request('search')]
])
```

### 3. カスタムページ範囲

```blade
@include('components.pagination', [
    'pagination' => $pagination,
    'route' => 'admin.posts.index',
    'mobilePageRange' => 2,
    'desktopPageRange' => 3
])
```

### 4. 複数パラメータの例

```blade
@include('components.pagination', [
    'pagination' => $pagination,
    'route' => 'admin.search.results',
    'routeParams' => [
        'query' => request('query'),
        'category' => request('category'),
        'sort' => request('sort')
    ]
])
```

## レスポンシブデザイン

### スマホレイアウト（768px未満）
```
┌─────────────────────┐
│    Page 1 of 5      │  ← ページ情報
├─────────────────────┤
│前へ │ 1 2 3 │ 次へ  │  ← ナビゲーション
└─────────────────────┘
```

### デスクトップレイアウト（768px以上）
```
┌─────────────────────────────────────────┐
│前へ Page 1 of 5 次へ    1 2 3 4 5 ...10 │
└─────────────────────────────────────────┘
```

## CSSクラス

使用されるCSSクラスは `_admin.scss` で定義されています：

- `.pagination-button`: 前へ・次へボタン
- `.pagination-number`: ページ番号ボタン
- `.pagination-ellipsis`: 省略記号
- `.pagination-info`: ページ情報

## 注意事項

- `pagination` が `null` または `last_page` が1以下の場合、コンポーネントは表示されません
- ルートパラメータは `array_merge()` で結合されるため、既存パラメータを上書きできます
- ページ範囲はスマホとデスクトップで個別に設定可能です
