<?php

namespace App\Console\Commands;

use App\Console\Traits\PluginManagementTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Services\PluginMigrator;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Filesystem\Filesystem;

class PluginUninstall extends Command
{

    use PluginManagementTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plugin:uninstall {pluginName}
                            {--rollback : Rollback database migrations}
                            {--delete : Delete plugin files and directories}';
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->description = __('command.plugin_uninstall.description');
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pluginName = $this->argument('pluginName');
        $plugin = DB::table('plugins')->where('name', $pluginName)->first();

        if (!$plugin) {
            $this->error(__('command.plugin_uninstall.not_found', ['pluginName' => $pluginName]));
            return;
        }

        $pluginPath = base_path('plugins/' . $plugin->directory);

        // プラグインの無効化
        $this->disablePlugin($pluginName);

        // マイグレーションのロールバック
        if ($this->option('rollback')) {
            $this->info(__('command.plugin_uninstall.rollback_running'));
            $migrator = new PluginMigrator(app(Filesystem::class), app(ConnectionResolverInterface::class), 'plugin_migrations', $plugin->slug);
            $migrator->rollback($plugin->directory);
        } else if ($this->confirm(__('command.plugin_uninstall.rollback_confirm', ['pluginName' => $pluginName]), false)) {
            $this->info(__('command.plugin_uninstall.rollback_running'));
            $migrator = new PluginMigrator(app(Filesystem::class), app(ConnectionResolverInterface::class), 'plugin_migrations', $plugin->slug);
            $migrator->rollback($plugin->directory);
        } else {
            $this->info(__('command.plugin_uninstall.rollback_skipped'));
        }

        // プラグインのディレクトリを削除するか？
        if ($this->option('delete')) {
            if (File::exists($pluginPath)) {
                File::deleteDirectory($pluginPath);
                $this->info(__('command.plugin_uninstall.directory_deleted', ['path' => $pluginPath]));
            } else {
                $this->info(__('command.plugin_uninstall.directory_not_exists'));
            }
        } else if ($this->confirm(__('command.plugin_uninstall.delete_confirm', ['pluginName' => $pluginName]), false)) {
            if (File::exists($pluginPath)) {
                File::deleteDirectory($pluginPath);
                $this->info(__('command.plugin_uninstall.directory_deleted', ['path' => $pluginPath]));
            } else {
                $this->info(__('command.plugin_uninstall.directory_not_exists'));
            }
        } else {
            $this->info(__('command.plugin_uninstall.directory_not_deleted'));
        }

        // データベースからプラグインを削除
        DB::table('plugins')->where('name', $pluginName)->delete();
        $this->info(__('command.plugin_uninstall.database_removed', ['pluginName' => $pluginName]));

        // オートロードを更新
        $this->updateAutoload();

        $this->info(__('command.plugin_uninstall.completed', ['pluginName' => $pluginName]));
    }
}
