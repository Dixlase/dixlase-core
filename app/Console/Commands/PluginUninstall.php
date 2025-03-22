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
    protected $signature = 'plugin:uninstall {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'プラグインをアンインストールし、データベースとファイルを削除します。';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pluginName = $this->argument('name');
        $plugin = DB::table('plugins')->where('name', $pluginName)->first();

        if (!$plugin) {
            $this->error("プラグイン '{$pluginName}' は見つかりません。");
            return;
        }

        $pluginPath = base_path('plugins/' . $plugin->directory);

        // プラグインの無効化
        $this->disablePlugin($pluginName);

        // マイグレーションのロールバック
        if ($this->confirm("プラグイン '{$pluginName}' に関連するデータベースのテーブルを削除しますか？", false)) {
            $this->info("マイグレーションのロールバックを実行中...");
            $migrator = new PluginMigrator(app(Filesystem::class), app(ConnectionResolverInterface::class), 'plugin_migrations', $plugin->slug);
            $migrator->rollback($plugin->directory);
        }


        // プラグインのディレクトリを削除するか？
        if ($this->confirm("プラグイン '{$pluginName}' のディレクトリとファイルを削除しますか？", false)) {
            if (File::exists($pluginPath)) {
                File::deleteDirectory($pluginPath);
                $this->info("プラグインのディレクトリ '{$pluginPath}' を削除しました。");
            } else {
                $this->info("プラグインのディレクトリは既に存在しません。");
            }
        } else {
            $this->info("プラグインのディレクトリは削除されませんでした。");
        }

        // データベースからプラグインを削除
        DB::table('plugins')->where('name', $pluginName)->delete();
        $this->info("プラグイン '{$pluginName}' をデータベースから削除しました。");

        // オートロードを更新
        $this->updateAutoload();

        $this->info("プラグイン '{$pluginName}' のアンインストールが完了しました。");
    }
}
