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
use Illuminate\Support\Facades\File;
use App\Helpers\ComposerLocalHelper;
use App\Helpers\GitExcludeHelper;
use App\Helpers\GitIgnoreHelper;

class PluginDelete extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:plugin:delete {pluginDirectory : The directory name of the plugin to delete}
                            {--force : Force delete without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete plugin files and directories (plugin must be uninstalled first)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pluginDirectory = $this->argument('pluginDirectory');
        $pluginPath = base_path('plugins/' . $pluginDirectory);

        // プラグインディレクトリの存在チェック
        if (!File::exists($pluginPath)) {
            $this->error(__('command.plugin_delete.not_found', ['directory' => $pluginDirectory]));
            return 1;
        }

        // データベースに登録されているかチェック（アンインストール済みかどうか）
        $plugin = DB::table('plugins')->where('directory', $pluginDirectory)->first();
        
        if ($plugin) {
            $this->error(__('command.plugin_delete.still_installed', ['pluginName' => $plugin->name]));
            $this->warn(__('command.plugin_delete.uninstall_first'));
            return 1;
        }

        // 確認プロンプト
        if (!$this->option('force')) {
            if (!$this->confirm(__('command.plugin_delete.confirm', ['directory' => $pluginDirectory]), false)) {
                $this->info(__('command.plugin_delete.cancelled'));
                return 0;
            }
        }

        // ディレクトリを削除
        try {
            File::deleteDirectory($pluginPath);
            $this->info(__('command.plugin_delete.deleted', ['path' => $pluginPath]));
        } catch (\Exception $e) {
            $this->error(__('command.plugin_delete.failed', ['error' => $e->getMessage()]));
            return 1;
        }

        // .git/info/excludeからプラグインの除外ルールを削除
        if (GitExcludeHelper::removePluginExclusion($pluginDirectory)) {
            $this->info("✓ プラグイン '{$pluginDirectory}' を .git/info/exclude から削除しました");
        } else {
            $this->warn("⚠ プラグイン '{$pluginDirectory}' の .git/info/exclude からの削除に失敗しました（既に削除されている可能性があります）");
        }

        // .gitignoreからプラグインの除外ルールを削除
        if (GitIgnoreHelper::removePluginExclusion($pluginDirectory)) {
            $this->info("✓ プラグイン '{$pluginDirectory}' を .gitignore から削除しました");
        }

        // composer.local.jsonを更新
        ComposerLocalHelper::syncAutoload();
        $this->info("✓ composer.local.jsonを更新しました");

        // 注意: composer.local.jsonのみ更新し、composer.jsonは素の状態を保持
        // オートロードの反映は `composer dump-autoload` で手動実行

        $this->info(__('command.plugin_delete.completed', ['directory' => $pluginDirectory]));
        
        return 0;
    }
}
