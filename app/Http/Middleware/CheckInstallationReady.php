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

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckInstallationReady
{
    /**
     * Paths excluded from installation check
     */
    protected array $excludedPaths = [
        'csp-report',              // CSP violation report endpoint
        '_boost/*',                // MCP/Windsurf development tools
        'install/verify-mail/*',   // mail-reception verification during install
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check excluded paths (CSP reports, etc.)
        if ($this->isExcludedPath($request)) {
            return $next($request);
        }

        // Pre-check installation status (to minimize log output)
        $installed = $_SERVER['INSTALLED'] ?? $_ENV['INSTALLED'] ?? env('INSTALLED') ?? config('app.installed');
        $isInstalled = ($installed === 'true' || $installed === true);

        // Do not output logs after installation is complete

        // Switch session driver to file during installation
        if (! $isInstalled) {
            $this->ensureFileSessionDriver();
        }

        // Set language from session
        if (session()->has('install_locale')) {
            app()->setLocale(session('install_locale'));
        }

        $envPath = base_path('.env');
        $envExamplePath = base_path('.env.example');

        try {
            // If .env file does not exist, copy from .env.example to generate it
            if (! file_exists($envPath)) {
                if (file_exists($envExamplePath)) {
                    $copied = copy($envExamplePath, $envPath);
                    if ($copied) {
                        chmod($envPath, 0664);
                        Log::channel('install')->info('.env file was created from .env.example');
                    } else {
                        Log::error('Failed to copy .env.example to .env');
                        throw new \RuntimeException('Failed to create .env file');
                    }
                } else {
                    Log::error('.env.example file not found');
                    throw new \RuntimeException('.env.example file not found. Please create one.');
                }
            }

            // Get .env file contents
            $envContent = file_get_contents($envPath);
            if ($envContent === false) {
                Log::error('Failed to read .env file');
                throw new \RuntimeException('Failed to read .env file');
            }

            // Check if APP_KEY is set
            if (! preg_match('/^APP_KEY=(.+)$/m', $envContent, $matches) || empty(trim($matches[1] ?? ''))) {
                // Generate new APP_KEY
                $newKey = 'base64:'.base64_encode(random_bytes(32));

                // Update .env file
                $updatedContent = preg_replace(
                    '/^APP_KEY=.*$/m',
                    'APP_KEY='.$newKey,
                    $envContent,
                    -1,
                    $count
                );

                // Append if no match found
                if ($count === 0) {
                    $updatedContent .= "\nAPP_KEY=".$newKey."\n";
                }

                // Write to file
                $written = file_put_contents($envPath, $updatedContent, LOCK_EX);
                if ($written === false) {
                    Log::error('Failed to write to .env file');
                    throw new \RuntimeException('Failed to update APP_KEY in .env file');
                }

                Log::channel('install')->info('APP_KEY was generated and saved to .env');

                // Reflect settings to runtime
                config(['app.key' => $newKey]);
                if (function_exists('opcache_invalidate')) {
                    opcache_invalidate($envPath, true);
                }

                // Reload .env and redirect (to ensure encryption key is applied)
                return redirect($request->fullUrl());
            }
        } catch (\Exception $e) {
            Log::error('Environment setup error: '.$e->getMessage());
            throw $e;
        }

        // Check installation status (prioritize .env settings)
        // Already checked above, so reuse
        $currentRoute = $request->route() ? $request->route()->getName() : 'unknown';

        // Check migration completion (log output only when not installed)
        $debugInfo = [];
        $isMigrated = $this->checkMigrationCompleted($debugInfo, ! $isInstalled);

        // Do not output logs after installation is complete

        if (! $isInstalled) {
            // Handle uninstalled state

            if ($request->is('install*') || $request->is('install/*')) {
                // Access within install routes

                if ($request->is('install/complete')) {
                    // Access to completion screen
                    if (! $isMigrated) {
                        // Accessing completion screen when migration incomplete → redirect to initial screen
                        return redirect()->route('install.index');
                    }
                    // Allow completion screen display if migration is complete
                    if ($request->hasSession()) {
                        session(['install_process_completed' => true]);
                    }
                    // It's the completion screen itself, so let it pass through
                } elseif ($request->is('install/finalize') || $currentRoute === 'install.finalize') {
                    // Always allow finalize process (POST request)
                } else {
                    // Other installation flow (index, environment, settings, database, confirm)
                    if ($isMigrated) {
                        // Migration complete → redirect to completion screen
                        session(['install_process_completed' => true]);

                        return redirect()->route('install.complete');
                    }
                    // Allow installation flow to continue if migration is incomplete
                }
            } else {
                // Access to routes other than install (front page, etc.)

                if ($isMigrated) {
                    // Migration complete → to completion screen
                    // Only if not already redirecting to completion screen
                    // Check only when session is available
                    if ($request->hasSession() && ! $request->session()->has('_redirect_to_complete')) {
                        session(['install_process_completed' => true]);
                        $request->session()->put('_redirect_to_complete', true);

                        return redirect()->route('install.complete');
                    } elseif ($request->hasSession()) {
                        // Prevent redirect loop: pass through if already redirected
                    } else {
                        // Redirect if session is not available
                        return redirect()->route('install.complete');
                    }
                } else {
                    // Migration incomplete → to installation start screen
                    return redirect()->route('install.index');
                }
            }
        } else {
            // Prevent access to installation screen when already installed
            if ($request->is('install') || $request->is('install/*')) {
                return redirect('/')->with('message', __('http/middleware/check_installation_ready.app_already_installed'));
            }
        }

        return $next($request);
    }

    /**
     * Check if migration is complete
     *
     * Integrated version of proposal 1 (migrations table) + proposal 3 (administrator check)
     *
     * @param  array  $debugInfo  Array to store debug information (passed by reference)
     * @param  bool  $logToInstall  Whether to output to installation log (default: false)
     */
    private function checkMigrationCompleted(array &$debugInfo = [], bool $logToInstall = false): bool
    {
        try {
            // Step 1: Check database connection
            $dbName = DB::connection()->getDatabaseName();
            $debugInfo['step1_db_connection'] = $dbName ? "OK ({$dbName})" : 'NG';

            if (! $dbName) {
                if ($logToInstall) {
                    Log::channel('install')->info(__('http/middleware/check_installation_ready.check_install_no_db_connection'));
                }

                return false;
            }

            // Step 2: Check if migrations table exists (Laravel standard)
            // Note: migrations table may not exist if installed via direct SQL execution
            //    If it doesn't exist, skip and proceed to next check
            // Consider table prefix: dls_migrations or migrations
            $hasMigrationsTable = DB::getSchemaBuilder()->hasTable('migrations');
            $debugInfo['step2_migrations_table'] = $hasMigrationsTable ? 'OK' : __('http/middleware/check_installation_ready.skip_direct_sql_execution');

            if ($hasMigrationsTable) {
                // Step 3: Check number of migration execution records
                try {
                    $migrationCount = DB::table('migrations')->count();
                    $minRequiredMigrations = 15;
                    $debugInfo['step3_migration_count'] = __('http/middleware/check_installation_ready.migration_count_required', ['migrationCount' => $migrationCount, 'minRequiredMigrations' => $minRequiredMigrations]);

                    if ($migrationCount < $minRequiredMigrations) {
                        return false;
                    }
                } catch (\Exception $e) {
                    // Skip if error occurs due to table name issues, etc.
                    $debugInfo['step3_migration_count'] = 'ERROR: '.$e->getMessage();
                }
            } else {
                $debugInfo['step3_migration_count'] = __('http/middleware/check_installation_ready.skip_migrations_table_not_found');
            }

            // Step 4: Check existence of main tables (just in case)
            $requiredTables = ['members', 'site_settings', 'themes'];
            $tableStatus = [];

            foreach ($requiredTables as $table) {
                $exists = DB::getSchemaBuilder()->hasTable($table);
                $tableStatus[$table] = $exists ? 'OK' : 'NG';

                if (! $exists) {
                    if ($logToInstall) {
                        Log::channel('install')->debug("CheckInstallationReady: main table '{$table}' does not exist");
                    }
                    $debugInfo['step4_tables'] = $tableStatus;

                    return false;
                }
            }
            $debugInfo['step4_tables'] = $tableStatus;

            // Step 5: Check if administrator user exists (evidence of initial data seeding)
            // role=10: SUPER_ADMIN, role=9: ADMIN
            $adminCount = DB::table('members')
                ->whereIn('role', [9, 10])
                ->count();
            $debugInfo['step5_admin_users'] = __('http/middleware/check_installation_ready.admin_count', ['adminCount' => $adminCount]);

            if ($adminCount === 0) {
                if ($logToInstall) {
                    Log::channel('install')->info(__('http/middleware/check_installation_ready.check_install_no_admin_user'));
                }

                return false;
            }

            if ($logToInstall) {
                Log::channel('install')->info(__('http/middleware/check_installation_ready.check_install_admin_user_check'));
            }

            // Step 6: Check if basic data exists in site_settings for
            // primary site (id=1) (additional confirmation). For multi-site support,
            // query while filtering by site_id
            $hasSiteName = DB::table('site_settings')
                ->where('site_id', 1)
                ->where('name', 'site_name')
                ->exists();
            $debugInfo['step6_site_name'] = $hasSiteName ? 'OK' : 'NG';

            if (! $hasSiteName) {
                if ($logToInstall) {
                    Log::channel('install')->info(__('http/middleware/check_installation_ready.check_install_no_site_settings_data'));
                }

                return false;
            }

            $debugInfo['result'] = '✅ ALL PASSED';
            if ($logToInstall) {
                Log::channel('install')->info(__('http/middleware/check_installation_ready.check_install_migration_completed'));
            }

            return true;
        } catch (\Exception $e) {
            $debugInfo['error'] = $e->getMessage();
            if ($logToInstall) {
                Log::channel('install')->info(__('http/middleware/check_installation_ready.check_install_migration_check_error').$e->getMessage());
            }

            return false;
        }
    }

    /**
     * Switch session driver to file during installation
     * Using guard-aware-database driver when DB tables don't exist yet will cause an error
     */
    private function ensureFileSessionDriver(): void
    {
        $currentDriver = config('session.driver');

        // Do nothing if already using file driver
        if ($currentDriver === 'file') {
            return;
        }

        // Switch to file if using database driver
        if (in_array($currentDriver, ['database', 'guard-aware-database'])) {
            config(['session.driver' => 'file']);

            // Rebind session manager
            app()->forgetInstance('session');
            app()->forgetInstance('session.store');

            Log::channel('install')->info(__('http/middleware/check_installation_ready.check_install_session_driver_switched'), [
                'original_driver' => $currentDriver,
            ]);
        }
    }

    /**
     * Check if path is excluded
     */
    protected function isExcludedPath(Request $request): bool
    {
        $path = $request->path();

        foreach ($this->excludedPaths as $pattern) {
            // Check wildcard pattern
            if (str_contains($pattern, '*')) {
                $regex = str_replace(['*', '/'], ['.*', '\/'], $pattern);
                if (preg_match("/^{$regex}$/", $path)) {
                    return true;
                }
            } elseif ($path === $pattern) {
                return true;
            }
        }

        return false;
    }
}
