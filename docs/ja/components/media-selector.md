# メディア選択モーダルの使用方法

## 概要

メディア選択モーダルコンポーネントは、メディアマスターからアップロード済みの画像やファイルを選択できる再利用可能なコンポーネントです。

## 基本的な使い方

### 1. コンポーネントの読み込み

Bladeテンプレートでコンポーネントをインクルードします：

```blade
@include('components.media-selector', [
    'id' => 'myMediaSelector',
    'inputId' => 'featured_image_id',
    'previewId' => 'featured_image_preview',
    'multiple' => false
])
```

### 2. パラメータ

| パラメータ | 型 | デフォルト | 説明 |
|-----------|-----|-----------|------|
| `id` | string | 'mediaSelectorModal' | モーダルのID |
| `inputId` | string | 'media_id' | 選択したメディアIDを格納する入力フィールドのID |
| `previewId` | string | 'media_preview' | プレビュー表示エリアのID |
| `multiple` | boolean | false | 複数選択を許可するか |
| `allowedTypes` | array | ['image/jpeg', 'image/png', 'image/gif', 'image/webp'] | 許可するファイルタイプ |

### 3. HTMLフォームの例

```blade
<form action="{{ route('pages.store') }}" method="POST">
    @csrf
    
    <!-- 隠しフィールド（選択したメディアIDが入る） -->
    <input type="hidden" id="featured_image_id" name="featured_image_id" value="">
    
    <!-- プレビュー表示エリア -->
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            アイキャッチ画像
        </label>
        <div id="featured_image_preview" class="mb-2">
            <!-- 選択した画像のプレビューがここに表示される -->
        </div>
        <button type="button" 
                onclick="openMediaSelector('myMediaSelector', 'featured_image_id', 'featured_image_preview', false)"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            画像を選択
        </button>
    </div>
    
    <!-- メディア選択モーダル -->
    @include('components.media-selector', [
        'id' => 'myMediaSelector',
        'inputId' => 'featured_image_id',
        'previewId' => 'featured_image_preview',
        'multiple' => false
    ])
    
    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg">
        保存
    </button>
</form>
```

### 4. 複数選択の例

```blade
<!-- 複数の画像を選択する場合 -->
<input type="hidden" id="gallery_images" name="gallery_images" value="">

<div id="gallery_preview" class="grid grid-cols-4 gap-4 mb-2">
    <!-- 選択した画像のプレビューがここに表示される -->
</div>

<button type="button" 
        onclick="openMediaSelector('gallerySelector', 'gallery_images', 'gallery_preview', true)"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg">
    ギャラリー画像を選択
</button>

@include('components.media-selector', [
    'id' => 'gallerySelector',
    'inputId' => 'gallery_images',
    'previewId' => 'gallery_preview',
    'multiple' => true
])
```

## JavaScript API

### openMediaSelector(modalId, inputId, previewId, multiple)

モーダルを開きます。

**パラメータ:**
- `modalId`: モーダルのID
- `inputId`: 入力フィールドのID
- `previewId`: プレビューエリアのID
- `multiple`: 複数選択を許可するか（boolean）

**例:**
```javascript
openMediaSelector('myMediaSelector', 'featured_image_id', 'featured_image_preview', false);
```

### closeMediaSelector(modalId)

モーダルを閉じます。

**例:**
```javascript
closeMediaSelector('myMediaSelector');
```

### removeMediaPreview(inputId, previewId, mediaId)

プレビューから特定のメディアを削除します。

**パラメータ:**
- `inputId`: 入力フィールドのID
- `previewId`: プレビューエリアのID
- `mediaId`: 削除するメディアのID（複数選択の場合のみ）

## コントローラーでの処理

### 単一選択の場合

```php
public function store(Request $request)
{
    $request->validate([
        'featured_image_id' => 'nullable|exists:media,id',
    ]);
    
    $page = Page::create([
        'title' => $request->title,
        'featured_image_id' => $request->featured_image_id,
    ]);
    
    return redirect()->route('pages.show', $page);
}
```

### 複数選択の場合

```php
public function store(Request $request)
{
    $request->validate([
        'gallery_images' => 'nullable|string',
    ]);
    
    // カンマ区切りのIDを配列に変換
    $imageIds = $request->gallery_images 
        ? explode(',', $request->gallery_images) 
        : [];
    
    $page = Page::create([
        'title' => $request->title,
    ]);
    
    // リレーションシップで保存
    $page->galleryImages()->sync($imageIds);
    
    return redirect()->route('pages.show', $page);
}
```

## メディア情報の取得

選択したメディアの情報を表示する場合：

```blade
@if($page->featuredImage)
    <img src="{{ asset('storage/media/' . $page->featuredImage->path) }}" 
         alt="{{ $page->featuredImage->alt_text ?? $page->featuredImage->name }}"
         title="{{ $page->featuredImage->caption }}">
    
    @if($page->featuredImage->caption)
        <p class="text-sm text-gray-600">{{ $page->featuredImage->caption }}</p>
    @endif
    
    @if($page->featuredImage->description)
        <p class="text-sm text-gray-500">{{ $page->featuredImage->description }}</p>
    @endif
@endif
```

## スタイリング

モーダルはTailwind CSSでスタイリングされており、ダークモード対応です。カスタムスタイルを追加する場合：

```css
/* カスタムスタイルの例 */
.media-selector-item.selected .relative {
    @apply ring-4 ring-blue-600;
}
```

## トラブルシューティング

### モーダルが表示されない

1. `@include`でコンポーネントを読み込んでいるか確認
2. `id`パラメータが一意であるか確認
3. JavaScriptエラーがないかブラウザのコンソールを確認

### 選択した画像が保存されない

1. 隠しフィールドの`name`属性が正しいか確認
2. コントローラーのバリデーションルールを確認
3. フォームの`method`が`POST`または`PUT`であるか確認

### プレビューが表示されない

1. `previewId`が正しいか確認
2. プレビューエリアの要素が存在するか確認
3. JavaScriptの`confirmMediaSelection`関数が正しく動作しているか確認

## 高度な使用例

### 既存のメディアを初期表示

```blade
<div id="featured_image_preview">
    @if($page->featuredImage)
        <div class="relative inline-block">
            <img src="{{ asset('storage/media/' . $page->featuredImage->path) }}" 
                 alt="{{ $page->featuredImage->name }}" 
                 class="w-32 h-32 object-cover rounded">
            <button type="button" 
                    onclick="removeMediaPreview('featured_image_id', 'featured_image_preview')" 
                    class="absolute -top-2 -right-2 w-6 h-6 bg-red-600 text-white rounded-full hover:bg-red-700">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>
    @endif
</div>
```

### カスタムフィルター

```javascript
// 画像のみを表示するカスタムフィルター
document.getElementById('myMediaSelector-type-filter').value = 'image';
```

## API エンドポイント

メディア一覧を取得するAPIエンドポイント：

```
GET /admin/media/api?page=1&per_page=20&search=&type=image
```

**レスポンス:**
```json
{
    "success": true,
    "media": {
        "data": [
            {
                "id": 1,
                "name": "example.jpg",
                "type": "image/jpeg",
                "url": "http://localhost/storage/media/example.jpg",
                "caption": "Example caption",
                "alt_text": "Example alt text",
                "description": "Example description"
            }
        ],
        "current_page": 1,
        "last_page": 5
    }
}
```
