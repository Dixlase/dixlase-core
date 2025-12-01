# 多言語プラグイン開発ガイド

Dixlaseの多言語システムは、WordPressのPolylang/WPMLと同様の「Contract + プラグイン実装」パターンを採用しています。これにより、多言語プラグインの有無に関わらず、テーマやプラグインは同じコードで動作します。

## アーキテクチャ概要

```
コア側（app/）
├── Contracts/
│   ├── Multilingual.php          # 多言語サービスインターフェイス
│   └── TranslatableModel.php     # 翻訳対象モデルインターフェイス
├── Services/
│   └── DummyMultilingualService.php  # 単一言語用ダミー実装
├── Traits/
│   └── HasTranslations.php       # 翻訳リレーション用トレイト
└── helpers.php
    └── multilingual()            # Contract経由でサービス取得

多言語プラグイン側（plugins/DixlaseMultilingual/）
├── Services/
│   └── MultilingualService.php   # Contract実装（本物）
└── Providers/
    └── ServiceProvider.php       # Contractをバインド
```

## 動作の仕組み

### 多言語プラグインが無効な場合
- コアの`DummyMultilingualService`が使用される
- `multilingual()->isEnabled()` は常に `false` を返す
- `multilingual()->getTranslated($model, 'title')` はモデルの元の属性値を返す
- 単一言語モードとして動作

### 多言語プラグインが有効な場合
- プラグインの`MultilingualService`が`DummyMultilingualService`を差し替える
- `multilingual()->isEnabled()` は設定に応じて `true` を返す
- `multilingual()->getTranslated($model, 'title')` は現在の言語の翻訳を返す
- 完全な多言語モードとして動作

## プラグイン開発者向けガイド

### 1. 翻訳対象フィールドの宣言

モデルに`TranslatableModel`インターフェイスを実装し、翻訳対象のフィールドを宣言します。

```php
<?php

namespace Plugins\YourPlugin\App\Models;

use App\Contracts\TranslatableModel;
use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model implements TranslatableModel
{
    use HasTranslations;

    /**
     * 翻訳対象の属性名一覧を取得
     */
    public function getTranslatableAttributes(): array
    {
        return ['title', 'content', 'meta_description'];
    }

    /**
     * 翻訳管理UIで表示するラベル用の属性名を取得
     */
    public function getTranslationLabelAttribute(): string
    {
        return 'title';
    }

    /**
     * 翻訳リレーション
     */
    public function translations(): HasMany
    {
        return $this->hasMany(PageTranslation::class, 'page_id');
    }
}
```

### 2. 翻訳テーブルの作成

翻訳データを保存するテーブルを作成します。

```php
// マイグレーション例
Schema::create('plg_your_plugin_page_translations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('page_id')->constrained('plg_your_plugin_pages')->onDelete('cascade');
    $table->string('locale', 10);
    $table->string('title');
    $table->text('content')->nullable();
    $table->string('meta_description')->nullable();
    $table->timestamps();

    $table->unique(['page_id', 'locale']);
});
```

### 3. 翻訳値の取得

テーマやコントローラーで翻訳値を取得する際は、常に`multilingual()`ヘルパーを使用します。

```php
// コントローラー
public function show(Page $page)
{
    $title = multilingual()->getTranslated($page, 'title');
    $content = multilingual()->getTranslated($page, 'content');

    return view('pages.show', compact('page', 'title', 'content'));
}
```

```blade
{{-- Bladeテンプレート --}}
<h1>{{ multilingual()->getTranslated($page, 'title') }}</h1>
<div class="content">
    {!! multilingual()->getTranslated($page, 'content') !!}
</div>
```

### 4. 多言語が有効かどうかのチェック

```php
// PHP
if (multilingual()->isEnabled()) {
    // 多言語モード
    $locales = multilingual()->getLocales();
} else {
    // 単一言語モード
}
```

```blade
{{-- Blade --}}
@if(multilingual()->isEnabled())
    {{-- 言語切替ボタンを表示 --}}
    @foreach(multilingual()->getLocales() as $locale)
        <a href="{{ multilingual()->switchUrl($locale) }}"
           class="{{ multilingual()->isCurrentLocale($locale) ? 'active' : '' }}">
            {{ multilingual()->getLocaleName($locale) }}
        </a>
    @endforeach
@endif
```

### 5. 多言語プラグインへの翻訳対象登録

多言語プラグインが有効な場合、翻訳管理UIで一元管理できるよう登録します。

```php
// プラグインのServiceProviderで登録
public function boot()
{
    // 多言語プラグインが有効な場合のみ登録
    if (class_exists(\Plugins\DixlaseMultilingual\App\Services\TranslationRegistry::class)) {
        $registry = app(\Plugins\DixlaseMultilingual\App\Services\TranslationRegistry::class);
        
        $registry->register('pages', [
            'model' => \Plugins\YourPlugin\App\Models\Page::class,
            'translation_model' => \Plugins\YourPlugin\App\Models\PageTranslation::class,
            'fields' => ['title', 'content', 'meta_description'],
            'label_field' => 'title',
            'plugin' => 'YourPlugin',
            'icon' => 'fas fa-file-alt',
        ]);
    }
}
```

## テーマ開発者向けガイド

### 言語切替ボタンの設置

```blade
{{-- ヘッダーに言語切替を配置 --}}
<header>
    <nav>
        {{-- ナビゲーション --}}
    </nav>
    
    @if(multilingual()->isEnabled())
        <div class="language-switcher">
            @foreach(multilingual()->getLocales() as $locale)
                <a href="{{ multilingual()->switchUrl($locale) }}"
                   class="{{ multilingual()->isCurrentLocale($locale) ? 'active' : '' }}">
                    {{ multilingual()->getLocaleFlag($locale) }}
                    {{ multilingual()->getLocaleName($locale) }}
                </a>
            @endforeach
        </div>
    @endif
</header>
```

### 多言語プラグインのBladeディレクティブ（プラグイン有効時のみ）

```blade
{{-- 多言語プラグインが提供するディレクティブ --}}
@if_multilingual
    <div class="language-switcher">
        @multilingual_switcher('dropdown')
    </div>
@endif_multilingual
```

## Multilingual Contract API

### メソッド一覧

| メソッド | 説明 | 戻り値 |
|---------|------|--------|
| `isEnabled()` | 多言語機能が有効か | `bool` |
| `getLocales()` | 利用可能な言語一覧 | `array` |
| `getDefaultLocale()` | デフォルト言語 | `string` |
| `getCurrentLocale()` | 現在の言語 | `string` |
| `setCurrentLocale($locale)` | 現在の言語を設定 | `void` |
| `isSupported($locale)` | 言語がサポートされているか | `bool` |
| `isCurrentLocale($locale)` | 現在の言語か | `bool` |
| `getTranslated($model, $field, $locale)` | 翻訳値を取得 | `mixed` |
| `getTranslatedFields($model, $fields, $locale)` | 複数の翻訳値を取得 | `array` |
| `switchUrl($locale, $path)` | 言語切替URL生成 | `string` |
| `getLocaleName($locale, $native)` | 言語名を取得 | `string` |
| `getLocaleFlag($locale)` | 言語フラグ絵文字 | `string` |
| `saveTranslations($model, $translations)` | 翻訳データを保存 | `void` |
| `clearCache()` | キャッシュをクリア | `void` |

## ベストプラクティス

### 1. 常に`multilingual()`ヘルパーを使用する

```php
// ✅ 良い例
$title = multilingual()->getTranslated($page, 'title');

// ❌ 悪い例（多言語プラグインに直接依存）
$title = app(MultilingualService::class)->getTranslated($page, 'title');
```

### 2. 多言語プラグインの有無を前提としない

```php
// ✅ 良い例（プラグインの有無に関わらず動作）
$title = multilingual()->getTranslated($page, 'title');
// 多言語OFF → 元のtitleを返す
// 多言語ON → 現在の言語のtitleを返す

// ❌ 悪い例（多言語プラグインがないとエラー）
if (class_exists(MultilingualService::class)) {
    $title = app(MultilingualService::class)->getTranslated($page, 'title');
} else {
    $title = $page->title;
}
```

### 3. 翻訳対象フィールドは最小限に

```php
// ✅ 良い例（必要なフィールドのみ）
public function getTranslatableAttributes(): array
{
    return ['title', 'content', 'meta_description'];
}

// ❌ 悪い例（不要なフィールドも含む）
public function getTranslatableAttributes(): array
{
    return ['title', 'content', 'meta_description', 'slug', 'created_at', 'updated_at'];
}
```

## 関連ファイル

- `app/Contracts/Multilingual.php` - 多言語サービスインターフェイス
- `app/Contracts/TranslatableModel.php` - 翻訳対象モデルインターフェイス
- `app/Services/DummyMultilingualService.php` - 単一言語用ダミー実装
- `app/Traits/HasTranslations.php` - 翻訳リレーション用トレイト
- `app/Helpers/GlobalHelper.php` - `multilingual()`ヘルパー関数
- `plugins/DixlaseMultilingual/` - 多言語プラグイン本体
