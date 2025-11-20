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
use Illuminate\Support\Facades\Artisan;
use App\Console\Traits\PluginManagementTrait;

class PluginSeed extends Command
{
    use PluginManagementTrait;

    /**
     * artisan コマンドのシグネチャ
     *  - {plugin} : プラグイン名
     *  - --class : 実行する Seeder クラス名 (デフォルト: DatabaseSeeder)
     */
    protected $signature = 'dls:plugin:seed
                            {plugin : The name of the plugin (e.g. EventsPlugin)}
                            {--class=DatabaseSeeder : The seeder class name to run}
                            {--force : Force the operation to run in production}';
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

        // 2. シーダークラスの確認
        $fullSeederClass = "Plugins\\{$plugin}\\Database\\Seeders\\{$seederClassName}";
        if (!class_exists($fullSeederClass)) {
            $this->error("Seeder class [{$fullSeederClass}] not found.");
            return Command::FAILURE;
        }

        // 3. シーダー実行
        $this->info("Seeding [{$fullSeederClass}]...");

        try {
            Artisan::call('db:seed', [
                '--class' => $fullSeederClass,
                '--force' => true, // 本番環境でも確認なしで実行
            ]);

            $this->info(Artisan::output()); // シーダーの実行結果を表示
            $this->info("Seeding for plugin [{$plugin}] completed successfully.");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error executing seeder: " . $e->getMessage());
            return Command::FAILURE;
        }

        $this->info('Seeding cancelled.');
        return Command::FAILURE;
    }
}
