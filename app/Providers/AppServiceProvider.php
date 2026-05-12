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
use App\Contracts\Security\PolicyEvaluatorInterface;
use App\Contracts\Security\RiskEvaluatorInterface;
use App\Contracts\Security\SecretProviderInterface;
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
use App\Services\Security\EnvSecretProvider;
use App\Services\Security\LowRiskEvaluator;
use App\Services\Security\NullPolicyEvaluator;
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
use App\View\Composers\AdminFooterComposer;
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
        // Register plugin permission service as singleton
        $this->app->singleton(PluginPermissionService::class, function ($app) {
            return new PluginPermissionService();
        });

        // Pattern detection registry (distributed with all default patterns registered)
        $this->app->singleton(\App\Services\Plugin\Scanning\PatternRegistry::class, function () {
            return \App\Services\Plugin\Scanning\PatternRegistry::createDefault();
        });

        // Bind backup service (can be overridden by backup plugin)
        $this->app->bind(BackupServiceInterface::class, CoreBackupService::class);

        // Bind restore service (can be overridden by backup plugin)
        $this->app->bind(RestoreServiceInterface::class, CoreRestoreService::class);

        // Bind file encryption service
        $this->app->bind(FileEncryptionServiceInterface::class, CoreFileEncryptionService::class);

        // Bind file integrity service
        $this->app->bind(FileIntegrityServiceInterface::class, FileIntegrityService::class);

        // Bind file hash verification service
        $this->app->bind(FileVerificationServiceInterface::class, CoreFileVerificationService::class);

        // Bind mail sending service
        $this->app->bind(MailServiceInterface::class, MailService::class);

        // Bind log output service
        $this->app->bind(LogServiceInterface::class, LogService::class);

        // Bind signature verification service (can be overridden by DixlaseDevKit plugin)
        $this->app->bind(SignatureVerifierInterface::class, CoreSignatureVerifier::class);

        // Register route slug registry as singleton
        $this->app->singleton(RouteSlugRegistry::class);

        // Register CSP Nonce Generator as singleton (use same nonce value per request)
        $this->app->singleton(\App\Services\Csp\CspNonceGenerator::class);

        // Register legal page registry service as singleton
        $this->app->singleton(LegalPageService::class);

        // Register system warning banner registry as singleton
        $this->app->singleton(\App\Services\SystemWarningService::class);

        // Bind Contract interfaces to concrete classes
        $this->app->bind(LegalPageServiceInterface::class, LegalPageService::class);
        $this->app->bind(TwoFaPasskeyServiceInterface::class, TwoFaPasskeyService::class);
        $this->app->bind(PluginPermissionServiceInterface::class, PluginPermissionService::class);
        $this->app->bind(ThemePermissionServiceInterface::class, ThemePermissionService::class);

        // Bind SiteContext as singleton so the resolved current site
        // persists across the request lifecycle.
        $this->app->singleton(SiteContextInterface::class, SiteContext::class);

        // Reserved Zero Trust extension points (Phase 1 stubs). Plugins or
        // operator integrations rebind these to deliver actual managed-secret,
        // conditional-access, and ABAC behaviour. The default implementations
        // are all no-ops by design — they preserve current behaviour exactly.
        // See docs/development/extension-points.md.
        $this->app->bind(SecretProviderInterface::class, EnvSecretProvider::class);
        $this->app->bind(RiskEvaluatorInterface::class, LowRiskEvaluator::class);
        $this->app->bind(PolicyEvaluatorInterface::class, NullPolicyEvaluator::class);

        // Default I18n missing-translation policy: 302 redirect to the
        // site's primary locale. Any multilingual plugin (first-party,
        // third-party, or a custom in-house implementation), or another
        // extension such as a redirects plugin, can rebind this contract
        // to change the behaviour.
        $this->app->bind(
            \App\Contracts\I18n\MissingTranslationHandler::class,
            \App\Services\I18n\DefaultMissingTranslationHandler::class,
        );

        // SettingDefinitionRegistry holds the catalog of known setting keys
        // and their scopes. Bound as singleton so registrations from
        // service providers are visible across the whole request.
        $this->app->singleton(SettingDefinitionRegistry::class);

        // Bind Laragear WebAuthn's WebAuthnCredential model to custom model
        $this->app->bind(
            \Laragear\WebAuthn\Models\WebAuthnCredential::class,
            \App\Models\WebAuthnCredential::class
        );

        // Tag the core member privacy provider so the privacy aggregator
        // services (UserPrivacyExporter / UserPrivacyEraser) discover it
        // alongside any plugin-provided implementations.
        $this->app->tag(
            [\App\Services\Privacy\Providers\CoreMemberPrivacyProvider::class],
            \App\Services\Plugin\PluginServiceResolver::CAPABILITY_TAG,
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

        // Skip if .env file does not exist
        if (! file_exists(base_path('.env'))) {
            return;
        }

        // Demo mode: route every mailer through Laravel's log driver so
        // inquiry forms, password resets, and other notification flows
        // remain functional but never deliver real email to the outside
        // world. The admin's "mail server settings" page is also blocked
        // by DemoGuard, so this override cannot be undone from the UI.
        if (config('dixlase.demo_mode')) {
            config(['mail.default' => 'log']);
        }

        // Disable Vite's CSP nonce feature (use custom implementation)
        // This prevents Vite from generating its own nonce
        config(['vite.csp_nonce' => false]);

        // Disable Livewire's navigate feature (remove data-navigate-once attribute)
        // This ensures Livewire components initialize properly on initial page load
        config(['livewire.navigate' => false]);

        // Custom Blade directive: @livewireScriptsWithoutNavigate
        // Remove data-navigate-once attribute from @livewireScripts output
        \Blade::directive('livewireScriptsWithoutNavigate', function () {
            return "<?php echo view('components.livewire-scripts-without-navigate')->render(); ?>";
        });

        // Check if installed (retrieve via config to support caching)
        // Use config() because env() is not updated when cached in production
        $isInstalled = config('app.installed', false) ?: env('INSTALLED', false);
        if (! $isInstalled) {
            return;
        }

        try {
            // force_ssl is Global scope. After multisite consolidation
            // it will be stored in global_settings. Keep table existence guard,
            // fallback to config if not created (e.g., during installation)
            if (Schema::hasTable('global_settings')) {
                $forceSsl = app(\App\Services\Site\SettingResolver::class)->get('force_ssl')
                    ?? config('security.force_ssl');
            } else {
                $forceSsl = config('security.force_ssl');
            }

            if ($forceSsl) {
                $this->app['request']->server->set('HTTPS', true);
                URL::forceRootUrl(Config::get('app.url')); // Set root URL
                URL::forceScheme('https');
            }
        } catch (\Exception $e) {
            // Log and skip on database connection errors, etc.
            \Log::warning('AppServiceProvider boot error (likely during installation): '.$e->getMessage());

            return;
        }

        // Language settings
        // Prioritize session/cookie language during installation
        $request = $this->app['request'];
        $language = null;

        // List of valid locales
        $availableLocales = array_keys(config('language.languages', ['en' => 'English']));

        if ($request && $request->is('install*')) {
            // Session value (may be available before StartSession), otherwise cookie
            $sessionLocale = session()->get('install_locale');
            $cookieLocale = $request->cookie('install_locale');

            // Allow only valid locales
            if ($sessionLocale && in_array($sessionLocale, $availableLocales)) {
                $language = $sessionLocale;
            } elseif ($cookieLocale && in_array($cookieLocale, $availableLocales)) {
                $language = $cookieLocale;
            }
        }

        // If still not set, determine in order: environment variable → DB → config
        if (! $language) {
            // Prioritize environment variable directly (.env settings take highest priority)
            $language = env('APP_LOCALE') ?? config('app.locale', 'en');

            // Check if valid locale
            if (! in_array($language, $availableLocales)) {
                $language = 'en'; // Fallback to default
            }
        }
        app()->setLocale($language);

        // Load theme settings
        $adminTheme = config('themes.admin_theme', 'admin'); // Admin panel theme
        $themeDirectory = config('themes.theme_directory', 'themes'); // Theme directory
        $activeThemeDirectory = config('themes.active_theme', 'default-theme'); // Active theme
        $defaultTheme = config('themes.default_theme', 'default-theme'); // Default theme

        // Custom files directory (path relative to base_path)
        $customFilesDir = config('custom.custom_files_dir', 'custom');

        // Helper to return only existing directories (prevents view:cache from failing on non-existent directories)
        $existingDirs = fn (array $paths) => array_values(array_filter($paths, 'is_dir'));

        // Configure admin panel templates to prioritize views_custom for loading
        View::addNamespace('admin', $existingDirs([
            base_path("{$customFilesDir}/resources/views/{$adminTheme}"),
            resource_path("views/{$adminTheme}"),
        ]));

        // Configure shared components to prioritize views_custom for loading
        View::addNamespace('components', $existingDirs([
            base_path("{$customFilesDir}/resources/views/components"), // Prioritize custom components
            resource_path('views/components'),       // Default components
        ]));

        // Common layout namespace
        View::addNamespace('layouts', $existingDirs([
            base_path("{$customFilesDir}/resources/views/layouts"), // Prioritize custom layouts
            resource_path('views/layouts'),       // Default layouts
        ]));

        // Get currently active theme
        $enabledThemeDirectory = $this->getEnabledThemeDirectory();

        // Configure theme file loading to prioritize custom/resources/views for custom theme loading
        View::addNamespace('themes', $existingDirs([
            base_path("{$customFilesDir}/{$themeDirectory}/{$enabledThemeDirectory}/resources/views"),
            base_path("{$themeDirectory}/{$enabledThemeDirectory}/resources/views"),
            base_path("{$themeDirectory}/{$defaultTheme}/resources/views"),
        ]));

        // Bind core version (and any future shared admin-footer data) once
        // per render of the admin layout footer.
        View::composer('admin.partials.footer', AdminFooterComposer::class);

        // Add custom files directory
        $customFilesPath = base_path(config('custom.custom_files_dir', 'custom'));
        $fileTypes = config('app.file_types');

        // Load custom files after plugin loading
        $this->app->booted(function () use ($customFilesPath, $fileTypes) {
            // Check command line arguments to determine if plugin management command is running
            $isPluginManagementCommand = $this->isPluginManagementCommand();

            if (! $isPluginManagementCommand) {
                // Load plugins
                $this->loadEnabledPlugins();
            }
            // Load custom files
            foreach ($fileTypes as $type => $typeConfig) {
                $this->loadCustomFilesForType($customFilesPath, $typeConfig);
            }
            // Reorganize settings to apply plugin navigation configuration
            $this->reorderAllConfig();
        });
    }

    /**
     * Check if plugin management command is running
     */
    private function isPluginManagementCommand(): bool
    {
        // Check command line arguments
        $argv = $_SERVER['argv'] ?? [];

        // Check if it's a plugin management command
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
     * Get the plugin name to uninstall
     */
    private function getUninstallingPluginName(): ?string
    {
        $argv = $_SERVER['argv'] ?? [];

        // Format: artisan plugin:uninstall PluginName
        if (count($argv) >= 3 && $argv[1] === 'plugin:uninstall') {
            return $argv[2];
        }

        return null;
    }
}
