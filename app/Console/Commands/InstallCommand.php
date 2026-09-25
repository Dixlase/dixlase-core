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

namespace App\Console\Commands;

use App\Exceptions\Install\CoreIntegrityBlockedException;
use App\Exceptions\Install\SqliteDatabaseUnusableException;
use App\Services\Install\InstallFinalizer;
use App\Services\Install\InstallRunner;
use App\Support\Install\EnvFile;
use App\Support\Install\InstallRunLock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * @internal For Core use only. Do not reference from plugins/themes
 *
 * Installs Dixlase without the browser wizard.
 *
 * The wizard needs a web server, and the one PHP ships with restarts on
 * every .env write — which is exactly what an installation does. This
 * command runs the same pipeline (InstallRunner) from the shell, so the
 * one-liner installer can finish without asking anyone to open a page.
 *
 * Every setting can be given as an option; anything missing is asked
 * for, unless --no-interaction, where a missing required setting is an
 * error rather than a prompt.
 */
class InstallCommand extends Command
{
    protected $signature = 'dls:install
                            {--site-name= : Site name}
                            {--admin-name= : Administrator account name (letters and digits, 3-20)}
                            {--admin-display-name= : Administrator display name}
                            {--admin-email= : Administrator email address}
                            {--admin-password= : Administrator password (or set DIXLASE_ADMIN_PASSWORD)}
                            {--url= : Site host, with or without scheme (example.com, example.com:8000)}
                            {--admin-url= : Admin panel path segment (default: a generated one)}
                            {--app-env=production : APP_ENV (local, staging, production)}
                            {--timezone=UTC : Display timezone}
                            {--locale=en : Admin language (en, ja)}
                            {--force-ssl : Serve the site over https}
                            {--db=sqlite : Database driver (sqlite, mysql)}
                            {--db-host=127.0.0.1 : Database host (ignored for sqlite)}
                            {--db-port=3306 : Database port (ignored for sqlite)}
                            {--db-database= : Database name, or the sqlite file path}
                            {--db-username= : Database user (ignored for sqlite)}
                            {--db-password= : Database password (or set DIXLASE_DB_PASSWORD)}
                            {--preserve-data : Migrate without dropping existing tables}
                            {--mail-mailer= : MAIL_MAILER}
                            {--mail-host= : MAIL_HOST}
                            {--mail-port= : MAIL_PORT}
                            {--mail-username= : MAIL_USERNAME}
                            {--mail-password= : MAIL_PASSWORD (or set DIXLASE_MAIL_PASSWORD)}
                            {--mail-encryption= : MAIL_ENCRYPTION}
                            {--mail-from= : MAIL_FROM_ADDRESS (default: the administrator address)}
                            {--force : Do not ask for confirmation before writing}';

    protected $description = 'Install Dixlase from the command line (no browser wizard)';

    public function handle(InstallRunner $runner, InstallFinalizer $finalizer): int
    {
        $this->line('Dixlase installer');

        if (! $this->ensureEnvFile()) {
            return self::FAILURE;
        }

        if (! $this->guardAgainstExistingInstall()) {
            return self::FAILURE;
        }

        if (! InstallRunLock::acquire()) {
            $this->error('An installation is already running. Wait for it to finish, or remove '.InstallRunLock::path().' if that run died.');

            return self::FAILURE;
        }

        try {
            $data = $this->collect();

            if ($data === null) {
                return self::FAILURE;
            }

            $adminPassword = (string) $data['_admin_password'];
            $dbPassword = (string) $data['_db_password'];
            $mailPassword = (string) $data['_mail_password'];
            unset($data['_admin_password'], $data['_db_password'], $data['_mail_password']);

            if (! $this->confirmDestructiveRun($data)) {
                $this->warn('Cancelled. Nothing was written.');

                return self::FAILURE;
            }

            try {
                $data = $runner->prepare($data);
            } catch (SqliteDatabaseUnusableException $e) {
                $this->error('The SQLite database file could not be created or written: '.$e->path);
                $this->line('Check that the directory exists and is writable, then run the command again.');

                return self::FAILURE;
            }

            $this->line('');
            $runner->run($data, $adminPassword, $dbPassword, $mailPassword, function (string $step): void {
                $this->line(match ($step) {
                    'env' => '  writing .env',
                    'migrate' => '  running migrations',
                    'seed' => '  seeding',
                    default => '  '.$step,
                });
            });

            $finalizer->finalize();

            $this->report($data);

            return self::SUCCESS;
        } catch (CoreIntegrityBlockedException $e) {
            $this->error('Installation blocked: this core carries a signature that does not verify.');

            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->error('Installation failed: '.$e->getMessage());
            $this->line('See storage/logs/install.log for the full trace.');

            return self::FAILURE;
        } finally {
            InstallRunLock::release();
        }
    }

    /**
     * Make sure there is a .env with an application key.
     *
     * The web wizard gets this from CheckInstallationReady, which copies
     * .env.example on the first request and then redirects so the new key
     * is picked up. There is no request here, so do it directly.
     */
    protected function ensureEnvFile(): bool
    {
        $envPath = base_path('.env');

        if (! File::exists($envPath)) {
            if (! File::exists(base_path('.env.example'))) {
                $this->error('Neither .env nor .env.example is present; cannot continue.');

                return false;
            }

            File::copy(base_path('.env.example'), $envPath);
            $this->line('  created .env from .env.example');
        }

        if (EnvFile::read('APP_KEY') === '') {
            Artisan::call('key:generate', ['--force' => true]);
            $this->line('  generated APP_KEY');
        }

        return true;
    }

    /**
     * Refuse to run over an installation that is already complete.
     */
    protected function guardAgainstExistingInstall(): bool
    {
        // Same expression the middleware uses, so a value already applied
        // to this process counts even when .env has not caught up.
        $installed = $_SERVER['INSTALLED'] ?? $_ENV['INSTALLED'] ?? config('app.installed');

        if (! ($installed === 'true' || $installed === true)) {
            return true;
        }

        $this->error('This installation is already complete (.env has INSTALLED=true).');
        $this->line('To install again, use `php artisan dls:app:uninstall` first, or edit .env by hand if you know what you are doing.');

        return false;
    }

    /**
     * Gather every setting, prompting for what is missing.
     *
     * Returns null when a required setting is missing and prompting is
     * not allowed, or when validation fails.
     *
     * @return array<string, mixed>|null
     */
    protected function collect(): ?array
    {
        $interactive = $this->input->isInteractive() && ! $this->option('no-interaction');

        $siteName = $this->need('site-name', 'Site name', $interactive);
        $adminName = $this->need('admin-name', 'Administrator account name', $interactive, 'admin');
        $adminEmail = $this->need('admin-email', 'Administrator email', $interactive);
        $adminPassword = (string) ($this->option('admin-password') ?: self::fromEnvironment('DIXLASE_ADMIN_PASSWORD'));
        if ($adminPassword === '' && $interactive) {
            $adminPassword = (string) $this->secret('Administrator password');
        }
        $url = $this->need('url', 'Site URL (host, e.g. example.com:8000)', $interactive);

        if ($siteName === null || $adminName === null || $adminEmail === null || $url === null || $adminPassword === '') {
            $this->error('Missing required settings. Pass them as options (--site-name, --admin-name, --admin-email, --admin-password, --url) or run without --no-interaction.');

            return null;
        }

        $driver = (string) $this->option('db');
        $database = (string) ($this->option('db-database') ?: ($driver === 'sqlite' ? database_path('database.sqlite') : 'dixlase'));
        $dbPassword = (string) ($this->option('db-password') ?: self::fromEnvironment('DIXLASE_DB_PASSWORD'));
        $mailPassword = (string) ($this->option('mail-password') ?: self::fromEnvironment('DIXLASE_MAIL_PASSWORD'));

        // Strip the scheme the way the wizard's environment step does, and
        // let --force-ssl decide the protocol instead.
        $host = preg_replace('#^https?://#', '', trim($url));
        $forceSsl = (bool) $this->option('force-ssl');

        $data = [
            'install_mode' => 1,
            'site_name' => $siteName,
            'admin_account_name' => $adminName,
            'admin_display_name' => $this->option('admin-display-name') ?: $adminName,
            'admin_email' => $adminEmail,
            'app_env' => (string) $this->option('app-env'),
            'app_debug' => false,
            'app_url' => $host,
            'app_timezone' => (string) $this->option('timezone'),
            'app_locale' => (string) $this->option('locale'),
            'admin_url' => (string) ($this->option('admin-url') ?: 'admin-'.Str::lower(Str::random(8))),
            'force_ssl' => $forceSsl,
            'db_connection' => $driver,
            'db_host' => $driver === 'sqlite' ? '' : (string) $this->option('db-host'),
            'db_port' => $driver === 'sqlite' ? '' : (string) $this->option('db-port'),
            'db_database' => $database,
            'db_username' => $driver === 'sqlite' ? '' : (string) $this->option('db-username'),
            'preserve_data' => (bool) $this->option('preserve-data'),
            'mail_mailer' => $this->option('mail-mailer') ?: 'log',
            'mail_host' => $this->option('mail-host') ?: 'localhost',
            'mail_port' => $this->option('mail-port') ?: 1025,
            'mail_username' => $this->option('mail-username') ?: '',
            'mail_encryption' => $this->option('mail-encryption') ?: 'null',
            'mail_from_address' => $this->option('mail-from') ?: $adminEmail,
            // The settings table keeps this encrypted, the same as the wizard.
            'mail_password' => $mailPassword !== '' ? Crypt::encryptString($mailPassword) : '',
        ];

        if (! $this->validate($data, $adminPassword)) {
            return null;
        }

        $data['_admin_password'] = $adminPassword;
        $data['_db_password'] = $dbPassword;
        $data['_mail_password'] = $mailPassword;

        return $data;
    }

    /**
     * A password handed over through the process environment.
     *
     * Read straight from the process rather than through `env()`, which
     * returns null once the config is cached — and an installer is
     * exactly the situation where a stale cache is likely.
     */
    protected static function fromEnvironment(string $name): string
    {
        $value = getenv($name);

        if ($value === false) {
            $value = $_SERVER[$name] ?? $_ENV[$name] ?? '';
        }

        return is_string($value) ? $value : '';
    }

    /**
     * An option's value, asked for when absent and prompting is allowed.
     */
    protected function need(string $option, string $question, bool $interactive, ?string $default = null): ?string
    {
        $value = (string) ($this->option($option) ?? '');

        if ($value !== '') {
            return $value;
        }

        if (! $interactive) {
            return null;
        }

        $answer = (string) $this->ask($question, $default);

        return $answer !== '' ? $answer : null;
    }

    /**
     * Apply the wizard's own rules before anything is written.
     *
     * Mirrors the install FormRequests; they read the session directly, so
     * they cannot be reused as they stand.
     *
     * @param  array<string, mixed>  $data
     */
    protected function validate(array $data, string $adminPassword): bool
    {
        $drivers = array_values(array_filter([
            extension_loaded('pdo_mysql') ? 'mysql' : null,
            extension_loaded('pdo_sqlite') ? 'sqlite' : null,
        ]));

        $isSqlite = $data['db_connection'] === 'sqlite';

        $validator = Validator::make(
            $data + ['admin_password' => $adminPassword],
            [
                'site_name' => 'required|string|max:60',
                'admin_account_name' => ['required', 'string', 'alpha_num', 'min:3', 'max:20'],
                'admin_display_name' => 'nullable|string|max:255',
                'admin_email' => 'required|email',
                'admin_password' => ['required', 'string', 'min:8', 'regex:/[A-Z]/', 'regex:/[a-z]/', 'regex:/[0-9]/'],
                'app_env' => 'required|in:local,staging,production',
                'app_url' => ['required', 'string', 'regex:/^[\w.\-]+(:\d+)?$/'],
                'app_timezone' => 'required|timezone',
                'app_locale' => 'required|string',
                'admin_url' => ['required', 'string', 'regex:/^[a-z0-9\-]+$/'],
                'db_connection' => ['required', 'string', Rule::in($drivers)],
                'db_host' => $isSqlite ? 'nullable|string' : 'required|string',
                'db_port' => $isSqlite ? 'nullable' : 'required|integer',
                'db_database' => 'required|string',
                'db_username' => $isSqlite ? 'nullable|string' : 'required|string',
                'mail_from_address' => 'nullable|email',
            ]
        );

        if ($validator->fails()) {
            $this->error('These settings are not usable:');
            foreach ($validator->errors()->all() as $message) {
                $this->line('  - '.$message);
            }

            return false;
        }

        return true;
    }

    /**
     * Say what is about to happen, and get an answer before writing.
     *
     * @param  array<string, mixed>  $data
     */
    protected function confirmDestructiveRun(array $data): bool
    {
        $this->line('');
        $this->line('  site        '.$data['site_name']);
        $this->line('  url         '.($data['force_ssl'] ? 'https://' : 'http://').$data['app_url']);
        $this->line('  admin panel /'.$data['admin_url']);
        $this->line('  admin user  '.$data['admin_account_name'].' <'.$data['admin_email'].'>');
        $this->line('  database    '.$data['db_connection'].' — '.$data['db_database']);
        $this->line('');

        if ($data['preserve_data']) {
            $this->line('Existing tables are kept (--preserve-data): migrations run without dropping anything.');
        } else {
            $this->warn('Every table in this database will be DROPPED and recreated.');
        }

        if ($this->option('force')) {
            return true;
        }

        if (! $this->input->isInteractive() || $this->option('no-interaction')) {
            $this->error('Refusing to write without confirmation. Re-run with --force once the settings above are right.');

            return false;
        }

        return $this->confirm('Install with these settings?', false);
    }

    /**
     * Print where the site now lives.
     *
     * @param  array<string, mixed>  $data
     */
    protected function report(array $data): void
    {
        $base = ($data['force_ssl'] ? 'https://' : 'http://').$data['app_url'];

        $this->line('');
        $this->info('Installation complete.');
        $this->line('  site   '.$base);
        $this->line('  admin  '.$base.'/'.$data['admin_url']);
        $this->line('  login  '.$data['admin_email']);
        $this->line('');
        $this->line('The admin path is in .env as ADMIN_URL-equivalent site settings; keep the URL above.');
    }
}
