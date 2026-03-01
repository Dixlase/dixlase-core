# Dixlase Translation API 仕様

## 概要

このドキュメントはDixlaseのTranslation APIを定義します。このAPIは、プラグインがコアシステムを変更せずに多言語機能を実装するための標準化された方法を提供します。

## バージョン

- **仕様バージョン**: 1.0
- **ステータス**: 安定版

## 1. アーキテクチャ

### 1.1 設計思想

- **コアはインターフェースを提供し、プラグインが実装を提供する**
- モデルが翻訳可能なフィールドを定義する
- 翻訳の保存と取得はプラグインに委譲される
- イベントによりプラグインが翻訳ライフサイクルにフックできる

### 1.2 コンポーネント構成

```
┌─────────────────────────────────────────────────────────────┐
│                      Your Model                              │
│  ┌─────────────────────────────────────────────────────┐    │
│  │  use TranslatableTrait;                              │    │
│  │  protected $translatable = ['title', 'body'];        │    │
│  └─────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                  TranslationManager                          │
│  - ロケール設定の管理                                         │
│  - 翻訳リゾルバーの登録                                       │
│  - ロケール変更イベントの発火                                   │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│              TranslationResolver (Interface)                 │
│  - resolve(): 翻訳値の取得                                    │
│  - store(): 翻訳の保存                                       │
│  - all(): 全翻訳の取得                                       │
│  - exists(): 翻訳の存在確認                                   │
│  - delete(): 翻訳の削除                                      │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│           プラグインの TranslationResolver 実装               │
│  （例: データベーステーブル、JSONファイル、外部API）              │
└─────────────────────────────────────────────────────────────┘
```

## 2. Translatable Trait

### 2.1 基本的な使い方

```php
use App\Traits\TranslatableTrait;

class Post extends Model
{
    use TranslatableTrait;

    /**
     * 翻訳可能なフィールド
     */
    protected array $translatable = [
        'title',
        'body',
        'slug',
        'meta_description',
    ];
}
```

### 2.2 利用可能なメソッド

| メソッド | 説明 |
|---------|------|
| `getTranslatableFields()` | 翻訳可能なフィールド名のリストを取得 |
| `isTranslatable($field)` | フィールドが翻訳可能かどうかを確認 |
| `getTranslation($field, $locale, $fallback)` | 翻訳値を取得 |
| `setTranslation($field, $value, $locale)` | 翻訳値を設定 |
| `getTranslations($field)` | フィールドの全翻訳を取得 |
| `hasTranslation($field, $locale)` | 翻訳が存在するかを確認 |
| `deleteTranslation($field, $locale)` | 翻訳を削除 |

### 2.3 自動翻訳

`TranslationResolver` が登録されている場合、`getAttribute()` メソッドが自動的に翻訳値を返します：

```php
$post = Post::find(1);

// ロケールが 'ja' でリゾルバーが登録されていれば、
// 自動的に日本語の翻訳を返す
echo $post->title;

// 特定のロケールを明示的に指定して取得
echo $post->getTranslation('title', 'en');
```

## 3. Translation Resolver

### 3.1 インターフェース

```php
interface TranslationResolver
{
    public function resolve(Model $model, string $field, string $locale): mixed;
    public function store(Model $model, string $field, mixed $value, string $locale): void;
    public function all(Model $model, string $field): array;
    public function exists(Model $model, string $field, string $locale): bool;
    public function delete(Model $model, string $field, ?string $locale = null): void;
    public function getAvailableLocales(Model $model): array;
    public function copy(Model $source, Model $target, ?array $fields = null, ?array $locales = null): void;
}
```

### 3.2 実装例

```php
namespace MyPlugin\Translation;

use App\Contracts\TranslationResolver;
use Illuminate\Database\Eloquent\Model;

class DatabaseTranslationResolver implements TranslationResolver
{
    public function resolve(Model $model, string $field, string $locale): mixed
    {
        return Translation::where([
            'translatable_type' => get_class($model),
            'translatable_id' => $model->getKey(),
            'field' => $field,
            'locale' => $locale,
        ])->value('value');
    }

    public function store(Model $model, string $field, mixed $value, string $locale): void
    {
        Translation::updateOrCreate(
            [
                'translatable_type' => get_class($model),
                'translatable_id' => $model->getKey(),
                'field' => $field,
                'locale' => $locale,
            ],
            ['value' => $value]
        );
    }

    // ... その他のメソッド
}
```

### 3.3 リゾルバーの登録

プラグインのServiceProviderで登録します：

```php
use App\Support\TranslationManager;

public function register()
{
    TranslationManager::resolveUsing(DatabaseTranslationResolver::class);
}
```

シンプルなケースではクロージャも使用できます：

```php
TranslationManager::resolveUsing(function ($model, $field, $locale) {
    return Cache::get("translation.{$model->id}.{$field}.{$locale}");
});
```

## 4. Translation Manager

### 4.1 ロケール管理

```php
use App\Support\TranslationManager;

// ロケールを設定（LOCALE_CHANGED イベントが発火される）
TranslationManager::setLocale('ja');

// 現在のロケールを取得
$locale = TranslationManager::getLocale();

// フォールバックロケールを取得
$fallback = TranslationManager::getFallbackLocale();
```

### 4.2 利用可能なロケール

```php
// 利用可能なロケールを登録（通常はプラグインのbootで実行）
TranslationManager::registerLocales([
    'en' => 'English',
    'ja' => '日本語',
    'zh' => '中文',
]);

// 利用可能なロケールを取得
$locales = TranslationManager::getAvailableLocales();

// ロケールの表示名を取得
$name = TranslationManager::getLocaleName('ja'); // '日本語'

// ロケールが利用可能かを確認
if (TranslationManager::isLocaleAvailable('fr')) {
    // ...
}
```

## 5. イベント

### 5.1 翻訳イベント

| イベント | ペイロード | 説明 |
|---------|-----------|------|
| `translation.model.retrieved` | `[Model]` | TranslatableTraitを持つモデルが取得された |
| `translation.model.saving` | `[Model]` | モデルが保存中 |
| `translation.model.saved` | `[Model]` | モデルが保存された |
| `translation.model.deleted` | `[Model]` | モデルが削除された |
| `translation.field.updated` | `[Model, field, value, locale]` | 翻訳が更新された |
| `translation.field.deleted` | `[Model, field, locale]` | 翻訳が削除された |

### 5.2 ロケールイベント

| イベント | ペイロード | 説明 |
|---------|-----------|------|
| `dixlase.locale.changed` | `['locale' => string, 'previous' => string]` | ロケールが変更された |

### 5.3 イベントのリッスン

```php
use App\Events\DixlaseEvents;
use Illuminate\Support\Facades\Event;

// プラグインのServiceProviderで
Event::listen(DixlaseEvents::LOCALE_CHANGED, function ($data) {
    // 新しいロケールの翻訳キャッシュをクリア
    Cache::tags(['translations', $data['locale']])->flush();
});

Event::listen('translation.model.deleted', function ($model) {
    // モデル削除時に翻訳をクリーンアップ
    Translation::where([
        'translatable_type' => get_class($model),
        'translatable_id' => $model->getKey(),
    ])->delete();
});
```

## 6. ベストプラクティス

### 6.1 プラグイン開発者向け

1. 翻訳操作を実行する前に、**リゾルバーが存在するか常に確認**してください
2. 翻訳をストレージと同期するために**イベントを使用**してください
3. パフォーマンスのためにリゾルバーに**キャッシュを実装**してください
4. 翻訳が存在しない場合の**フォールバックを適切にハンドリング**してください

### 6.2 テーマ開発者向け

1. 明示的なロケール制御には **`getTranslation()` を使用**してください
2. ロケール切替UIを表示する前に **`hasTranslation()` で確認**してください
3. 翻訳フォームの構築には **`getTranslatableFields()` を使用**してください

### 6.3 推奨データベーススキーマ

データベースベースの翻訳を実装する場合：

```sql
CREATE TABLE translations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    translatable_type VARCHAR(255) NOT NULL,
    translatable_id BIGINT UNSIGNED NOT NULL,
    field VARCHAR(255) NOT NULL,
    locale VARCHAR(10) NOT NULL,
    value TEXT,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    UNIQUE KEY unique_translation (translatable_type, translatable_id, field, locale),
    INDEX idx_translatable (translatable_type, translatable_id),
    INDEX idx_locale (locale)
);
```

## 7. 移行ガイド

### 7.1 既存モデルへの翻訳サポートの追加

1. `TranslatableTrait` トレイトを追加する
2. `$translatable` 配列を定義する
3. 翻訳プラグインをインストールする
4. 既存コンテンツを翻訳テーブルに移行する

### 7.2 移行スクリプトの例

```php
// 既存コンテンツを翻訳に移行
$posts = Post::all();
$resolver = TranslationManager::getResolver();

foreach ($posts as $post) {
    foreach ($post->getTranslatableFields() as $field) {
        $resolver->store($post, $field, $post->$field, 'en');
    }
}
```

## 付録A: 完全な実装例

### プラグイン: Simple Translation

```php
// app/Providers/SimpleTranslationServiceProvider.php
namespace MyPlugin\Providers;

use App\Contracts\TranslationResolver;
use App\Support\TranslationManager;
use Illuminate\Support\ServiceProvider;

class SimpleTranslationServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(TranslationResolver::class, SimpleTranslationResolver::class);
    }

    public function boot()
    {
        TranslationManager::registerLocales([
            'en' => 'English',
            'ja' => '日本語',
        ]);
    }
}
```

---

**ドキュメントバージョン**: 1.0.0
**最終更新日**: 2025-01-01
**著者**: Dixlase Development Team
