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

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckInstallStatus extends Command
{
    protected $signature = 'dls:install:check';

    protected $description = 'Check installation status and migration state';

    public function handle()
    {
        $this->info('========================================');
        $this->info(__('console/commands/check_install_status.installation_status_check'));
        $this->info('========================================');
        $this->newLine();

        // 1. INSTALLED status
        $installed = env('INSTALLED');
        $this->info('1. INSTALLED: '.var_export($installed, true));
        $this->line(__('console/commands/check_install_status.type_label').gettype($installed));
        $this->line(__('console/commands/check_install_status.result_label').(($installed === 'true' || $installed === true) ? __('console/commands/check_install_status.installed') : __('console/commands/check_install_status.not_installed')));
        $this->newLine();

        // 2. Database connection
        try {
            $dbName = DB::connection()->getDatabaseName();
            $this->info('2. Database connection: ✅ OK');
            $this->line(__('console/commands/check_install_status.database_name_display', ['dbName' => $dbName]));
        } catch (\Exception $e) {
            $this->error('2. Database connection: ❌ NG');
            $this->line(__('console/commands/check_install_status.error_label').$e->getMessage());

            return 1;
        }
        $this->newLine();

        // 3. migrations table
        try {
            $hasMigrationsTable = DB::getSchemaBuilder()->hasTable('migrations');
            $this->info('3. migrations table: '.($hasMigrationsTable ? __('console/commands/check_install_status.exists') : __('console/commands/check_install_status.not_found')));

            if ($hasMigrationsTable) {
                $migrationCount = DB::table('migrations')->count();
                $this->line(__('console/commands/check_install_status.executed_migrations_count', ['migrationCount' => $migrationCount]));

                // Display the 5 most recent migrations
                $recentMigrations = DB::table('migrations')
                    ->orderBy('id', 'desc')
                    ->limit(5)
                    ->pluck('migration');

                $this->line(__('console/commands/check_install_status.recent_migrations'));
                foreach ($recentMigrations as $migration) {
                    $this->line("   - {$migration}");
                }
            }
        } catch (\Exception $e) {
            $this->error(__('console/commands/check_install_status.migrations_table_check_error').$e->getMessage());
        }
        $this->newLine();

        // 4. Main tables
        $this->info('4. Main tables:');
        $requiredTables = ['members', 'site_settings', 'themes', 'theme_settings', 'members_roles'];

        foreach ($requiredTables as $table) {
            try {
                $exists = DB::getSchemaBuilder()->hasTable($table);
                if ($exists) {
                    $count = DB::table($table)->count();
                    $this->line(__('console/commands/check_install_status.table_exists_with_records', ['table' => $table, 'count' => $count]));
                } else {
                    $this->line(__('console/commands/check_install_status.table_not_found', ['table' => $table]));
                }
            } catch (\Exception $e) {
                $this->error(__('console/commands/check_install_status.table_error', ['table' => $table]).$e->getMessage());
            }
        }
        $this->newLine();

        // 5. Administrator user
        try {
            if (DB::getSchemaBuilder()->hasTable('members')) {
                // role=10: SUPER_ADMIN, role=9: ADMIN, role=1: GUEST
                $adminCount = DB::table('members')->whereIn('role', [9, 10])->count();
                $this->info(__('console/commands/check_install_status.administrator_user_count', ['adminCount' => $adminCount]));

                if ($adminCount > 0) {
                    $admins = DB::table('members')
                        ->whereIn('role', [9, 10])
                        ->select('id', 'name', 'email', 'role')
                        ->get();

                    foreach ($admins as $admin) {
                        $roleLabel = $admin->role == 10 ? 'SUPER_ADMIN' : 'ADMIN';
                        $this->line("   - [{$admin->id}] {$admin->name} ({$admin->email}) [role={$admin->role}:{$roleLabel}]");
                    }
                } else {
                    $this->warn(__('console/commands/check_install_status.admin_user_not_exist'));
                }
            }
        } catch (\Exception $e) {
            $this->error(__('console/commands/check_install_status.admin_user_check_error').$e->getMessage());
        }
        $this->newLine();

        // 6. site_settings (primary site, site_id=1)
        try {
            if (DB::getSchemaBuilder()->hasTable('site_settings')) {
                $hasSiteName = DB::table('site_settings')
                    ->where('site_id', 1)
                    ->where('name', 'site_name')
                    ->exists();

                $this->info('6. site_settings (site_name @ primary site): '.($hasSiteName ? __('console/commands/check_install_status.exists') : __('console/commands/check_install_status.not_found')));

                if ($hasSiteName) {
                    $siteName = DB::table('site_settings')
                        ->where('site_id', 1)
                        ->where('name', 'site_name')
                        ->value('value');
                    $this->line(__('console/commands/check_install_status.site_name_display', ['siteName' => $siteName]));
                }
            }
        } catch (\Throwable $e) {
            $this->error(__('console/commands/check_install_status.site_settings_check_error').$e->getMessage());
        }
        $this->newLine();

        // 7. Overall determination
        $this->info('========================================');
        $this->info(__('console/commands/check_install_status.overall_result'));
        $this->info('========================================');

        try {
            $isMigrationComplete = $this->checkMigrationCompleted();

            if ($isMigrationComplete) {
                $this->info(__('console/commands/check_install_status.migration_completed'));

                if ($installed === 'true' || $installed === true) {
                    $this->info(__('console/commands/check_install_status.installation_completed'));
                    $this->line('');
                    $this->info(__('console/commands/check_install_status.system_successfully_installed'));
                } else {
                    $this->warn(__('console/commands/check_install_status.installed_is_false'));
                    $this->line('');
                    $this->info(__('console/commands/check_install_status.access_front_page_for_completion'));
                    $this->info(__('console/commands/check_install_status.press_button_to_set_installed_true'));
                }
            } else {
                $this->error(__('console/commands/check_install_status.migration_not_completed'));
                $this->line('');
                $this->warn(__('console/commands/check_install_status.run_installation_process'));
            }
        } catch (\Exception $e) {
            $this->error(__('console/commands/check_install_status.determination_error').$e->getMessage());
        }

        return 0;
    }

    private function checkMigrationCompleted(): bool
    {
        try {
            // Basic check
            if (! DB::connection()->getDatabaseName()) {
                return false;
            }

            // Skip if migrations table does not exist (when SQL is executed directly)
            if (DB::getSchemaBuilder()->hasTable('migrations')) {
                $migrationCount = DB::table('migrations')->count();
                if ($migrationCount < 15) {
                    return false;
                }
            }

            if (! DB::getSchemaBuilder()->hasTable('members')) {
                return false;
            }
            if (! DB::getSchemaBuilder()->hasTable('site_settings')) {
                return false;
            }

            // role=10: SUPER_ADMIN, role=9: ADMIN
            $adminCount = DB::table('members')->whereIn('role', [9, 10])->count();
            if ($adminCount === 0) {
                return false;
            }

            return DB::table('site_settings')
                ->where('site_id', 1)
                ->where('name', 'site_name')
                ->exists();
        } catch (\Exception $e) {
            return false;
        }
    }
}
