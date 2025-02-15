<?php

namespace App\Console\Commands;

use App\Services\PluginMigrator;
use Illuminate\Console\Command;

class PluginFactory extends PluginMigrationCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    /**
     * artisan コマンドのシグネチャ
     *
     * {plugin} : プラグイン名
     * {model} : モデル名 (例: Page)
     * --count= : 生成するレコード数 (デフォルト: 10)
     * --force : 本番環境でも実行確認なし
     */
    protected $signature = 'plugin:factory
                            {plugin : The name of the plugin (e.g. PagesPlugin)}
                            {model : The model name (e.g. Page)}
                            {--count=10 : Number of records to create}
                            {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */

    protected $description = 'Generate records using a factory for a specific plugin model';

    protected PluginMigrator $pluginMigrator;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $plugin = $this->argument('plugin'); // 例: "PagesPlugin"
        $model  = $this->argument('model');  // 例: "Page"
        $count  = (int)($this->option('count') ?? 10);
        $force  = $this->option('force');

        // 1. プラグインディレクトリが存在するか確認
        if (!$this->pluginExists($plugin)) {
            $this->error("Plugin [{$plugin}] does not exist.");
            return Command::FAILURE;
        }

        // 2. 本番環境での確認
        if (! $force && ! $this->confirmProduction('factory')) {
            $this->info('Operation cancelled.');
            return Command::FAILURE;
        }

        // 3. モデルクラスを組み立て
        //    例: "Plugins\PagesPlugin\App\Models\Page"
        $modelClass = "Plugins\\{$plugin}\\App\\Models\\{$model}";

        if (! class_exists($modelClass)) {
            $this->error("Model class [{$modelClass}] not found.");
            return Command::FAILURE;
        }

        // 4. ファクトリが存在するかどうか (Laravel 9 以降は「モデルファクトリ」推奨)
        //    例: "Plugins\PagesPlugin\App\Models\PageFactory"
        //    ただし、実際にはモデルと同じ場所に「 PageFactory 」があるかどうかは
        //    名前の規約次第なので、厳格にチェックしたいならここで class_exists() してもOK

        try {
            // 5. ファクトリを呼び出してレコード作成
            $this->info("Creating [{$count}] records for plugin model [{$modelClass}]...");

            $modelClass::factory()->count($count)->create();

            $this->info("Successfully created [{$count}] records for [{$modelClass}].");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error creating records: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
