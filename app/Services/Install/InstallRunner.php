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

declare(strict_types=1);

namespace App\Services\Install;

use App\Contracts\Site\SiteContextInterface;
use App\DTO\Core\CoreIntegrityResult;
use App\Exceptions\Install\CoreIntegrityBlockedException;
use App\Exceptions\Install\SqliteDatabaseUnusableException;
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
use App\Support\Install\EnvFile;
use App\Support\Install\SqliteDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * @internal For Core use only. Do not reference from plugins/themes
 *
 * Performs an installation.
 *
 * Everything here used to live inside InstallConfirmController::store(),
 * where it could only ever be reached through a browser. The wizard is a
 * poor fit for the one-liner installer — it needs a running web server,
 * and PHP's built-in server restarts on every .env write — so the same
 * pipeline is now callable from `dls:install` as well.
 *
 * The service deals in a plain settings array (the shape the wizard keeps
 * in `install_data`) plus already-decrypted passwords. Sessions,
 * redirects and flash messages stay with the caller.
 */
class InstallRunner
{
    /**
     * Normalise the collected settings before anything is written.
     *
     * SQLite needs this: the wizard's database step hides host / port /
     * user with `x-show`, which only hides them — the browser still
     * submits whatever was in those inputs, so the settings can carry the
     * docker preset (DB_HOST=mysql, DB_USERNAME=dixlase) next to
     * DB_CONNECTION=sqlite. The database file also has to exist before
     * the migration: the connector throws instead of creating it, and
     * only the wizard's connection test creates one today.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws SqliteDatabaseUnusableException
     */
    public function prepare(array $data): array
    {
        if (($data['db_connection'] ?? null) !== 'sqlite') {
            return $data;
        }

        $data['db_database'] = SqliteDatabase::resolvePath($data['db_database'] ?? null);
        $data['db_host'] = '';
        $data['db_port'] = '';
        $data['db_username'] = '';
        $data['db_password'] = '';

        if (! SqliteDatabase::ensureFile($data['db_database'])) {
            Log::channel('install')->error('SQLite database file is missing and could not be created', [
                'database' => $data['db_database'],
            ]);

            throw new SqliteDatabaseUnusableException($data['db_database']);
        }

        return $data;
    }

    /**
     * Run the installation.
     *
     * Expects settings that have already been through prepare(), and
     * passwords in plain text. Reports coarse progress through the
     * optional callback so a CLI caller can show it; the web wizard
     * ignores it.
     *
     * @param  array<string, mixed>  $data
     * @param  (callable(string): void)|null  $progress
     *
     * @throws CoreIntegrityBlockedException
     * @throws \Throwable
     */
    public function run(
        array $data,
        string $adminPassword,
        string $dbPassword = '',
        string $mailPassword = '',
        ?callable $progress = null
    ): void {
        // Core integrity gate: block installation only when the signature is
        // INVALID (manifest present but signature fails = tampering). Unsigned
        // dev builds and modified-but-authentic releases are allowed through.
        if (app(CoreIntegrityVerifier::class)->verify()->status === CoreIntegrityResult::STATUS_INVALID) {
            Log::channel('install')->error('Install blocked: core integrity is INVALID (signature verification failed).');

            throw new CoreIntegrityBlockedException();
        }

        // SQLite ignores credentials; carrying them into .env would only
        // describe a server that is not there.
        if (($data['db_connection'] ?? null) === 'sqlite') {
            $dbPassword = '';
        }

        // Get force_ssl value
        $forceSslBool = ! empty($data['force_ssl']);

        // Determine APP_URL protocol based on force_ssl
        $protocol = $forceSslBool ? 'https://' : 'http://';
        $appUrl = $protocol.$data['app_url'];

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
        $existingSessionCookie = EnvFile::read('SESSION_COOKIE');

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
            'MAIL_PASSWORD' => $mailPassword,
            'MAIL_ENCRYPTION' => $data['mail_encryption'] ?? 'null',
            'MAIL_FROM_ADDRESS' => $data['mail_from_address'] ?? $data['admin_email'],
            'MAIL_FROM_NAME' => "\"{$data['site_name']}\"",

            // DB settings
            'DB_CONNECTION' => $data['db_connection'],
            'DB_HOST' => $data['db_host'],
            'DB_PORT' => $data['db_port'],
            'DB_DATABASE' => $data['db_database'],
            'DB_USERNAME' => $data['db_username'],
            'DB_PASSWORD' => $dbPassword,
        ];

        if ($existingSessionCookie !== '') {
            $envData['SESSION_COOKIE'] = $existingSessionCookie;
        }

        $this->report($progress, 'env');

        // Update .env file
        Log::channel('install')->info(__('http/controllers/install/install_confirm_controller.update_env_file_started'));
        EnvFile::update($envData);
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

        $this->report($progress, 'migrate');

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
    }

    /**
     * Hand a step name to the caller's progress callback, if it gave one.
     *
     * @param  (callable(string): void)|null  $progress
     */
    protected function report(?callable $progress, string $step): void
    {
        if ($progress !== null) {
            $progress($step);
        }
    }

    /**
     * Add initial data to database
     */
    protected function initializeDatabase(array $data, string $adminPassword)
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

        // The caller decides the locale; the wizard seeds it from the
        // session before handing the settings over.
        $installLocale = $data['app_locale'] ?? 'ja';
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
    protected function seedCoreReleaseState(int $adminMemberId): void
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
    protected function auditBundledThemes(): void
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

    /**
     * Dynamically register PSR-4 autoload for theme
     *
     * In environments where composer.local.json is not reflected (when Docker build is run with --no-scripts
     * and sync-local-autoload.php was not executed, etc.), enable theme
     * seeders etc. to be loaded during installation
     */
    protected function registerThemeAutoload(string $themeDirectory): void
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
}
