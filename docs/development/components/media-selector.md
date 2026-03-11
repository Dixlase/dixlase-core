# Media Selector Modal Usage

## Overview

The media selector modal component is a reusable component that allows selecting uploaded images and files from the Media Master.

## Basic Usage

### 1. Including the Component

Include the component in your Blade template:

```blade
@include('components.media-selector', [
    'id' => 'myMediaSelector',
    'inputId' => 'featured_image_id',
    'previewId' => 'featured_image_preview',
    'multiple' => false
])
```

### 2. Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `id` | string | 'mediaSelectorModal' | Modal ID |
| `inputId` | string | 'media_id' | ID of the input field that stores the selected media ID |
| `previewId` | string | 'media_preview' | ID of the preview display area |
| `multiple` | boolean | false | Whether to allow multiple selection |
| `allowedTypes` | array | ['image/jpeg', 'image/png', 'image/gif', 'image/webp'] | Allowed file types |

### 3. HTML Form Example

```blade
<form action="{{ route('pages.store') }}" method="POST">
    @csrf

    <!-- Hidden field (stores the selected media ID) -->
    <input type="hidden" id="featured_image_id" name="featured_image_id" value="">

    <!-- Preview display area -->
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            Featured Image
        </label>
        <div id="featured_image_preview" class="mb-2">
            <!-- Preview of the selected image appears here -->
        </div>
        <button type="button"
                onclick="openMediaSelector('myMediaSelector', 'featured_image_id', 'featured_image_preview', false)"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            Select Image
        </button>
    </div>

    <!-- Media selector modal -->
    @include('components.media-selector', [
        'id' => 'myMediaSelector',
        'inputId' => 'featured_image_id',
        'previewId' => 'featured_image_preview',
        'multiple' => false
    ])

    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg">
        Save
    </button>
</form>
```

### 4. Multiple Selection Example

```blade
<!-- For selecting multiple images -->
<input type="hidden" id="gallery_images" name="gallery_images" value="">

<div id="gallery_preview" class="grid grid-cols-4 gap-4 mb-2">
    <!-- Preview of selected images appears here -->
</div>

<button type="button"
        onclick="openMediaSelector('gallerySelector', 'gallery_images', 'gallery_preview', true)"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg">
    Select Gallery Images
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

Opens the modal.

**Parameters:**
- `modalId`: Modal ID
- `inputId`: Input field ID
- `previewId`: Preview area ID
- `multiple`: Whether to allow multiple selection (boolean)

**Example:**
```javascript
openMediaSelector('myMediaSelector', 'featured_image_id', 'featured_image_preview', false);
```

### closeMediaSelector(modalId)

Closes the modal.

**Example:**
```javascript
closeMediaSelector('myMediaSelector');
```

### removeMediaPreview(inputId, previewId, mediaId)

Removes a specific media item from the preview.

**Parameters:**
- `inputId`: Input field ID
- `previewId`: Preview area ID
- `mediaId`: ID of the media to remove (only for multiple selection)

## Controller Handling

### Single Selection

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

### Multiple Selection

```php
public function store(Request $request)
{
    $request->validate([
        'gallery_images' => 'nullable|string',
    ]);

    // Convert comma-separated IDs to an array
    $imageIds = $request->gallery_images
        ? explode(',', $request->gallery_images)
        : [];

    $page = Page::create([
        'title' => $request->title,
    ]);

    // Save via relationship
    $page->galleryImages()->sync($imageIds);

    return redirect()->route('pages.show', $page);
}
```

## Displaying Media Information

To display information about the selected media:

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

## Styling

The modal is styled with Tailwind CSS and supports dark mode. To add custom styles:

```css
/* Custom style example */
.media-selector-item.selected .relative {
    @apply ring-4 ring-blue-600;
}
```

## Troubleshooting

### Modal Does Not Appear

1. Verify the component is included via `@include`
2. Ensure the `id` parameter is unique
3. Check the browser console for JavaScript errors

### Selected Image Is Not Saved

1. Verify the hidden field's `name` attribute is correct
2. Check the controller's validation rules
3. Ensure the form's `method` is `POST` or `PUT`

### Preview Does Not Display

1. Verify the `previewId` is correct
2. Ensure the preview area element exists
3. Check that the `confirmMediaSelection` JavaScript function is working correctly

## Advanced Usage

### Displaying Existing Media on Load

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

### Custom Filter

```javascript
// Custom filter to display only images
document.getElementById('myMediaSelector-type-filter').value = 'image';
```

## API Endpoint

API endpoint for retrieving the media list:

```
GET /admin/media/api?page=1&per_page=20&search=&type=image
```

**Response:**
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
