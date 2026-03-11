# Content File Storage

A guide for file-based content storage in plugins and themes.

## Directory Structure

Content files are stored under `storage/app/private/`:

```
storage/app/private/
├── plugins/
│   ├── dixlase-pages/           # Pages plugin
│   │   ├── about/               # Page slug
│   │   │   ├── content.html     # English (default language)
│   │   │   └── content.ja.html  # Japanese
│   │   └── contact/
│   │       ├── content.md       # Markdown (English)
│   │       └── content.ja.md    # Markdown (Japanese)
│   ├── dixlase-blog/            # Blog plugin
│   │   └── {post-slug}/
│   └── dixlase-docs/            # Docs plugin
│       └── {doc-slug}/
├── themes/
│   └── {theme-slug}/            # Theme-specific content
└── front/                       # Front page data
```

## File Naming Convention

| Editor Type | Default Language | Other Languages |
|-------------|-----------------|-----------------|
| HTML | `content.html` | `content.{locale}.html` |
| Markdown | `content.md` | `content.{locale}.md` |
| Blade | `content.blade.php` | `content.{locale}.blade.php` |

## Using ContentFileService

### Basic Usage

```php
use App\Services\ContentFileService;

// Create an instance (base path, disk, default language)
$service = new ContentFileService('plugins/my-plugin', 'local', 'en');

// Save to file
$service->saveToFile('page-slug', 'ja', 'html', '<p>Content</p>');

// Load from file
$content = $service->loadFromFile('page-slug', 'ja', 'html');

// Check if file exists
$exists = $service->fileExists('page-slug', 'ja', 'html');

// Delete entire directory
$service->deleteDirectory('page-slug');

// Rename directory when slug changes
$service->renameFiles('old-slug', 'new-slug', 'html', ['en', 'ja']);
```

### Extending in Plugins

```php
namespace Plugins\MyPlugin\App\Services;

use App\Services\ContentFileService;

class MyContentService extends ContentFileService
{
    protected const PLUGIN_SLUG = 'my-plugin';

    public function __construct()
    {
        // storage/app/private/plugins/my-plugin/{slug}/
        parent::__construct('plugins/' . self::PLUGIN_SLUG, 'local', 'en');
    }
}
```

## Auto-Reload with Vite

To detect content file changes and trigger auto-reload during development, add paths to the `refresh` option of `laravel-vite-plugin` in `vite.config.js`:

```javascript
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                // Input files...
            ],
            refresh: [
                // Default Blade templates
                'resources/views/**',
                // Content files in storage
                'storage/app/private/plugins/**',
                'storage/app/private/themes/**',
                'storage/app/private/front/**',
            ],
        }),
        // ...
    ],
});
```

### Watched Directories

| Path | Description |
|------|-------------|
| `storage/app/private/plugins/**` | Plugin content files |
| `storage/app/private/themes/**` | Theme content files |
| `storage/app/private/front/**` | Front page content files |

> **Note**: The `refresh` option of `laravel-vite-plugin` triggers a full page reload when files at the specified paths change. This works more reliably than `vite-plugin-live-reload`.

## Notes

1. **Security**: `storage/app/private/` is not a public directory, so direct URL access is not possible
2. **Backup**: Content files need to be backed up separately from the database
3. **Deployment**: Include the storage directory when deploying to production
4. **Permissions**: Ensure the web server has write permissions to the directory

## Related Documentation

- [Plugin Development Guide](./plugin-integration-contracts.md)
- [Theme Development Guide](./theme-development.md)
