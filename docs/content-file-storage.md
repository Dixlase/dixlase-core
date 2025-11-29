# コンテンツファイルストレージ

プラグインやテーマでファイルベースのコンテンツ保存を行う際のガイドです。

## ディレクトリ構造

コンテンツファイルは `storage/app/private/` 配下に保存されます：

```
storage/app/private/
├── plugins/
│   ├── dixlase-pages/           # ページプラグイン
│   │   ├── about/               # ページスラッグ
│   │   │   ├── content.html     # 英語（デフォルト言語）
│   │   │   └── content.ja.html  # 日本語
│   │   └── contact/
│   │       ├── content.md       # Markdown（英語）
│   │       └── content.ja.md    # Markdown（日本語）
│   ├── dixlase-blog/            # ブログプラグイン
│   │   └── {post-slug}/
│   └── dixlase-docs/            # ドキュメントプラグイン
│       └── {doc-slug}/
├── themes/
│   └── {theme-slug}/            # テーマ固有のコンテンツ
└── front/                       # フロントページデータ
```

## ファイル命名規則

| エディタータイプ | デフォルト言語 | 他言語 |
|-----------------|---------------|--------|
| HTML | `content.html` | `content.{locale}.html` |
| Markdown | `content.md` | `content.{locale}.md` |
| Blade | `content.blade.php` | `content.{locale}.blade.php` |

## ContentFileService の使用方法

### 基本的な使用

```php
use App\Services\ContentFileService;

// インスタンス作成（ベースパス、ディスク、デフォルト言語）
$service = new ContentFileService('plugins/my-plugin', 'local', 'en');

// ファイルに保存
$service->saveToFile('page-slug', 'ja', 'html', '<p>コンテンツ</p>');

// ファイルから読み込み
$content = $service->loadFromFile('page-slug', 'ja', 'html');

// ファイルの存在確認
$exists = $service->fileExists('page-slug', 'ja', 'html');

// ディレクトリごと削除
$service->deleteDirectory('page-slug');

// スラッグ変更時のディレクトリリネーム
$service->renameFiles('old-slug', 'new-slug', 'html', ['en', 'ja']);
```

### プラグインでの継承

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

## Vite での自動リロード設定

開発時にコンテンツファイルの変更を検知して自動リロードするには、`vite.config.js` の `laravel-vite-plugin` の `refresh` オプションにパスを追加します：

```javascript
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                // 入力ファイル...
            ],
            refresh: [
                // デフォルトのBladeテンプレート
                'resources/views/**',
                // ストレージ内のコンテンツファイル
                'storage/app/private/plugins/**',
                'storage/app/private/themes/**',
                'storage/app/private/front/**',
            ],
        }),
        // ...
    ],
});
```

### 監視対象ディレクトリ

| パス | 説明 |
|-----|------|
| `storage/app/private/plugins/**` | プラグインのコンテンツファイル |
| `storage/app/private/themes/**` | テーマのコンテンツファイル |
| `storage/app/private/front/**` | フロントページのコンテンツファイル |

> **Note**: `laravel-vite-plugin` の `refresh` オプションは、指定したパスのファイル変更時にフルページリロードをトリガーします。`vite-plugin-live-reload` よりも確実に動作します。

## 注意事項

1. **セキュリティ**: `storage/app/private/` は公開ディレクトリではないため、直接URLアクセスはできません
2. **バックアップ**: コンテンツファイルはデータベースとは別にバックアップが必要です
3. **デプロイ**: 本番環境へのデプロイ時はストレージディレクトリも含めてください
4. **権限**: Webサーバーがディレクトリに書き込み権限を持っていることを確認してください

## 関連ドキュメント

- [プラグイン開発ガイド](./plugin-integration-contracts.md)
- [テーマ開発ガイド](./theme-development.md)
