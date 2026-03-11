<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

use App\Console\Traits\PluginManagementTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Services\PluginMigrator;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Providers\PluginServiceProvider;

class PluginUninstall extends Command
{

    use PluginManagementTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:plugin:uninstall {pluginName}
                            {--rollback : Rollback database migrations}
                            {--force : Force uninstall even if plugin is enabled}';
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->description = __('admin/command.plugin_uninstall.description');
    }

    /**
     * コマンド実行前の初期化
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);
        
        // アンインストール対象のプラグイン名を取得
        $pluginName = $input->getArgument('pluginName');
        if ($pluginName) {
            // 環境変数とconfigの両方でフラグを設定
            putenv("PLUGIN_UNINSTALLING={$pluginName}");
            config(['app.plugin_uninstalling' => $pluginName]);
            $this->info("DEBUG: Early flag set for plugin: {$pluginName}");
            $this->info("DEBUG: ENV variable: " . getenv('PLUGIN_UNINSTALLING'));
        }
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pluginName = $this->argument('pluginName');
        $plugin = DB::table('plugins')->where('name', $pluginName)->first();

        if (!$plugin) {
            $this->error(__('admin/command.plugin_uninstall.not_found', ['pluginName' => $pluginName]));
            return 1;
        }

        // 有効化状態チェック
        if (!is_null($plugin->enabled_at) && !$this->option('force')) {
            $this->error(__('admin/command.plugin_uninstall.still_enabled', ['pluginName' => $pluginName]));
            $this->warn(__('admin/command.plugin_uninstall.disable_first'));
            return 1;
        }

        $pluginPath = base_path('plugins/' . $plugin->directory);

        // アンインストール確認（--no-interactionオプションがない場合のみ）
        if (!$this->option('no-interaction')) {
            $this->warn(__('admin/command.plugin_uninstall.confirm', ['pluginName' => $pluginName]));
            $answer = $this->ask('yes/no を入力してください');
            
            if (!in_array(strtolower($answer), ['yes', 'y'])) {
                $this->info(__('admin/command.plugin_uninstall.cancelled'));
                return 0;
            }
        }

        // アンインストール処理中フラグを設定（ServiceProvider読み込みを防ぐ）
        config(['app.plugin_uninstalling' => $pluginName]);

        // --forceオプションが指定されている場合のみ無効化を実行
        if (!is_null($plugin->enabled_at) && $this->option('force')) {
            $this->warn(__('admin/command.plugin_uninstall.force_disabling', ['pluginName' => $pluginName]));
            $this->disablePlugin($pluginName);
            $plugin = DB::table('plugins')->where('name', $pluginName)->first();
        }
        // マイグレーションのロールバック
        if ($this->option('rollback')) {
            $this->info(__('admin/command.plugin_uninstall.rollback_running'));
            $migrator = new PluginMigrator(app(Filesystem::class), app(ConnectionResolverInterface::class), 'plugin_migrations', $plugin->slug);
            // 全てのマイグレーションをロールバックするため、stepを大きな値に設定
            $migrator->rollback($plugin->directory, ['step' => 999]);
        } else if (!$this->option('no-interaction') && $this->confirm(__('admin/command.plugin_uninstall.rollback_confirm', ['pluginName' => $pluginName]), false)) {
            $this->info(__('admin/command.plugin_uninstall.rollback_running'));
            $migrator = new PluginMigrator(app(Filesystem::class), app(ConnectionResolverInterface::class), 'plugin_migrations', $plugin->slug);
            // 全てのマイグレーションをロールバックするため、stepを大きな値に設定
            $migrator->rollback($plugin->directory, ['step' => 999]);
        } else {
            $this->info(__('admin/command.plugin_uninstall.rollback_skipped'));
        }

        // ディレクトリは削除しない（plugin:deleteコマンドを使用）
        $this->info(__('admin/command.plugin_uninstall.files_preserved'));

        // データベースからプラグインを削除
        DB::table('plugins')->where('name', $pluginName)->delete();
        $this->info(__('admin/command.plugin_uninstall.database_removed', ['pluginName' => $pluginName]));

        // 注意: composer.local.jsonと.git/info/excludeの更新は、
        // プラグイン削除時（plugin:delete）に行うため、ここでは不要

        // アンインストール処理中フラグをクリア
        putenv('PLUGIN_UNINSTALLING');
        config(['app.plugin_uninstalling' => null]);

        // Clear enabled plugins cache
        PluginServiceProvider::clearEnabledPluginsCache();

        $this->info(__('admin/command.plugin_uninstall.completed', ['pluginName' => $pluginName]));
        $this->info(__('admin/command.plugin_uninstall.delete_hint'));
        
        return 0;
    }
}
