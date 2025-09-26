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

use App\Services\PluginMigrator;
use Illuminate\Console\Command;
use App\Console\Traits\PluginManagementTrait;


class PluginFactory extends Command
{
    use PluginManagementTrait;

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
     */
    protected $signature = 'plugin:factory
                            {plugin : The name of the plugin (e.g. DixlasePages)}
                            {model : The model name (e.g. Page)}
                            {--count=10 : Number of records to create}
                            {--force : Force the operation to run}';

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

        // 3. モデルクラスを組み立て
        //    例: "Plugins\PagesPlugin\App\Models\Page"
        $modelClass = "Plugins\\{$plugin}\\App\\Models\\{$model}";

        if (! class_exists($modelClass)) {
            $this->error("Model class [{$modelClass}] not found.");
            return Command::FAILURE;
        }

        // ファクトリを実行
        try {
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
