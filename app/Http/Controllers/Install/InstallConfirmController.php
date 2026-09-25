<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
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

namespace App\Http\Controllers\Install;

use App\Contracts\Site\SiteContextInterface;
use App\DTO\Core\CoreIntegrityResult;
use App\Helpers\ComposerLocalHelper;
use App\Helpers\GitExcludeHelper;
use App\Helpers\GitIgnoreHelper;
use App\Models\Site;
use App\Models\ThemeAudit;
use App\Services\Core\CoreIntegrityVerifier;
use App\Services\Csp\CspComplianceScanner;
use App\Services\Site\SettingResolver;
use App\Services\Theme\ThemePermissionService;
use App\Services\ThemeMigrator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Install - Confirmation screen
 */
class InstallConfirmController extends BaseInstallController
{
    /**
     * Display confirmation screen for input content
     */
    public function show()
    {
        $locale = $this->getCurrentLocale();
        app()->setLocale($locale);

        $data = session('install_data', []);

        // Output debug information to log
        Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.session_data_at_confirmation_display'), [
            'session_id' => session()->getId(),
            'session_keys' => array_keys($data),
            'has_site_name' => isset($data['site_name']),
            'has_admin_account_name' => isset($data['admin_account_name']),
            'has_admin_email' => isset($data['admin_email']),
            'has_admin_password' => isset($data['admin_password']),
            'has_app_env' => isset($data['app_env']),
            'has_app_url' => isset($data['app_url']),
            'has_admin_url' => isset($data['admin_url']),
            'has_app_timezone' => isset($data['app_timezone']),
            'has_db_connection' => isset($data['db_connection']),
            'has_db_host' => isset($data['db_host']),
            'has_db_port' => isset($data['db_port']),
            'has_db_database' => isset($data['db_database']),
            'has_db_username' => isset($data['db_username']),
            'full_data_keys' => $data ? array_keys($data) : 'empty',
        ]);

        // Check required fields and redirect to appropriate step based on missing fields.
        //
        // SQLite has no server to reach, so the database step neither shows nor
        // submits host / port / username, and InstallDatabaseController blanks
        // them in .env on purpose (a leftover DB_HOST=mysql from the docker
        // preset would otherwise make other code paths attempt a TCP connect).
        // Requiring them here made every SQLite installation fail this check.
        $steps = [
            'settings' => ['site_name', 'admin_account_name', 'admin_email', 'admin_password'],
            'environment' => ['app_env', 'app_url', 'admin_url', 'app_timezone'],
            'database' => $this->requiredDatabaseFields($data['db_connection'] ?? null),
        ];

        // Start over if session data is completely empty
        if (empty($data)) {
            Log::channel('install')->error(__('http/controllers/install/install_confirm_controller.session_data_completely_empty'));

            return redirect()->route('install.index')
                ->with('error', __('http/controllers/install/install_confirm_controller.session_data_lost_restart_installation'));
        }

        // Check required fields for each step
        $missingFields = [];
        foreach ($steps as $step => $fields) {
            foreach ($fields as $field) {
                if (empty($data[$field])) {
                    $missingFields[] = ['step' => $step, 'field' => $field];
                }
            }
        }

        // If there are missing fields
        if (! empty($missingFields)) {
            $firstMissing = $missingFields[0];
            Log::channel('install')->error(__('http/controllers/install/install_confirm_controller.required_fields_missing'), [
                'missing_fields' => $missingFields,
                'session_keys' => array_keys($data),
                'session_id' => session()->getId(),
                'redirecting_to_step' => $firstMissing['step'],
            ]);

            // Route names are `install.settings` / `install.environment` /
            // `install.database` — the GET step screens. Appending `.create`
            // (the controller method name) produced undefined route names, so
            // this recovery path threw RouteNotFoundException instead of
            // sending the operator back to fix the field.
            return redirect()->route('install.'.$firstMissing['step'])
                ->with('error', __('install/common.missing_required_fields').__('http/controllers/install/install_confirm_controller.missing_field', ['field' => $firstMissing['field']]));
        }

        // Retrieve email test results from session
        $mailTestStatus = [
            'connection_tested' => (bool) ($data['mail_connection_tested'] ?? false),
            'connection_test_date' => $data['mail_connection_test_date'] ?? null,
            'send_tested' => (bool) ($data['mail_send_tested'] ?? false),
            'send_test_date' => $data['mail_send_test_date'] ?? null,
            'receive_tested' => (bool) ($data['mail_receive_tested'] ?? false),
            'receive_test_date' => $data['mail_receive_test_date'] ?? null,
        ];

        return view('install.confirm', [
            'data' => $data,
            'mailTestStatus' => $mailTestStatus,
            'currentLocale' => $locale,
            'availableLocales' => $this->availableLocales,
        ]);

        // NOTE: the visible core-integrity pre-flight panel is intentionally not
        // shown on the confirm screen for the initial release (the core
        // signature-check feature is not surfaced to users yet). The store()
        // INVALID guard below is kept as a dormant safety net — it only ever
        // triggers for a genuinely tampered *signed* core, which cannot happen
        // while core is unsigned. Re-introduce coreIntegrityPanel() + the
        // confirm.blade.php panel when shipping core signing.
        // See .backlog/core-signing-deferred.md.
    }

    /**
     * Required database fields for the chosen driver.
     *
     * SQLite only needs the file path; every other driver needs the server
     * coordinates as well. Kept as its own method so the confirm screen and
     * its tests share one definition.
     *
     * @return array<int, string>
     */
    protected function requiredDatabaseFields(?string $connection): array
    {
        if ($connection === 'sqlite') {
            return ['db_connection', 'db_database'];
        }

        return ['db_connection', 'db_host', 'db_port', 'db_database', 'db_username'];
    }

    /**
     * Execute installation
     */
    public function store()
    {
        try {
            Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.installation_started'));

            $data = session('install_data');
            Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.session_data_retrieved'), [
                'keys' => array_keys($data ?? []),
                'session_id' => session()->getId(),
                'full_data' => $data,
            ]);

            // Redirect to confirmation screen if session data is empty
            if (empty($data)) {
                Log::channel('install')->error(__('http/controllers/install/install_confirm_controller.session_empty_redirect_to_confirm'));

                return redirect()->route('install.confirm')
                    ->with('error', '');
            }

            // Core integrity gate: block installation only when the signature is
            // INVALID (manifest present but signature fails = tampering). Unsigned
            // dev builds and modified-but-authentic releases are allowed through.
            if (app(CoreIntegrityVerifier::class)->verify()->status === CoreIntegrityResult::STATUS_INVALID) {
                Log::channel('install')->error('Install blocked: core integrity is INVALID (signature verification failed).');

                return redirect()->route('install.confirm')
                    ->with('error', __('install/confirm.integrity.blocked'));
            }

            // Decrypt administrator password
            $adminPassword = Crypt::decryptString($data['admin_password']);
            Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.admin_password_decrypted'));

            // Decrypt DB password (do not decrypt if empty string)
            $dbPassword = (! empty($data['db_password'])) ? Crypt::decryptString($data['db_password']) : '';
            Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.db_password_decrypted'));

            // SQLite: normalise what the wizard collected before it reaches
            // .env and the migration.
            //
            // The database step hides host / port / user with `x-show`, which
            // only hides them — the browser still submits whatever was in
            // those inputs, so the session can carry the docker preset
            // (DB_HOST=mysql, DB_USERNAME=dixlase) next to DB_CONNECTION=sqlite.
            // Blank them here so the written .env describes the driver in use.
            //
            // The file itself is created only by the connection test on the
            // database step, which is gated client-side and is not re-run when
            // the path field changes afterwards. A missing file makes the
            // connector throw at `migrate`, half-way through the install, so
            // resolve the path and create the file here as well.
            if (($data['db_connection'] ?? null) === 'sqlite') {
                $data['db_database'] = $this->resolveSqliteDatabasePath($data['db_database'] ?? null);
                $data['db_host'] = '';
                $data['db_port'] = '';
                $data['db_username'] = '';
                $dbPassword = '';

                if (! $this->ensureSqliteDatabaseFile($data['db_database'])) {
                    Log::channel('install')->error(__('http/controllers/install/install_confirm_controller.sqlite_database_file_unusable'), [
                        'database' => $data['db_database'],
                    ]);

                    return redirect()->route('install.database')
                        ->with('error', __('install/step3.db_sqlite_file_error', ['path' => $data['db_database']]));
                }

                session(['install_data' => array_merge(session('install_data', []), [
                    'db_database' => $data['db_database'],
                    'db_host' => '',
                    'db_port' => '',
                    'db_username' => '',
                ])]);
            }

            // Decrypt email password (do not decrypt if empty string)
            $mailPassword = (! empty($data['mail_password'])) ? Crypt::decryptString($data['mail_password']) : '';
            Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.mail_password_decrypted'));

            // Get force_ssl value
            $forceSslBool = ! empty($data['force_ssl']);

            // Determine APP_URL protocol based on force_ssl
            $protocol = $forceSslBool ? 'https://' : 'http://';
            $appUrl = $protocol.$data['app_url'];

            // Get language settings from session
            $locale = session('install_locale', 'en');

            // SESSION_COOKIE: an installation gets a cookie name of its own so
            // two Dixlase sites on one hostname (localhost:8080 and
            // localhost:8081, say) cannot overwrite each other's session.
            //
            // That name is NOT decided here. This request writes .env and then
            // keeps working for another minute (migrations, seeds, the admin
            // insert), and every later request in the wizard reads the new
            // name while the browser still holds a cookie under the old one —
            // the session comes back empty and the step middleware sends the
            // operator back to step 1 with every field cleared. So keep
            // whatever name is in place and let finalize() name it once the
            // wizard is done. `config/session.php` falls back to
            // `dixlase_session` while the entry is blank.
            $existingSessionCookie = $this->readEnvValue('SESSION_COOKIE');

            $envPath = base_path('.env');

            $envData = [
                'APP_NAME' => $data['site_name'],
                'APP_ENV' => $data['app_env'],
                'APP_DEBUG' => $data['app_debug'] ? 'true' : 'false',
                'APP_URL' => $appUrl,
                'APP_LOCALE' => $data['app_locale'] ?? 'ja',
                // APP_TIMEZONE is fixed to UTC (storage and calculation use UTC. Display TZ is managed by site_settings.display_timezone)
                'APP_TIMEZONE' => 'UTC',
                'INSTALLED' => 'false',
                'FORCE_SSL' => $data['force_ssl'] ? 'true' : 'false',
                'MAINTENANCE_MODE' => 'false',

                // Session settings
                'SESSION_DRIVER' => 'database',
                'SESSION_LIFETIME' => '120',
                'SESSION_ENCRYPT' => 'false',

                // Email settings
                'MAIL_MAILER' => $data['mail_mailer'] ?? 'smtp',
                'MAIL_HOST' => $data['mail_host'] ?? 'localhost',
                'MAIL_PORT' => $data['mail_port'] ?? 1025,
                'MAIL_USERNAME' => $data['mail_username'] ?? 'null',
                'MAIL_PASSWORD' => $mailPassword ?? 'null',
                'MAIL_ENCRYPTION' => $data['mail_encryption'] ?? 'null',
                'MAIL_FROM_ADDRESS' => $data['mail_from_address'] ?? $data['admin_email'],
                'MAIL_FROM_NAME' => "\"{$data['site_name']}\"",

                // DB settings
                'DB_CONNECTION' => $data['db_connection'],
                'DB_HOST' => $data['db_host'],
                'DB_PORT' => $data['db_port'],
                'DB_DATABASE' => $data['db_database'],
                'DB_USERNAME' => $data['db_username'],
                'DB_PASSWORD' => $dbPassword ?? '',
            ];

            if ($existingSessionCookie !== '') {
                $envData['SESSION_COOKIE'] = $existingSessionCookie;
            }

            // Update .env file
            Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.update_env_file_started'));
            $this->updateEnv($envData);
            Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.update_env_file_completed'));

            // Clear settings and apply new .env
            Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.config_cache_clear_started'));
            Artisan::call('config:clear');
            Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.config_cache_clear_completed'));

            // Artisan::call('config:clear') only deletes the cached config
            // file on disk — it does NOT refresh $app['config'] in the
            // current request. Artisan::call sub-kernels (migrate:fresh,
            // db:seed) re-read .env on their own, but anything that runs
            // directly in this controller (initializeDatabase below) would
            // otherwise keep using the boot-time DB_CONNECTION. Force the
            // in-memory config to match the freshly-written .env so every
            // subsequent in-request DB call targets the chosen driver.
            $newDriver = $data['db_connection'];
            config([
                'database.default' => $newDriver,
                "database.connections.{$newDriver}.database" => $data['db_database'],
            ]);
            if ($newDriver !== 'sqlite') {
                config([
                    "database.connections.{$newDriver}.host" => $data['db_host'],
                    "database.connections.{$newDriver}.port" => $data['db_port'],
                    "database.connections.{$newDriver}.username" => $data['db_username'],
                    "database.connections.{$newDriver}.password" => $dbPassword,
                ]);
            }
            DB::purge();

            // Temporarily change session driver to file during migration
            $envContent = file_get_contents($envPath);

            // Save original SESSION_DRIVER
            preg_match('/SESSION_DRIVER=(.+)/', $envContent, $matches);
            $originalSessionDriver = $matches[1] ?? 'guard-aware-database';

            // Change SESSION_DRIVER to file
            $envContent = preg_replace('/SESSION_DRIVER=.+/', 'SESSION_DRIVER=file', $envContent);
            file_put_contents($envPath, $envContent);

            // Reload settings
            Artisan::call('config:clear');
            Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.session_driver_changed_to_file'), ['original' => $originalSessionDriver]);

            // Check whether to reset database
            if (empty($data['preserve_data'])) {
                Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.database_reset_migration_started'));
                Artisan::call('migrate:fresh', ['--force' => true]);
                Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.database_reset_migration_completed'));
            } else {
                Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.migration_started_data_preserved'));
                Artisan::call('migrate', ['--force' => true]);
                Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.migration_completed_data_preserved'));
            }

            // Generate the Tailwind plugin-source aggregator so themes' tailwind.css
            // @import targets exist before the asset build pipeline runs.
            // Safe to call when no plugins are installed yet — emits an empty stub.
            Artisan::call('dls:tailwind:regenerate-plugin-sources');

            // Restore session driver
            $envContent = file_get_contents($envPath);
            $envContent = preg_replace('/SESSION_DRIVER=.+/', 'SESSION_DRIVER='.$originalSessionDriver, $envContent);
            file_put_contents($envPath, $envContent);

            // Reload settings
            Artisan::call('config:clear');
            Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.session_driver_restored'), ['driver' => $originalSessionDriver]);

            // Re-establish DB connection (to correctly apply table prefix)
            DB::purge();
            DB::reconnect();

            // Debug: Get current table prefix and all tables list.
            // Use Schema::getTableListing() so this works on every supported
            // driver (MySQL / PostgreSQL / SQLite). The raw "SHOW TABLES" is
            // MySQL-only and fails on SQLite with a syntax error.
            $prefix = DB::connection()->getTablePrefix();
            $tableNames = Schema::getTableListing();
            Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.db_connection_reestablished'), [
                'prefix' => $prefix,
                'tables_count' => count($tableNames),
                'sample_tables' => array_slice($tableNames, 0, 5),
            ]);

            // Run seeder (skip table existence check and execute unconditionally)
            Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.database_seeder_started'));
            Artisan::call('db:seed', [
                '--class' => 'DatabaseSeeder',
                '--force' => true,
            ]);
            Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.database_seeder_completed'));

            // Run theme migrations via ThemeMigrator so the rows land in
            // dls_theme_migrations (matching the ThemeInstall CLI path).
            // The previous Artisan::call('migrate', --path ...) recorded
            // them in dls_migrations instead, which left the rows in the
            // wrong ledger and exposed the theme migrations to stock
            // `php artisan migrate` runs (re-applying already-applied
            // migrations and hitting "table already exists").
            Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.dixlase_onepage_migration_started'));
            $themeMigrator = new ThemeMigrator(
                app(\Illuminate\Filesystem\Filesystem::class),
                app(\Illuminate\Database\ConnectionResolverInterface::class),
                'theme_migrations',
                'dixlase-onepage',
            );
            $themeMigrator->migrate('DixlaseOnePage');
            Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.dixlase_onepage_migration_completed'));

            // Run theme seeders
            // Dynamically register PSR-4 to support environments without composer.local.json applied
            Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.dixlase_onepage_seeder_started'));
            $this->registerThemeAutoload('DixlaseOnePage');
            Artisan::call('db:seed', [
                '--class' => 'Themes\\DixlaseOnePage\\Database\\Seeders\\DatabaseSeeder',
                '--force' => true,
            ]);
            Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.dixlase_onepage_seeder_completed'));

            // Insert initial data
            Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.initial_data_insertion_started'));
            $this->initializeDatabase($data, $adminPassword);
            Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.initial_data_insertion_completed'));

            // Create storage symbolic link.
            //
            // `php artisan storage:link` only creates the public/storage
            // symlink itself; the target (storage/app/public/) is assumed
            // to exist as a real directory. On a fresh checkout where the
            // directory was never committed (or was wiped by an external
            // tool), the resulting symlink is dangling. Any PHP filesystem
            // op that subsequently follows it — most notably
            // CoreSourceSnapshot::capture() copying public/ for a core
            // update — then fails with `Failed to open stream: No such
            // file or directory`, aborts the update mid-flight, and
            // leaves storage/app/private/core-update/.in-progress stuck
            // until the 900s UI timeout. Ensure the target exists first
            // so the symlink is always valid.
            Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.storage_symlink_creation_started'));
            File::ensureDirectoryExists(storage_path('app/public'));
            // `--relative` so the recorded target is `../storage/app/public`
            // rather than an absolute path anchored at the PHP container's
            // mount point. On split-container topologies (nginx and php-fpm
            // in different containers with different app-root mounts), the
            // absolute form does not resolve from nginx and every
            // `/storage/*` request 404s.
            Artisan::call('storage:link', ['--relative' => true]);
            Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.storage_symlink_creation_completed'));

            // Create symbolic link for active theme
            Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.theme_symlink_creation_started'));
            $themeSetting = DB::table('theme_settings')
                ->where('key', 'enabled_theme_id')
                ->first();

            $activeTheme = null;
            if ($themeSetting && $themeSetting->value) {
                $activeTheme = DB::table('themes')
                    ->where('id', $themeSetting->value)
                    ->select('directory')
                    ->first();
            }

            if ($activeTheme) {
                try {
                    Artisan::call('dls:theme:symlink', [
                        'action' => 'create',
                        'theme' => $activeTheme->directory,
                    ]);
                    Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.theme_symlink_creation_completed'), ['theme' => $activeTheme->directory]);

                    GitExcludeHelper::addThemeExclusion($activeTheme->directory);
                    GitIgnoreHelper::addThemeExclusion($activeTheme->directory);
                    Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.theme_git_exclusion_rule_added'), ['theme' => $activeTheme->directory]);
                } catch (\Exception $e) {
                    Log::channel('install')->error(__('http/controllers/install/install_confirm_controller.symlink_creation_failed', ['_e__getmessage__' => $e->getMessage()]));
                }
            } else {
                Log::channel('install')->warning(__('http/controllers/install/install_confirm_controller.no_active_theme_symlink_not_created'));
            }

            // Generate the Tailwind plugin-source aggregator. The default
            // theme imports the resulting file during its Vite build, so
            // it must exist (possibly empty) after the very first install
            // to keep `npm run build` working before any plugin is
            // enabled.
            try {
                $aggregatorResult = app(\App\Services\Tailwind\PluginSourceAggregator::class)->regenerate();
                Log::channel('install')->info('Tailwind plugin-source aggregator generated', $aggregatorResult);
            } catch (\Throwable $e) {
                Log::channel('install')->error('Failed to generate Tailwind plugin-source aggregator', [
                    'error' => $e->getMessage(),
                ]);
            }

            // Generate file integrity baseline
            Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.file_integrity_baseline_started'));
            try {
                $fileIntegrityService = app(\App\Services\FileIntegrityService::class);
                $baseline = $fileIntegrityService->generateCoreBaseline();
                $fileIntegrityService->saveBaselineArray($baseline);

                \App\Models\FileIntegrityAudit::create([
                    'scope' => \App\Models\FileIntegrityAudit::SCOPE_CORE,
                    'trigger' => \App\Models\FileIntegrityAudit::TRIGGER_INSTALL,
                    'initiated_by_type' => \App\Models\FileIntegrityAudit::INITIATED_BY_SYSTEM,
                    'status' => \App\Models\FileIntegrityAudit::STATUS_OK,
                    'hash_algo' => 'sha256',
                    'baseline_version' => $baseline['meta']['app_version'] ?? null,
                    'total_files_scanned' => count($baseline['files']),
                    'started_at' => now(),
                    'finished_at' => now(),
                    'duration_ms' => 0,
                    'summary' => __('command.integrity.baseline_generated'),
                ]);

                Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.file_integrity_baseline_completed'), [
                    'files_count' => count($baseline['files']),
                    'version' => $baseline['meta']['app_version'] ?? 'unknown',
                ]);
            } catch (\Exception $e) {
                Log::channel('install')->warning(__('http/controllers/install/install_confirm_controller.file_integrity_baseline_failed_continue'), [
                    'error' => $e->getMessage(),
                ]);
            }

            // Run security audit for bundled themes
            $this->auditBundledThemes();

            // Persist theme/plugin PSR-4 autoload to vendor/composer/autoload_psr4.php.
            //
            // registerThemeAutoload() above only patches the running PHP-FPM
            // worker so the install-time db:seed call can resolve theme
            // classes. The next request lands on a different worker (or the
            // same worker after Laravel re-bootstraps), with no memory of
            // that runtime addPsr4(). Without persisting, ThemeServiceProvider
            // fails class_exists() on the theme's provider, never registers
            // it, never require_once's the theme helpers file, and the very
            // first front-page render after install 500s on the now-undefined
            // dls_<theme>_localized_setting() call from the hero blade.
            Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.composer_autoload_sync_started'));
            if (ComposerLocalHelper::syncAutoload()) {
                Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.composer_autoload_sync_completed'));
            } else {
                Log::channel('install')->warning(__('http/controllers/install/install_confirm_controller.composer_autoload_sync_failed'));
            }

            // Delete session data
            session()->forget('install_data');
            Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.installation_completed'));
            Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.redirecting_to_install_complete'));

            return redirect()->route('install.complete');
        } catch (\Exception $e) {
            Log::channel('install')->error(__('http/controllers/install/install_confirm_controller.installation_error'), [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'session_data_exists' => ! empty($data),
                'session_keys' => array_keys($data ?? []),
            ]);

            // Verify session data is not lost
            if (empty(session('install_data'))) {
                Log::channel('install')->warning(__('http/controllers/install/install_confirm_controller.session_data_lost_attempting_recovery'));
                if (! empty($data)) {
                    session(['install_data' => $data]);
                    Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.session_data_restored'));
                }
            }

            // Create user-friendly error message
            $errorMessage = $this->getInstallationErrorMessage($e);

            return redirect()->route('install.mode')
                ->with('error', $errorMessage)
                ->with('error_details', $e->getMessage());
        }
    }

    /**
     * Get user-friendly error message for installation errors
     */
    private function getInstallationErrorMessage(\Exception $e)
    {
        $errorMessage = $e->getMessage();

        if (strpos($errorMessage, 'database') !== false) {
            return __('install/error.database_general');
        }

        if (strpos($errorMessage, 'file') !== false || strpos($errorMessage, 'directory') !== false) {
            return __('install/error.file_system');
        }

        if (strpos($errorMessage, 'env') !== false || strpos($errorMessage, 'environment') !== false) {
            return __('install/error.environment');
        }

        return __('install/error.unknown');
    }

    /**
     * Dynamically register PSR-4 autoload for theme
     *
     * In environments where composer.local.json is not reflected (when Docker build is run with --no-scripts
     * and sync-local-autoload.php was not executed, etc.), enable theme
     * seeders etc. to be loaded during installation
     */
    private function registerThemeAutoload(string $themeDirectory): void
    {
        $loaders = \Composer\Autoload\ClassLoader::getRegisteredLoaders();
        if (empty($loaders)) {
            return;
        }

        $loader = reset($loaders);
        $baseNs = "Themes\\{$themeDirectory}\\";
        $basePath = base_path("themes/{$themeDirectory}");

        $loader->addPsr4($baseNs.'App\\', $basePath.'/app');
        $loader->addPsr4($baseNs.'Database\\Factories\\', $basePath.'/database/factories');
        $loader->addPsr4($baseNs.'Database\\Seeders\\', $basePath.'/database/seeders');
    }

    /**
     * Add initial data to database
     */
    private function initializeDatabase(array $data, string $adminPassword)
    {
        Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.initialize_database_started_admin_email').$data['admin_email'].', admin_account_name='.$data['admin_account_name']);

        Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.initialize_database_site_settings_update'));

        $baseSettings = [
            'app_name' => $data['site_name'],
            'locale' => $data['app_locale'] ?? 'ja',
            'display_timezone' => $data['app_timezone'] ?? 'Asia/Tokyo',
            'mail_mailer' => $data['mail_mailer'] ?? 'smtp',
            'mail_host' => $data['mail_host'] ?? 'localhost',
            'mail_port' => (string) ($data['mail_port'] ?? 587),
            'mail_username' => $data['mail_username'] ?? '',
            'mail_password' => $data['mail_password'] ?? '',
            'mail_encryption' => $data['mail_encryption'] ?? '',
            'mail_from_address' => $data['mail_from_address'] ?? $data['admin_email'],
            'maintenance_mode' => '0',
            'maintenance_message' => __('http/controllers/install/install_confirm_controller.currently_under_maintenance'),
            'system_admin_email' => $data['admin_email'],
            'site_name' => $data['site_name'],
            'admin_mode' => (string) ($data['install_mode'] ?? 0),
        ];

        // Route through SettingResolver so each key lands in the correct
        // multisite-aware store: Global keys (app_name, admin_url,
        // system_admin_email, admin_mode, force_ssl) go to global_settings;
        // PerSite keys (maintenance_mode, site_name, locale, timezone,
        // mail_*_tested) go to site_settings for the primary site;
        // Overridable keys (mail_*) default to global_settings.
        $resolver = app(SettingResolver::class);

        foreach ($baseSettings as $name => $value) {
            $resolver->set($name, $value);
            Log::channel('install')->info("initializeDatabase - {$name}: {$value}");
        }

        // Sync the canonical Site.name column. SitesSeeder creates the row
        // with a placeholder before this point, and nothing used to replace
        // it, so every installation started with Site::name = 'Main Site'
        // while the operator's own name lived only in .env and the settings
        // tables -- canonical in the definition comment, and wrong in the
        // database from the first request onwards.
        //
        // Same shape as the locale sync in AdminBaseSiteController: the
        // column is authoritative and the site_name setting written above is
        // the legacy shadow kept for backward-compatible reads.
        Site::query()
            ->whereKey(app(SiteContextInterface::class)->currentSiteId())
            ->update(['name' => $data['site_name']]);
        Log::channel('install')->info('initializeDatabase - sites.name: '.$data['site_name']);

        // Admin panel URL (admin_url is Global scope so goes to global_settings)
        $resolver->set('admin_url', $data['admin_url']);
        Log::channel('install')->info('initializeDatabase - admin_url: '.$data['admin_url']);

        // Save mail test results (PerSite scope)
        Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.initialize_database_mail_test_save'));
        $mailTestFields = [
            'mail_connection_tested' => $data['mail_connection_tested'] ?? 0,
            'mail_connection_test_date' => $data['mail_connection_test_date'] ?? null,
            'mail_send_tested' => $data['mail_send_tested'] ?? 0,
            'mail_send_test_date' => $data['mail_send_test_date'] ?? null,
            'mail_receive_tested' => $data['mail_receive_tested'] ?? 0,
            'mail_receive_test_date' => $data['mail_receive_test_date'] ?? null,
        ];

        foreach ($mailTestFields as $fieldName => $fieldValue) {
            if ($fieldValue !== null) {
                $resolver->set($fieldName, $fieldValue);
                Log::channel('install')->info("initializeDatabase - {$fieldName}: {$fieldValue}");
            }
        }
        Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.initialize_database_mail_test_completed'));

        // Security-related settings (all Global scope, automatically to global_settings)
        $securitySettings = [
            'enable_allowed_admin_ips' => $data['enable_allowed_admin_ips'] ?? 0,
            'allowed_admin_ips' => ($data['enable_allowed_admin_ips'] ?? 0) ? ($data['allowed_admin_ips'] ?? '') : '',
            'enable_blocked_admin_ips' => $data['enable_blocked_admin_ips'] ?? 0,
            'blocked_admin_ips' => ($data['enable_blocked_admin_ips'] ?? 0) ? ($data['blocked_admin_ips'] ?? '') : '',
            'enable_allowed_front_ips' => $data['enable_allowed_front_ips'] ?? 0,
            'allowed_front_ips' => ($data['enable_allowed_front_ips'] ?? 0) ? ($data['allowed_front_ips'] ?? '') : '',
            'enable_blocked_front_ips' => $data['enable_blocked_front_ips'] ?? 0,
            'blocked_front_ips' => ($data['enable_blocked_front_ips'] ?? 0) ? ($data['blocked_front_ips'] ?? '') : '',
            'session_driver' => 'database',
            'session_lifetime' => '120',
            'session_encrypt' => '0',
        ];

        foreach ($securitySettings as $name => $value) {
            $resolver->set($name, $value);
        }

        // Administrator account processing
        Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.initialize_database_admin_processing'));
        $admin = DB::table('members')->where('email', $data['admin_email'])->first();

        $installLocale = $data['app_locale'] ?? session('install_locale', 'ja');
        Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.initialize_database_language_setting').$installLocale);

        if ($admin) {
            Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.initialize_database_existing_admin_update').$admin->id);
            DB::table('members')
                ->where('id', $admin->id)
                ->update([
                    'account_name' => $data['admin_account_name'],
                    'display_name' => $data['admin_display_name'] ?? null,
                    'locale' => $installLocale,
                    'password' => Hash::make($adminPassword),
                    'role' => 10,
                    'status' => 1,
                    'email_verified_at' => now(),
                    'updated_at' => now(),
                ]);
            Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.init_db_existing_admin_update_completed').$installLocale.'）');
            $adminMemberId = $admin->id;
        } else {
            Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.initialize_database_new_admin_started'));
            $memberId = DB::table('members')->insertGetId([
                'account_name' => $data['admin_account_name'],
                'display_name' => $data['admin_display_name'] ?? null,
                'email' => $data['admin_email'],
                'email_verified_at' => now(),
                'locale' => $installLocale,
                'role' => 10,
                'password' => Hash::make($adminPassword),
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.init_db_new_admin_creation_completed').$memberId.__('http/controllers/install/install_confirm_controller.language_setting_prefix').$installLocale.'）');
            $adminMemberId = $memberId;
        }

        $memberCount = DB::table('members')->count();
        Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.initialize_database_members_total_count').$memberCount);

        $this->seedCoreReleaseState($adminMemberId);

        Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.initialize_database_completed'));
    }

    /**
     * Seed the singleton core_releases row and the initial core_version_history
     * entry. The state row tracks remote update detection; the history row marks
     * the install transition (`null` -> current version).
     */
    private function seedCoreReleaseState(int $adminMemberId): void
    {
        Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.init_db_core_releases_insertion_started'));

        DB::table('core_releases')->updateOrInsert(
            ['id' => \App\Models\CoreRelease::PRIMARY_ID],
            [
                'installation_method' => \App\Models\CoreVersionHistory::METHOD_INSTALL,
                'installed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Baseline the ledger to the actual on-disk VERSION file, not
        // config('app.version'). The latter is unset in shipped releases,
        // so it collapsed to the '0.1.0' default and recorded a bogus
        // baseline: the admin then showed the wrong "current version" and
        // VersionDriftService flagged drift (VERSION 0.3.x vs ledger 0.1.0).
        // Fall back to config only when no VERSION file is present.
        $currentVersion = \App\Services\Core\CoreUpdater::readVersionFromDisk()
            ?? (string) config('app.version', '0.1.0');
        DB::table('core_version_history')->insert([
            'old_version' => null,
            'new_version' => $currentVersion,
            'installation_method' => \App\Models\CoreVersionHistory::METHOD_INSTALL,
            'applied_by_id' => $adminMemberId,
            'applied_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \App\Models\CoreVersionHistory::forgetCurrentVersionCache();

        Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.init_db_core_releases_insertion_done'), [
            'version' => $currentVersion,
            'admin_id' => $adminMemberId,
        ]);
    }

    /**
     * Run security audit for bundled themes
     *
     * Scan permission declarations, signatures, and CSP compliance status of themes registered during installation,
     * and save audit results to DB. Installation continues even if this fails
     *
     * @see \App\Http\Controllers\Admin\Settings\AdminThemesSettingsController::runThemeAudit()
     */
    private function auditBundledThemes(): void
    {
        Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.bundled_theme_security_audit_started'));

        $themes = DB::table('themes')->select('slug')->get();

        foreach ($themes as $theme) {
            $slug = $theme->slug;
            try {
                Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.theme_audit_started', ['slug' => $slug]));

                // Check permission declaration consistency (theme.json vs actual code)
                Artisan::call('dls:theme:audit', [
                    'theme' => $slug,
                    '--json' => true,
                ]);
                Log::channel('install')->debug("theme audit: artisan call returned for {$slug}");

                $output = trim(Artisan::output());
                $result = json_decode($output, true);
                Log::channel('install')->debug("theme audit: json decoded for {$slug}", [
                    'output_length' => strlen($output),
                    'json_error' => json_last_error_msg(),
                    'is_array' => is_array($result),
                ]);

                if (json_last_error() !== JSON_ERROR_NONE || ! is_array($result)) {
                    Log::channel('install')->warning(__('http/controllers/install/install_confirm_controller.theme_audit_json_parse_failed', ['slug' => $slug]), [
                        'json_error' => json_last_error_msg(),
                        'output_length' => strlen($output),
                        'output_preview' => substr($output, 0, 500),
                    ]);

                    continue;
                }

                // Limit evidence (reduce DB size)
                $mismatches = $result['mismatches'] ?? [];
                foreach ($mismatches as &$mismatch) {
                    if (isset($mismatch['evidence']) && is_array($mismatch['evidence'])) {
                        $mismatch['evidence'] = array_slice($mismatch['evidence'], 0, 3);
                    }
                }
                unset($mismatch);

                // Get signature information
                $permissionService = app(ThemePermissionService::class);
                $summary = $permissionService->getSummary($slug);
                $signature = $summary['signature'] ?? [];
                Log::channel('install')->debug("theme audit: permission summary for {$slug}", [
                    'has_signature' => ! empty($signature),
                ]);

                // Verify CSP compliance status with code scan
                $cspScanner = app(CspComplianceScanner::class);
                $cspCompatibility = $cspScanner->scanTheme($slug);
                Log::channel('install')->debug("theme audit: CSP scan for {$slug}", [
                    'csp_status' => $cspCompatibility['status'] ?? null,
                ]);

                $auditData = [
                    'has_mismatches' => ! empty($mismatches),
                    'mismatches' => $mismatches,
                    'matches_count' => count($result['matches'] ?? []),
                    'total_checked' => $result['total_checked'] ?? 0,
                    'risk_level' => $result['risk_level'] ?? null,
                    'risk_reasons' => $result['risk_reasons'] ?? [],
                    'signature_status' => $signature['status'] ?? 'unsigned',
                    'signature_signer' => $signature['signer'] ?? null,
                    'csp_status' => $cspCompatibility['status'] ?? 'not_checked',
                    'csp_requires_inline_js' => $cspCompatibility['requires_inline_js'] ?? false,
                    'csp_requires_inline_css' => $cspCompatibility['requires_inline_css'] ?? false,
                    'csp_violations' => $cspCompatibility['violations'] ?? [],
                    'csp_summary' => $cspCompatibility['summary'] ?? [],
                ];

                $saved = ThemeAudit::saveAuditResult($slug, $auditData);
                Log::channel('install')->debug("theme audit: saved to DB for {$slug}", [
                    'audit_id' => $saved->id ?? null,
                ]);

                Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.theme_audit_completed', ['slug' => $slug]), [
                    'risk_level' => $auditData['risk_level'],
                    'csp_status' => $auditData['csp_status'],
                    'has_mismatches' => $auditData['has_mismatches'],
                ]);
            } catch (\Throwable $e) {
                Log::channel('install')->error(__('http/controllers/install/install_confirm_controller.theme_audit_failed_continue_install', ['slug' => $slug]), [
                    'error_class' => get_class($e),
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.bundled_theme_security_audit_completed'));
    }
}
