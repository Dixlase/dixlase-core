<?php

namespace App\Console\Commands;

use App\Console\Traits\PluginManagementTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Services\PluginMigrator;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Filesystem\Filesystem;
use App\Helpers\PluginGitignoreHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
            $this->error(__('command.plugin_uninstall.not_found', ['pluginName' => $pluginName]));
            return;
        }

        $pluginPath = base_path('plugins/' . $plugin->directory);

        // アンインストール処理中フラグを設定（ServiceProvider読み込みを防ぐ）
        config(['app.plugin_uninstalling' => $pluginName]);
        $this->info("DEBUG: Set uninstalling flag for plugin: {$pluginName}");
        $this->info("DEBUG: Current config value: " . config('app.plugin_uninstalling'));

        // プラグインの無効化（最初に実行してServiceProviderの読み込みを防ぐ）
        $this->info("DEBUG: Disabling plugin: {$pluginName}");
        $this->disablePlugin($pluginName);
        
        // 無効化後の状態を確認
        $updatedPlugin = DB::table('plugins')->where('name', $pluginName)->first();
        $this->info("DEBUG: Plugin status after disable: " . ($updatedPlugin ? $updatedPlugin->status : 'NOT FOUND'));
        
        // .gitignore除外リストからプラグインを削除（早期実行）
        // ディレクトリ名を使用（プラグイン名ではなく）
        if (PluginGitignoreHelper::removePlugin($plugin->directory)) {
            $this->info("✓ プラグイン '{$plugin->directory}' を .gitignore の除外リストから削除しました");
        } else {
            $this->warn("⚠ プラグイン '{$plugin->directory}' の .gitignore からの削除に失敗しました");
        }

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

        // アンインストール処理中フラグをクリア
        putenv('PLUGIN_UNINSTALLING');
        config(['app.plugin_uninstalling' => null]);

        $this->info(__('command.plugin_uninstall.completed', ['pluginName' => $pluginName]));
    }
}
