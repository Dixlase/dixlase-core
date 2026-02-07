<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
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

namespace App\Console\Commands;

use App\Services\DatabaseCleanupService;
use Illuminate\Console\Command;

class DatabaseCleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:cleanup 
                            {--type= : Cleanup type (e.g., login_attempts, two_fa_tokens, all, or plugin:slug:type)}
                            {--days=30 : Number of days to keep records}
                            {--all : Delete all records}
                            {--force : Force deletion without confirmation}
                            {--list : List all available cleanup types}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Unified database cleanup command for core and plugin tables';

    protected DatabaseCleanupService $cleanupService;

    /**
     * Create a new command instance.
     */
    public function __construct(DatabaseCleanupService $cleanupService)
    {
        parent::__construct();
        $this->cleanupService = $cleanupService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('list')) {
            return $this->listCleanupTypes();
        }

        $type = $this->option('type');
        
        if (!$type) {
            $this->error(__('admin/command/database-cleanup.type_required'));
            return 1;
        }

        $deleteAll = $this->option('all');
        $days = (int) $this->option('days');
        $force = $this->option('force');

        if ($deleteAll || $days === 0) {
            $days = 0;
            
            if (!$force) {
                if (app()->runningInConsole() && php_sapi_name() === 'cli') {
                    if (!$this->confirm(__('admin/command/database-cleanup.confirm_delete_all', ['type' => $type]))) {
                        $this->info(__('admin/command/database-cleanup.operation_cancelled'));
                        return 0;
                    }
                } else {
                    $this->error(__('admin/command/database-cleanup.force_required'));
                    return 1;
                }
            }
        }

        if ($days < 0) {
            $this->error(__('admin/command/database-cleanup.invalid_days'));
            return 1;
        }

        if ($type === 'all') {
            return $this->cleanupAll($days, $force);
        }

        return $this->cleanupType($type, $days, $force);
    }

    /**
     * 特定のタイプをクリーンアップ
     */
    protected function cleanupType(string $type, int $days, bool $force): int
    {
        $config = $this->cleanupService->getCleanupConfig($type);
        
        if (!$config) {
            $this->error(__('admin/command/database-cleanup.type_not_found', ['type' => $type]));
            return 1;
        }

        if ($days === 0) {
            $this->info(__('admin/command/database-cleanup.deleting_all', ['type' => $type]));
        } else {
            $this->info(__('admin/command/database-cleanup.cleaning_up', ['type' => $type, 'days' => $days]));
        }

        $result = $this->cleanupService->cleanup($type, $days, $force);

        if ($result['success']) {
            $this->info(__('admin/command/database-cleanup.deleted_success', ['count' => $result['count']]));
            $this->line("DELETED_COUNT: {$result['count']}");
            return 0;
        } else {
            $this->error(__('admin/command/database-cleanup.cleanup_failed', ['error' => $result['message']]));
            return 1;
        }
    }

    /**
     * すべてのテーブルをクリーンアップ
     */
    protected function cleanupAll(int $days, bool $force): int
    {
        $this->info(__('admin/command/database-cleanup.cleaning_up_all', ['days' => $days]));

        $result = $this->cleanupService->cleanupAll($days, $force);

        if ($result['success']) {
            $this->info(__('admin/command/database-cleanup.deleted_all_success', ['count' => $result['count']]));
            $this->line("DELETED_COUNT: {$result['count']}");
            
            if ($this->option('verbose')) {
                $this->table(
                    [__('admin/command/database-cleanup.table_type'), __('admin/command/database-cleanup.table_count')],
                    collect($result['details'])->map(fn($detail, $type) => [$type, $detail['count']])->toArray()
                );
            }
            
            return 0;
        } else {
            $this->error(__('admin/command/database-cleanup.cleanup_failed', ['error' => $result['message']]));
            return 1;
        }
    }

    /**
     * 利用可能なクリーンアップタイプを一覧表示
     */
    protected function listCleanupTypes(): int
    {
        $this->info(__('admin/command/database-cleanup.available_types'));
        $this->newLine();

        $this->line(__('admin/command/database-cleanup.core_tables'));
        $coreConfig = $this->cleanupService->getCoreCleanupConfig();
        
        foreach ($coreConfig as $type => $config) {
            $description = is_string($config['description']) && str_contains($config['description'], '.') 
                ? __($config['description']) 
                : $config['description'];
            $this->line("  - {$type} (table: {$config['table']}, default: {$config['default_days']} days)");
            $this->line("    {$description}");
        }

        $this->newLine();
        $this->line(__('admin/command/database-cleanup.plugin_tables'));
        $pluginConfig = $this->cleanupService->getPluginCleanupConfig();
        
        if (empty($pluginConfig)) {
            $this->line("  " . __('admin/command/database-cleanup.no_plugin_tables'));
        } else {
            foreach ($pluginConfig as $type => $config) {
                $description = is_string($config['description']) && str_contains($config['description'], '.') 
                    ? __($config['description']) 
                    : $config['description'];
                $this->line("  - {$type} (table: {$config['table']}, default: {$config['default_days']} days)");
                $this->line("    [{$config['plugin_name']}] {$description}");
            }
        }

        $this->newLine();
        $this->line(__('admin/command/database-cleanup.usage_examples'));
        $this->line("  php artisan dls:cleanup --type=login_attempts --days=30");
        $this->line("  php artisan dls:cleanup --type=all --days=30");
        $this->line("  php artisan dls:cleanup --type=plugin:inquiry:submissions --days=60");
        $this->line("  php artisan dls:cleanup --type=login_attempts --all --force");

        return 0;
    }
}
