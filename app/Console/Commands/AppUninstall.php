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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact office@exc-d.com).
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

/**
 * This file is part of Your Software Name.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class AppUninstall extends Command
{
    /**
     * Command name and signature
     *
     * @var string
     */
    protected $signature = 'dls:app:uninstall {--force : Execute uninstallation immediately without interaction}';

    /**
     * Command description
     *
     * @var string
     */
    protected $description = 'Uninstall the application and delete environment configuration and database.';

    /**
     * Command execution process
     */
    public function handle()
    {
        $force = $this->option('force');

        if (! $force) {
            $this->warn(__('console/commands/app_uninstall.uninstall_warning_message'));
            if (! $this->confirm(__('console/commands/app_uninstall.confirm_uninstall'), false)) {
                $this->info(__('console/commands/app_uninstall.uninstall_cancelled'));

                return;
            }
        } else {
            $this->warn(__('console/commands/app_uninstall.force_option_specified'));
        }

        // ✅ 1. Database processing (priority)
        if (! $force && $this->confirm(__('console/commands/app_uninstall.confirm_database_backup'), true)) {
            $dbName = env('DB_DATABASE');
            $dumpFile = base_path("database/backups/{$dbName}_".now()->format('Ymd_His').'.sql');
            $this->dumpDatabase($dumpFile);
            $this->info(__('console/commands/app_uninstall.database_backed_up', ['dumpFile' => $dumpFile]));
        } elseif ($force) {
            $this->info(__('console/commands/app_uninstall.force_skip_database_backup'));
        }

        if (! $force && $this->confirm(__('console/commands/app_uninstall.confirm_delete_all_tables'))) {
            $this->dropAllTables();
            $this->info(__('console/commands/app_uninstall.all_tables_deleted'));
        } elseif ($force) {
            $this->dropAllTables();
            $this->info(__('console/commands/app_uninstall.all_tables_deleted'));
        }

        // ✅ 2. Delete symbolic links
        $this->removeSymlinks();

        // ✅ 3. Clear cache
        $this->clearCache();

        // ✅ 4. Process `.env` file (last)
        $envPath = base_path('.env');

        if (File::exists($envPath)) {
            $backupPath = base_path('.env.backup_'.now()->format('Ymd_His'));

            if (! $force && $this->confirm(__('console/commands/app_uninstall.confirm_env_backup'), true)) {
                try {
                    File::copy($envPath, $backupPath);
                    $this->info(__('console/commands/app_uninstall.env_backed_up', ['backupPath' => $backupPath]));
                } catch (\Exception $e) {
                    $this->error(__('console/commands/app_uninstall.backup_error').$e->getMessage());
                }
            } elseif ($force) {
                $this->info(__('console/commands/app_uninstall.force_skip_env_backup'));
            }

            // Delete .env file after uninstall (complete uninstall)
            $deleted = false;

            // Method 1: Laravel File::delete()
            try {
                $deleteResult = File::delete($envPath);
                if ($deleteResult && ! File::exists($envPath)) {
                    $deleted = true;
                }
            } catch (\Exception $e) {
                // Log if File::delete() fails
            }

            // Method 2: PHP unlink() (if File::delete() fails)
            if (! $deleted && File::exists($envPath)) {
                try {
                    $unlinkResult = unlink($envPath);
                    if ($unlinkResult && ! File::exists($envPath)) {
                        $deleted = true;
                    }
                } catch (\Exception $e) {
                    // If unlink() also fails
                }
            }

            if (! $deleted) {
                $this->error(__('console/commands/app_uninstall.env_deletion_failed', ['envPath' => $envPath]));
            } else {
                $this->info(__('console/commands/app_uninstall.env_file_deleted'));
                $this->info(__('console/commands/app_uninstall.env_creation_info'));
            }
        } else {
            $this->warn(__('console/commands/app_uninstall.env_file_not_found'));
        }

        // ✅ Uninstall complete
        $this->info(__('console/commands/app_uninstall.uninstall_completed'));
        $this->line('');
        $this->info(__('console/commands/app_uninstall.reinstall_notes'));
        $this->info(__('console/commands/app_uninstall.reinstall_cache_cleared'));
        $this->info(__('console/commands/app_uninstall.reinstall_no_additional_commands'));
    }

    /**
     * Create database dump
     */
    private function dumpDatabase(string $dumpFile)
    {
        try {
            $dbConnection = env('DB_CONNECTION');
            $dbHost = env('DB_HOST');
            $dbPort = env('DB_PORT');
            $dbDatabase = env('DB_DATABASE');
            $dbUsername = env('DB_USERNAME');
            $dbPassword = env('DB_PASSWORD');

            if (! File::exists(dirname($dumpFile))) {
                File::makeDirectory(dirname($dumpFile), 0755, true);
            }

            $command = match ($dbConnection) {
                'mysql' => "mysqldump -h {$dbHost} -P {$dbPort} -u {$dbUsername} --password={$dbPassword} {$dbDatabase} > {$dumpFile}",
                'pgsql' => "PGPASSWORD={$dbPassword} pg_dump -h {$dbHost} -p {$dbPort} -U {$dbUsername} -F c -b -v -f {$dumpFile} {$dbDatabase}",
                'sqlite' => "cp {$dbDatabase} {$dumpFile}",
                default => null,
            };

            if ($command) {
                exec($command, $output, $returnVar);
                if ($returnVar !== 0) {
                    throw new Exception('Failed to dump database');
                }
            } else {
                throw new Exception("Unsupported database driver: {$dbConnection}");
            }
        } catch (Exception $e) {
            $this->error($e->getMessage());
            Log::channel('install')->error('Error during database dump: '.$e->getMessage());
        }
    }

    /**
     * Delete all database tables
     */
    private function dropAllTables()
    {
        try {
            $dbConnection = DB::connection();
            $dbType = config('database.default');

            if ($dbType === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                $tables = DB::select('SHOW TABLES');
                foreach ($tables as $table) {
                    $tableName = reset($table);
                    // Wrap table names in backticks to handle table names with special characters like hyphens
                    DB::statement("DROP TABLE `{$tableName}`");
                }
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            } elseif ($dbType === 'pgsql') {
                $tables = DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");
                foreach ($tables as $table) {
                    // Wrap in double quotes for PostgreSQL
                    DB::statement("DROP TABLE IF EXISTS \"{$table->tablename}\" CASCADE");
                }
            } elseif ($dbType === 'sqlite') {
                // For SQLite, delete the database file itself
                $dbPath = database_path('database.sqlite');
                if (File::exists($dbPath)) {
                    File::delete($dbPath);
                    $this->info(__('console/commands/app_uninstall.sqlite_database_deleted', ['dbPath' => $dbPath]));
                }
            } else {
                throw new Exception("Unsupported database driver: {$dbType}");
            }
        } catch (Exception $e) {
            $this->error($e->getMessage());
            Log::channel('install')->error('Database error during uninstall: '.$e->getMessage());
        }
    }

    /**
     * Delete symbolic links for storage, theme, and plugin
     */
    private function removeSymlinks()
    {
        // ✅ Delete storage symbolic link
        $storageLink = public_path('storage');
        if (file_exists($storageLink) || is_link($storageLink)) {
            unlink($storageLink);
            $this->info(__('console/commands/app_uninstall.storage_symlink_removed'));
        }

        // ✅ Remove theme asset symbolic links
        $themeLink = public_path('assets/theme');
        if (file_exists($themeLink) || is_link($themeLink)) {
            unlink($themeLink);
            $this->info(__('console/commands/app_uninstall.theme_asset_symlinks_removed'));
        }

        // ✅ Remove plugin asset symbolic links
        $pluginsDir = public_path('assets/plugins');
        if (file_exists($pluginsDir) && is_dir($pluginsDir)) {
            $pluginLinks = scandir($pluginsDir);
            foreach ($pluginLinks as $pluginLink) {
                if ($pluginLink !== '.' && $pluginLink !== '..') {
                    $pluginPath = "{$pluginsDir}/{$pluginLink}";
                    if (file_exists($pluginPath) || is_link($pluginPath)) {
                        unlink($pluginPath);
                        $this->info(__('console/commands/app_uninstall.plugin_symlink_removed', ['pluginLink' => $pluginLink]));
                    }
                }
            }
        }
    }

    /**
     * Clear cache
     *
     * Note: Do not regenerate cache during uninstall (do not run config:cache)
     * This ensures the installation screen displays correctly on next access
     */
    private function clearCache()
    {
        // ✅ If cache driver is database, temporarily change to file
        $originalCacheDriver = config('cache.default');
        if ($originalCacheDriver === 'database') {
            config(['cache.default' => 'file']);
        }

        try {
            // Basic cache clearing (run config:clear first)
            Artisan::call('config:clear');
            $this->info(__('console/commands/app_uninstall.config_cache_cleared'));

            // cache:clear wrapped in try-catch since DB tables may have been deleted
            try {
                Artisan::call('cache:clear');
                $this->info(__('console/commands/app_uninstall.application_cache_cleared'));
            } catch (\Exception $e) {
                $this->warn(__('console/commands/app_uninstall.app_cache_skipped_no_tables'));
            }

            Artisan::call('view:clear');
            Artisan::call('route:clear');

            // Additional clear commands (for reinstallation issue prevention)
            Artisan::call('clear-compiled');

            // optimize:clear wrapped in try-catch since it may use DB cache
            try {
                Artisan::call('optimize:clear');
            } catch (\Exception $e) {
                $this->warn(__('console/commands/app_uninstall.optimize_clear_partially_skipped'));
            }

            $this->info(__('console/commands/app_uninstall.basic_cache_cleared'));

            // Regenerate Composer autoload
            $this->info(__('console/commands/app_uninstall.regenerating_composer_autoload'));
            $composerResult = shell_exec('composer dump-autoload 2>&1');
            if ($composerResult !== null) {
                $this->info(__('console/commands/app_uninstall.composer_autoload_regenerated'));
            } else {
                $this->warn(__('console/commands/app_uninstall.composer_autoload_skipped_not_found'));
            }

            // ⚠️ Do not run config:cache during uninstall
            // Regenerating cache will prevent the installation screen from displaying on next access
            $this->info(__('console/commands/app_uninstall.config_cache_regen_skipped_uninstall'));
        } catch (\Exception $e) {
            $this->error(__('console/commands/app_uninstall.clear_cache_error').$e->getMessage());
        }

        // ✅ Restore original cache driver
        config(['cache.default' => $originalCacheDriver]);
    }

    /**
     * Change session driver to file in .env file
     */
    private function updateEnvSessionDriver()
    {
        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            return;
        }

        try {
            $envContent = file_get_contents($envPath);

            // Change SESSION_DRIVER to file
            $envContent = preg_replace(
                '/^SESSION_DRIVER=.*$/m',
                'SESSION_DRIVER=file',
                $envContent
            );

            // Add SESSION_DRIVER if it does not exist
            if (! preg_match('/^SESSION_DRIVER=/m', $envContent)) {
                $envContent .= "\nSESSION_DRIVER=file\n";
            }

            file_put_contents($envPath, $envContent);
            $this->info(__('console/commands/app_uninstall.session_driver_changed_to_file'));
        } catch (\Exception $e) {
            $this->warn(__('console/commands/app_uninstall.session_driver_change_failed').$e->getMessage());
        }
    }

    /**
     * Check if .env file is complete
     */
    private function isEnvComplete(): bool
    {
        $requiredKeys = ['APP_KEY', 'DB_CONNECTION', 'DB_HOST', 'DB_DATABASE'];

        foreach ($requiredKeys as $key) {
            if (empty(env($key))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Update .env file values
     */
    private function updateEnvValue(string $key, string $value): void
    {
        $envPath = base_path('.env');

        if (! File::exists($envPath)) {
            return;
        }

        $envContent = File::get($envPath);

        // Update existing key or add if it does not exist
        if (preg_match("/^{$key}=.*$/m", $envContent)) {
            $envContent = preg_replace("/^{$key}=.*$/m", "{$key}={$value}", $envContent);
        } else {
            $envContent .= "\n{$key}={$value}";
        }

        File::put($envPath, $envContent);
    }
}
