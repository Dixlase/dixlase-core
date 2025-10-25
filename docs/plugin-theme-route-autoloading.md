# プラグイン・テーマルート自動読み込みシステム

## 概要

Dixlaseでは、プラグインとテーマのルートファイルを自動的に読み込む仕組みを実装しています。
有効化されたプラグインやテーマは、それぞれのルートファイル（`routes/web.php`、`routes/admin.php`）を持つことができ、これらは自動的にシステムに統合されます。

## アーキテクチャ

### 1. PluginServiceProvider（プラグイン用）

**場所**: `app/Providers/PluginServiceProvider.php`

**機能**:
- データベースから有効化されたプラグイン（`status = 1`）を取得
- 各プラグインの`routes/web.php`と`routes/admin.php`を自動読み込み
- 適切なミドルウェアを自動適用

**読み込まれるルート**:
- `plugins/{PluginDirectory}/routes/web.php` - フロントエンド用ルート
- `plugins/{PluginDirectory}/routes/admin.php` - 管理画面用ルート

**自動適用されるミドルウェア（admin.php）**:
- `admin.ip` - IPアドレスフィルタリング
- `auth:member` - 管理メンバー認証
- `verified` - メール認証済みチェック
- `log.admin.activity` - 管理画面操作ログ

**ルートプレフィックス**:
- Web: なし
- Admin: `/admin`（動的に取得）

**ルート名プレフィックス**:
- Web: なし
- Admin: `admin.`

### 2. ThemeServiceProvider（テーマ用）

**場所**: `app/Providers/ThemeServiceProvider.php`

**機能**:
- データベースから有効化されたテーマ（`theme_settings.active_theme_id`）を取得
- アクティブテーマの`routes/web.php`と`routes/admin.php`を自動読み込み
- 適切なミドルウェアを自動適用

**読み込まれるルート**:
- `themes/{ThemeDirectory}/routes/web.php` - フロントエンド用ルート
- `themes/{ThemeDirectory}/routes/admin.php` - 管理画面用ルート

**自動適用されるミドルウェア（admin.php）**:
- `admin.ip` - IPアドレスフィルタリング
- `auth:member` - 管理メンバー認証
- `verified` - メール認証済みチェック
- `log.admin.activity` - 管理画面操作ログ

**ルートプレフィックス**:
- Web: なし
- Admin: `/admin`（動的に取得）

**ルート名プレフィックス**:
- Web: なし
- Admin: `admin.`

## プラグイン開発者向けガイド

### ルートファイルの作成

#### 1. 管理画面ルート（routes/admin.php）

```php
<?php

use Illuminate\Support\Facades\Route;
use Plugins\YourPlugin\App\Http\Controllers\Admin\YourController;

/*
|--------------------------------------------------------------------------
| プラグイン管理画面ルート（自動読み込み）
|--------------------------------------------------------------------------
|
| このファイルはプラグインが有効化されている場合、PluginServiceProviderによって
| 自動的に読み込まれます。以下のミドルウェアが自動適用されます：
|
| - admin.ip: IPアドレスフィルタリング
| - auth:member: 管理メンバー認証
| - verified: メール認証済みチェック
| - log.admin.activity: 管理画面操作ログ
|
| ルートプレフィックス: /admin（動的に取得）
| ルート名プレフィックス: admin.
|
*/

// 例: /admin/your-plugin にアクセス
Route::prefix('your-plugin')
    ->name('your-plugin::admin.')
    ->group(function () {
        Route::get('/', [YourController::class, 'index'])->name('index');
        Route::get('/settings', [YourController::class, 'settings'])->name('settings');
    });
```

**重要な注意点**:
- ミドルウェア（`admin.ip`、`auth:member`など）は**自動適用されるため記述不要**
- ルートプレフィックス`/admin`は**自動付与されるため記述不要**
- ルート名プレフィックス`admin.`は**自動付与されるため記述不要**
- プラグイン固有のプレフィックスとルート名は自分で定義する

#### 2. フロントエンドルート（routes/web.php）

```php
<?php

use Illuminate\Support\Facades\Route;
use Plugins\YourPlugin\App\Http\Controllers\Front\YourFrontController;

// 例: /your-page にアクセス
Route::get('/your-page', [YourFrontController::class, 'index'])->name('your-plugin.page');
```

### ServiceProviderでの設定

プラグインのServiceProviderでは、ルート読み込みを**記述しない**でください：

```php
<?php

namespace Plugins\YourPlugin\App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Traits\PluginLoaderTrait;

class YourPluginServiceProvider extends ServiceProvider
{
    use PluginLoaderTrait;
    
    public function register(): void
    {
        // ナビゲーションマージ
        $this->mergeAdminNavigation('YourPlugin', __DIR__ . '/../../config/admin.php');
    }

    public function boot(): void
    {
        // ビュー、翻訳、マイグレーションなどの登録
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'your-plugin');
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'your-plugin');
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        
        // 注: ルート（routes/web.php, routes/admin.php）はPluginServiceProviderが自動読み込み
        // 動的ルートが必要な場合のみ、ここで登録
    }
}
```

## テーマ開発者向けガイド

### ルートファイルの作成

テーマのルートファイルは、プラグインと同様の構造で作成します：

#### 管理画面ルート（routes/admin.php）

```php
<?php

use Illuminate\Support\Facades\Route;
use Themes\YourTheme\App\Http\Controllers\Admin\Settings\AdminThemeSettingsController;

/*
|--------------------------------------------------------------------------
| テーマ管理画面ルート（自動読み込み）
|--------------------------------------------------------------------------
|
| このファイルはテーマが有効化されている場合、ThemeServiceProviderによって
| 自動的に読み込まれます。以下のミドルウェアが自動適用されます：
|
| - admin.ip: IPアドレスフィルタリング
| - auth:member: 管理メンバー認証
| - verified: メール認証済みチェック
| - log.admin.activity: 管理画面操作ログ
|
| ルートプレフィックス: /admin（動的に取得）
| ルート名プレフィックス: admin.
|
*/

// テーマ設定
Route::prefix('settings/themes')
    ->name('settings.themes.')
    ->group(function () {
        Route::get('/settings', [AdminThemeSettingsController::class, 'settings'])
            ->middleware('check.menu.access:settings.themes.settings')
            ->name('settings');
        
        Route::put('/settings', [AdminThemeSettingsController::class, 'update'])
            ->middleware(['check.menu.access:settings.themes.settings', 'check.menu.edit:settings.themes.settings'])
            ->name('settings.update');
    });
```

## 動的ルートの登録

設定に応じて動的にルートを登録する必要がある場合は、ServiceProviderの`boot()`メソッドで登録します：

```php
public function boot(): void
{
    // 静的ルートは自動読み込み
    
    // 動的ルート登録
    $this->registerDynamicRoutes();
}

protected function registerDynamicRoutes(): void
{
    try {
        $settings = YourSetting::getSettings();
        
        if ($settings->enable_custom_page) {
            $slug = $settings->custom_page_slug ?? 'custom';
            
            Route::middleware(['web'])
                ->group(function () use ($slug) {
                    Route::get($slug, [YourController::class, 'show'])
                        ->name('your-plugin.custom');
                });
        }
    } catch (\Exception $e) {
        \Log::debug('Failed to register dynamic routes: ' . $e->getMessage());
    }
}
```

## トラブルシューティング

### ルートが読み込まれない

1. **プラグイン/テーマが有効化されているか確認**
   - データベースの`plugins`テーブルで`status = 1`
   - データベースの`theme_settings`テーブルで`active_theme_id`が設定されている

2. **ルートファイルのパスを確認**
   - `plugins/{PluginDirectory}/routes/admin.php`
   - `themes/{ThemeDirectory}/routes/admin.php`

3. **ログを確認**
   - `storage/logs/dixlase.log`に「Plugin/Theme routes loaded」のログが出力されているか

### 無限ループが発生する

- ServiceProviderで手動ルート読み込みを**削除**してください
- `$this->loadRoutesFrom()`や`Route::middleware()->group()`は使用しない

### ミドルウェアが適用されない

- プラグイン/テーマのルートファイル内でミドルウェアを**重複して記述しない**
- 自動適用されるミドルウェアは、PluginServiceProvider/ThemeServiceProviderで管理されています

## まとめ

- ✅ プラグイン・テーマのルートは**自動読み込み**
- ✅ ミドルウェアは**自動適用**
- ✅ ServiceProviderでルート読み込みを**記述不要**
- ✅ 動的ルートのみServiceProviderで登録
- ✅ 有効化されたプラグイン・テーマのみ読み込まれる
