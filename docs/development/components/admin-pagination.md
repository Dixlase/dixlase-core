# Pagination Component

A responsive pagination component shared between the admin panel and front pages.

## Basic Usage

```blade
@include('components.pagination', ['pagination' => $pagination])
```

## Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `pagination` | array | Required | Pagination information |
| `route` | string | `'admin.settings.systems.logs'` | Route name |
| `routeParams` | array | `[]` | Route parameters |
| `mobilePageRange` | int | `1` | Page range displayed on mobile |
| `desktopPageRange` | int | `2` | Page range displayed on desktop |

## Pagination Array Structure

```php
$pagination = [
    'current_page' => 1,        // Current page
    'last_page' => 10,          // Last page
    'prev_page' => null,        // Previous page (null when disabled)
    'next_page' => 2,           // Next page (null when disabled)
];
```

## Usage Examples

### 1. Basic Usage (Log Page)

```blade
@include('components.pagination', [
    'pagination' => $pagination,
    'route' => 'admin.settings.systems.logs',
    'routeParams' => ['type' => $logType]
])
```

### 2. Member List Page

```blade
@include('components.pagination', [
    'pagination' => $pagination,
    'route' => 'admin.members.index',
    'routeParams' => ['search' => request('search')]
])
```

### 3. Custom Page Range

```blade
@include('components.pagination', [
    'pagination' => $pagination,
    'route' => 'admin.posts.index',
    'mobilePageRange' => 2,
    'desktopPageRange' => 3
])
```

### 4. Multiple Parameters Example

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

## Responsive Design

### Mobile Layout (below 768px)
```
┌─────────────────────┐
│    Page 1 of 5      │  ← Page info
├─────────────────────┤
│Prev │ 1 2 3 │ Next  │  ← Navigation
└─────────────────────┘
```

### Desktop Layout (768px and above)
```
┌─────────────────────────────────────────┐
│Prev Page 1 of 5 Next    1 2 3 4 5 ...10│
└─────────────────────────────────────────┘
```

## CSS Classes

The CSS classes used are defined in `_admin.scss`:

- `.pagination-button`: Previous / Next buttons
- `.pagination-number`: Page number buttons
- `.pagination-ellipsis`: Ellipsis
- `.pagination-info`: Page information

## Notes

- The component is not rendered when `pagination` is `null` or `last_page` is 1 or less
- Route parameters are merged using `array_merge()`, so existing parameters can be overridden
- Page ranges can be configured independently for mobile and desktop
