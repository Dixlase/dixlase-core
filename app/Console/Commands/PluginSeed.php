<?php

namespace App\Console\Commands;

use App\Services\PluginMigrator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class PluginSeed extends PluginMigrationCommand
{
    /**
     * artisan コマンドのシグネチャ
     *  - {plugin} : プラグイン名
     *  - --class : 実行する Seeder クラス名 (デフォルト: DatabaseSeeder)
     *  - --force : 本番環境でも確認無しで実行
     */
    protected $signature = 'plugin:seed
                            {plugin : The name of the plugin (e.g. EventsPlugin)}
                            {--class=DatabaseSeeder : The seeder class name to run}
                            {--force : Force the operation to run when in production}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the database seeds for a specific plugin';

    protected PluginMigrator $pluginMigrator;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $plugin = $this->argument('plugin');
        $force  = $this->option('force');
        $seederClassName = $this->option('class'); // 既定は 'DatabaseSeeder'

        // 1. プラグインディレクトリが存在するか確認
        if (!$this->pluginExists($plugin)) {
            $this->error("Plugin [{$plugin}] does not exist.");
            return Command::FAILURE;
        }

        // 2. （必要に応じて）シーダーファイルがあるか確認
        //    -> ただし Laravel のシーダーはクラス名ベースなので、class_exists() で検証してもOK
        $fullSeederClass = "Plugins\\{$plugin}\\Database\\Seeders\\{$seederClassName}";
        if (! class_exists($fullSeederClass)) {
            $this->error("Seeder class [{$fullSeederClass}] not found.");
            return Command::FAILURE;
        }

        // 3. 本番環境での確認
        if ($force || $this->confirmProduction('seed')) {
            // 4. 実行
            $this->info("Seeding [{$fullSeederClass}]...");

            Artisan::call('db:seed', [
                '--class' => $fullSeederClass,
                '--force' => true, // 本番でも確認無しで実行
            ]);

            $this->info(Artisan::output()); // シーダーの結果表示
            $this->info("Seeding for plugin [{$plugin}] completed successfully.");
            return Command::SUCCESS;
        }

        $this->info('Seeding cancelled.');
        return Command::FAILURE;
    }
}
