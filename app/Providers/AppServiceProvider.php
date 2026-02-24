<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Providers;

use App\Contracts\FileIntegrity\FileIntegrityServiceInterface;
use App\Contracts\LegalPage\LegalPageServiceInterface;
use App\Contracts\Logging\LogServiceInterface;
use App\Contracts\Mail\MailServiceInterface;
use App\Contracts\Plugin\PluginPermissionServiceInterface;
use App\Contracts\Plugin\SignatureVerifierInterface;
use App\Contracts\Theme\ThemePermissionServiceInterface;
use App\Contracts\TwoFa\TwoFaPasskeyServiceInterface;
use App\Models\SecuritySetting;
use App\Services\FileIntegrityService;
use App\Services\LegalPageService;
use App\Services\LogService;
use App\Services\MailService;
use App\Services\Plugin\CoreSignatureVerifier;
use App\Services\Plugin\PluginPermissionService;
use App\Services\RouteSlugRegistry;
use App\Services\Theme\ThemePermissionService;
use App\Services\TwoFa\TwoFaPasskeyService;
use App\Traits\CustomFilesLoaderTrait;
use App\Traits\PluginLoaderTrait;
use App\Traits\ThemeLoaderTrait;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    use CustomFilesLoaderTrait;
    use PluginLoaderTrait;
    use ThemeLoaderTrait;

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // プラグイン権限サービスをシングルトンとして登録
        $this->app->singleton(PluginPermissionService::class, function ($app) {
            return new PluginPermissionService();
        });

        // ファイル整合性サービスをバインド
        $this->app->bind(FileIntegrityServiceInterface::class, FileIntegrityService::class);

        // メール送信サービスをバインド
        $this->app->bind(MailServiceInterface::class, MailService::class);

        // ログ出力サービスをバインド
        $this->app->bind(LogServiceInterface::class, LogService::class);

        // 署名検証サービスをバインド（DixlaseDevKit プラグインが上書き可能）
        $this->app->bind(SignatureVerifierInterface::class, CoreSignatureVerifier::class);

        // ルートスラッグレジストリをシングルトンとして登録
        $this->app->singleton(RouteSlugRegistry::class);

        // CSP Nonce Generatorをシングルトンとして登録（リクエストごとに同じnonce値を使用）
        $this->app->singleton(\App\Services\Csp\CspNonceGenerator::class);

        // 法務ページレジストリサービスをシングルトンとして登録
        $this->app->singleton(LegalPageService::class);

        // Contract インターフェース → 具象クラスのバインド
        $this->app->bind(LegalPageServiceInterface::class, LegalPageService::class);
        $this->app->bind(TwoFaPasskeyServiceInterface::class, TwoFaPasskeyService::class);
        $this->app->bind(PluginPermissionServiceInterface::class, PluginPermissionService::class);
        $this->app->bind(ThemePermissionServiceInterface::class, ThemePermissionService::class);

        // Laragear WebAuthnのWebAuthnCredentialモデルをカスタムモデルにバインド
        $this->app->bind(
            \Laragear\WebAuthn\Models\WebAuthnCredential::class,
            \App\Models\WebAuthnCredential::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        // .envファイルが存在しない場合はスキップ
        if (! file_exists(base_path('.env'))) {
            return;
        }

        // ViteのCSP nonce機能を無効化（カスタム実装を使用）
        // これによりViteは独自のnonceを生成しなくなる
        config(['vite.csp_nonce' => false]);

        // Livewireのnavigate機能を無効化（data-navigate-once属性を削除）
        // これにより初回ページロードでLivewireコンポーネントが正常に初期化される
        config(['livewire.navigate' => false]);

        // カスタムBladeディレクティブ: @livewireScriptsWithoutNavigate
        // @livewireScriptsの出力からdata-navigate-once属性を削除
        \Blade::directive('livewireScriptsWithoutNavigate', function () {
            return "<?php echo view('components.livewire-scripts-without-navigate')->render(); ?>";
        });

        // インストール済みかどうかをチェック（config経由で取得することでキャッシュに対応）
        // env()は本番環境でキャッシュされると更新されないため、config()を使用
        $isInstalled = config('app.installed', false) ?: env('INSTALLED', false);
        if (! $isInstalled) {
            return;
        }

        try {
            // セキュリティ設定でSSLを矯正しているかどうかを判定
            // security_settingsテーブルのforce_sslの値を取得
            // テーブルが存在しているか確認

            if (Schema::hasTable('security_settings')) {
                $forceSsl = SecuritySetting::get('force_ssl', config('security.force_ssl'));
            } else {
                $forceSsl = config('security.force_ssl');
            }

            if ($forceSsl) {
                $this->app['request']->server->set('HTTPS', true);
                URL::forceRootUrl(Config::get('app.url')); // ルートURLを設定
                URL::forceScheme('https');
            }
        } catch (\Exception $e) {
            // データベース接続エラーなどの場合はログに記録してスキップ
            \Log::warning('AppServiceProvider boot error (likely during installation): '.$e->getMessage());

            return;
        }

        // 言語の設定
        // インストール中はセッション/クッキーの言語を優先
        $request = $this->app['request'];
        $language = null;

        // 有効なロケールのリスト
        $availableLocales = array_keys(config('language.languages', ['en' => 'English']));

        if ($request && $request->is('install*')) {
            // セッションの値（StartSessionより前でも取得できる場合がある）、なければクッキー
            $sessionLocale = session()->get('install_locale');
            $cookieLocale = $request->cookie('install_locale');

            // 有効なロケールのみを許可
            if ($sessionLocale && in_array($sessionLocale, $availableLocales)) {
                $language = $sessionLocale;
            } elseif ($cookieLocale && in_array($cookieLocale, $availableLocales)) {
                $language = $cookieLocale;
            }
        }

        // それでも未設定なら環境変数→DB→configの順で決定
        if (! $language) {
            // 環境変数を直接優先（.envの設定を最優先）
            $language = env('APP_LOCALE') ?? config('app.locale', 'en');

            // 有効なロケールかチェック
            if (! in_array($language, $availableLocales)) {
                $language = 'en'; // デフォルトにフォールバック
            }
        }
        app()->setLocale($language);

        // テーマの設定を読み込む
        $adminTheme = config('themes.admin_theme', 'admin'); // 管理画面テーマ
        $themeDirectory = config('themes.theme_directory', 'themes'); // テーマディレクトリ
        $activeThemeDirectory = config('themes.active_theme', 'default-theme'); // アクティブなテーマ
        $defaultTheme = config('themes.default_theme', 'default-theme'); // デフォルトテーマ

        // カスタムファイルのディレクトリを追加
        $customFilesDir = base_path(config('custom.custom_files_dir', 'custom'));

        // 管理画面のテンプレートの読み込みがviews_customのほうを優先されるように設定
        View::addNamespace('admin', [
            base_path("{$customFilesDir}/resources/views/{$adminTheme}"),
            resource_path("views/{$adminTheme}"),
        ]);

        // 共用コンポーネントの読み込みがviews_customのほうを優先されるように設定
        View::addNamespace('components', [
            base_path("{$customFilesDir}/resources/views/components"), // カスタムコンポーネントを優先
            resource_path('views/components'),       // デフォルトコンポーネント
        ]);

        // 共通レイアウトの名前空間
        View::addNamespace('layouts', [
            base_path("{$customFilesDir}/resources/views/layouts"), // カスタムレイアウトを優先
            resource_path('views/layouts'),       // デフォルトレイアウト
        ]);

        // 現在有効化されているテーマを取得
        $enabledThemeDirectory = $this->getEnabledThemeDirectory();

        // テーマファイルの読み込み、カスタムテーマの読み込みがcustom/resources/viewsのほうを優先されるように設定
        View::addNamespace('themes', [
            base_path("{$customFilesDir}/{$themeDirectory}/{$enabledThemeDirectory}/resources/views"),
            base_path("{$themeDirectory}/{$enabledThemeDirectory}/resources/views"),
            base_path("{$themeDirectory}/{$defaultTheme}/resources/views"),
        ]);

        // カスタムファイルのディレクトリを追加
        $customFilesPath = base_path(config('custom.custom_files_dir', 'custom'));
        $fileTypes = config('app.file_types');

        // プラグインロード後にカスタムファイルをロード
        $this->app->booted(function () use ($customFilesPath, $fileTypes) {
            // コマンドライン引数をチェックしてプラグイン管理コマンド実行中かを判定
            $isPluginManagementCommand = $this->isPluginManagementCommand();

            if (! $isPluginManagementCommand) {
                // プラグインのロード
                $this->loadEnabledPlugins();
            }
            // カスタムファイルのロード
            foreach ($fileTypes as $type => $typeConfig) {
                $this->loadCustomFilesForType($customFilesPath, $typeConfig);
            }
            // プラグインのナビゲーション設定を適用するため、設定の再配置を実行
            $this->reorderAllConfig();
        });
    }

    /**
     * プラグイン管理コマンドが実行中かをチェック
     */
    private function isPluginManagementCommand(): bool
    {
        // コマンドライン引数をチェック
        $argv = $_SERVER['argv'] ?? [];

        // プラグイン管理コマンドかをチェック
        if (count($argv) >= 2) {
            $pluginCommands = [
                'plugin:install',
                'plugin:uninstall',
                'plugin:enable',
                'plugin:disable',
            ];

            return in_array($argv[1], $pluginCommands);
        }

        return false;
    }

    /**
     * アンインストール対象のプラグイン名を取得
     */
    private function getUninstallingPluginName(): ?string
    {
        $argv = $_SERVER['argv'] ?? [];

        // artisan plugin:uninstall PluginName の形式
        if (count($argv) >= 3 && $argv[1] === 'plugin:uninstall') {
            return $argv[2];
        }

        return null;
    }
}
