<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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

use App\Contracts\Backup\BackupServiceInterface;
use App\Contracts\Backup\RestoreServiceInterface;
use App\Contracts\Encryption\FileEncryptionServiceInterface;
use App\Contracts\FileIntegrity\FileIntegrityServiceInterface;
use App\Contracts\LegalPage\LegalPageServiceInterface;
use App\Contracts\Logging\LogServiceInterface;
use App\Contracts\Mail\MailServiceInterface;
use App\Contracts\Plugin\PluginPermissionServiceInterface;
use App\Contracts\Plugin\SignatureVerifierInterface;
use App\Contracts\Site\SiteContextInterface;
use App\Contracts\Theme\ThemePermissionServiceInterface;
use App\Contracts\TwoFa\TwoFaPasskeyServiceInterface;
use App\Contracts\Verification\FileVerificationServiceInterface;
use App\Services\Backup\CoreBackupService;
use App\Services\Backup\CoreRestoreService;
use App\Services\Encryption\CoreFileEncryptionService;
use App\Services\FileIntegrityService;
use App\Services\LegalPageService;
use App\Services\LogService;
use App\Services\MailService;
use App\Services\Plugin\CoreSignatureVerifier;
use App\Services\Plugin\PluginPermissionService;
use App\Services\RouteSlugRegistry;
use App\Services\Site\SettingDefinitionRegistry;
use App\Services\Site\SiteContext;
use App\Services\Theme\ThemePermissionService;
use App\Services\TwoFa\TwoFaPasskeyService;
use App\Services\Verification\CoreFileVerificationService;
use App\Settings\ApiSettingDefinitions;
use App\Settings\CoreSettingDefinitions;
use App\Settings\SecuritySettingDefinitions;
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

        // パターン検出レジストリ（デフォルトパターンを全登録した状態で配布）
        $this->app->singleton(\App\Services\Plugin\Scanning\PatternRegistry::class, function () {
            return \App\Services\Plugin\Scanning\PatternRegistry::createDefault();
        });

        // バックアップサービスをバインド（バックアッププラグインが上書き可能）
        $this->app->bind(BackupServiceInterface::class, CoreBackupService::class);

        // 復元サービスをバインド（バックアッププラグインが上書き可能）
        $this->app->bind(RestoreServiceInterface::class, CoreRestoreService::class);

        // ファイル暗号化サービスをバインド
        $this->app->bind(FileEncryptionServiceInterface::class, CoreFileEncryptionService::class);

        // ファイル整合性サービスをバインド
        $this->app->bind(FileIntegrityServiceInterface::class, FileIntegrityService::class);

        // ファイルハッシュ検証サービスをバインド
        $this->app->bind(FileVerificationServiceInterface::class, CoreFileVerificationService::class);

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

        // システム警告バナーレジストリをシングルトンとして登録
        $this->app->singleton(\App\Services\SystemWarningService::class);

        // Contract インターフェース → 具象クラスのバインド
        $this->app->bind(LegalPageServiceInterface::class, LegalPageService::class);
        $this->app->bind(TwoFaPasskeyServiceInterface::class, TwoFaPasskeyService::class);
        $this->app->bind(PluginPermissionServiceInterface::class, PluginPermissionService::class);
        $this->app->bind(ThemePermissionServiceInterface::class, ThemePermissionService::class);

        // Bind SiteContext as singleton so the resolved current site
        // persists across the request lifecycle.
        $this->app->singleton(SiteContextInterface::class, SiteContext::class);

        // SettingDefinitionRegistry holds the catalog of known setting keys
        // and their scopes. Bound as singleton so registrations from
        // service providers are visible across the whole request.
        $this->app->singleton(SettingDefinitionRegistry::class);

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
        // Register core setting definitions so SettingResolver can route
        // reads/writes correctly. Runs before the .env-presence check so
        // tests and CLI tooling have access to the registry without
        // requiring a real environment file.
        $registry = $this->app->make(SettingDefinitionRegistry::class);
        CoreSettingDefinitions::register($registry);
        SecuritySettingDefinitions::register($registry);
        ApiSettingDefinitions::register($registry);

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
            // force_ssl は Global scope。multisite consolidation 後は
            // global_settings に格納される。テーブル存在ガードを残し、
            // 未作成（インストール中など）は config フォールバック。
            if (Schema::hasTable('global_settings')) {
                $forceSsl = app(\App\Services\Site\SettingResolver::class)->get('force_ssl')
                    ?? config('security.force_ssl');
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

        // カスタムファイルのディレクトリ（base_path 相対のパス）
        $customFilesDir = config('custom.custom_files_dir', 'custom');

        // 存在するディレクトリのみを返すヘルパー（view:cache が存在しないディレクトリで失敗するのを防ぐ）
        $existingDirs = fn (array $paths) => array_values(array_filter($paths, 'is_dir'));

        // 管理画面のテンプレートの読み込みがviews_customのほうを優先されるように設定
        View::addNamespace('admin', $existingDirs([
            base_path("{$customFilesDir}/resources/views/{$adminTheme}"),
            resource_path("views/{$adminTheme}"),
        ]));

        // 共用コンポーネントの読み込みがviews_customのほうを優先されるように設定
        View::addNamespace('components', $existingDirs([
            base_path("{$customFilesDir}/resources/views/components"), // カスタムコンポーネントを優先
            resource_path('views/components'),       // デフォルトコンポーネント
        ]));

        // 共通レイアウトの名前空間
        View::addNamespace('layouts', $existingDirs([
            base_path("{$customFilesDir}/resources/views/layouts"), // カスタムレイアウトを優先
            resource_path('views/layouts'),       // デフォルトレイアウト
        ]));

        // 現在有効化されているテーマを取得
        $enabledThemeDirectory = $this->getEnabledThemeDirectory();

        // テーマファイルの読み込み、カスタムテーマの読み込みがcustom/resources/viewsのほうを優先されるように設定
        View::addNamespace('themes', $existingDirs([
            base_path("{$customFilesDir}/{$themeDirectory}/{$enabledThemeDirectory}/resources/views"),
            base_path("{$themeDirectory}/{$enabledThemeDirectory}/resources/views"),
            base_path("{$themeDirectory}/{$defaultTheme}/resources/views"),
        ]));

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
