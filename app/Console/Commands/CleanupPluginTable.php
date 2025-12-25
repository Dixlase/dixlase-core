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

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class CleanupPluginTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:admin:cleanup-plugin-table
                            {--plugin= : プラグインのslug（例: dixlase-blog）}
                            {--table= : クリーンアップ対象のテーブル名}
                            {--days=30 : 何日より古いレコードを削除するか}
                            {--date-column=created_at : 日付判定に使用するカラム}
                            {--force : 確認なしで実行}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'プラグインのテーブルをクリーンアップします';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $pluginSlug = $this->option('plugin');
        $tableName = $this->option('table');
        $days = (int) $this->option('days');
        $dateColumn = $this->option('date-column');
        $force = $this->option('force');

        // バリデーション
        if (empty($pluginSlug) || empty($tableName)) {
            $this->error(__('command.cleanup_plugin_table.missing_options'));
            return Command::FAILURE;
        }

        // テーブルの存在確認
        if (!Schema::hasTable($tableName)) {
            $this->error(__('command.cleanup_plugin_table.table_not_found', ['table' => $tableName]));
            return Command::FAILURE;
        }

        // カラムの存在確認
        if (!Schema::hasColumn($tableName, $dateColumn)) {
            $this->error(__('command.cleanup_plugin_table.column_not_found', [
                'column' => $dateColumn,
                'table' => $tableName
            ]));
            return Command::FAILURE;
        }

        // プラグインのcleanup設定を確認（セキュリティチェック）
        if (!$this->isTableAllowedForCleanup($pluginSlug, $tableName)) {
            $this->error(__('command.cleanup_plugin_table.table_not_allowed', [
                'table' => $tableName,
                'plugin' => $pluginSlug
            ]));
            return Command::FAILURE;
        }

        // 削除対象のレコード数を取得
        $cutoffDate = Carbon::now()->subDays($days);
        $count = DB::table($tableName)
            ->where($dateColumn, '<', $cutoffDate)
            ->count();

        if ($count === 0) {
            $this->info(__('command.cleanup_plugin_table.no_records'));
            $this->line("DELETED_COUNT: 0");
            return Command::SUCCESS;
        }

        // 確認
        if (!$force && !$this->confirm(__('command.cleanup_plugin_table.confirm', [
            'count' => $count,
            'table' => $tableName,
            'days' => $days
        ]))) {
            $this->info(__('command.cleanup_plugin_table.cancelled'));
            return Command::SUCCESS;
        }

        // 削除実行
        $deleted = DB::table($tableName)
            ->where($dateColumn, '<', $cutoffDate)
            ->delete();

        $this->info(__('command.cleanup_plugin_table.success', [
            'count' => $deleted,
            'table' => $tableName
        ]));
        $this->line("DELETED_COUNT: {$deleted}");

        return Command::SUCCESS;
    }

    /**
     * プラグインのplugin.jsonでクリーンアップが許可されているテーブルか確認
     */
    protected function isTableAllowedForCleanup(string $pluginSlug, string $tableName): bool
    {
        // プラグインディレクトリを取得
        $plugin = DB::table('plugins')
            ->where('slug', $pluginSlug)
            ->first();

        if (!$plugin) {
            return false;
        }

        $pluginJsonPath = base_path("plugins/{$plugin->directory}/plugin.json");

        if (!File::exists($pluginJsonPath)) {
            return false;
        }

        try {
            $pluginData = json_decode(File::get($pluginJsonPath), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return false;
            }

            // cleanup.tables配列をチェック
            $cleanupTables = $pluginData['cleanup']['tables'] ?? [];

            foreach ($cleanupTables as $tableConfig) {
                if (($tableConfig['name'] ?? '') === $tableName) {
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
}
