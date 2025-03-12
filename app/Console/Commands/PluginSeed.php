<?php

/**
 * This file is part of MySoftware.
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
