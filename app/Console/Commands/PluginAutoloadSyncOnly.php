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

use Illuminate\Console\Command;

class PluginAutoloadSyncOnly extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plugin:autoload:sync-only';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add new plugin directories to composer.json autoload (no cleanup).';

    // プラグインで追加したいPSR-4パス設定例
    protected array $pluginPaths = [
        'App\\' => 'app',
        'Database\\Factories\\' => 'database/factories',
        'Database\\Seeders\\'   => 'database/seeders',
        'Database\\Seeders\\Dev\\'   => 'database/seeders/dev',
        'Database\\Seeders\\Pro\\'   => 'database/seeders/pro',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $composerJsonPath = base_path('composer.json');
        $composerConfig = json_decode(file_get_contents($composerJsonPath), true);

        // 現在のPSR-4設定を取得
        $psr4 = $composerConfig['autoload']['psr-4'] ?? [];

        // pluginsディレクトリ内のすべてのサブフォルダを取得
        $pluginDirectories = glob(base_path('plugins/*'), GLOB_ONLYDIR);

        $hasChanges = false;

        foreach ($pluginDirectories as $pluginDir) {
            $pluginName = basename($pluginDir); // 例: "MyPlugin"

            // 期待するPSR-4を順番に確認
            foreach ($this->pluginPaths as $namespaceSuffix => $subDir) {
                // 期待する名前空間 例: "Plugins\MyPlugin\App\"
                $namespace = "Plugins\\{$pluginName}\\" . $namespaceSuffix;
                // 期待するディレクトリ 例: "plugins/MyPlugin/app"
                $relativePath = "plugins/{$pluginName}/{$subDir}";

                // もしディレクトリがある && PSR-4に未登録なら追加
                if (is_dir(base_path($relativePath)) && !isset($psr4[$namespace])) {
                    $psr4[$namespace] = $relativePath;
                    $hasChanges = true;
                }
            }
        }

        if ($hasChanges) {
            $composerConfig['autoload']['psr-4'] = $psr4;

            file_put_contents(
                $composerJsonPath,
                json_encode($composerConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );

            $this->info('Added new plugin directories to composer.json autoload.');
        } else {
            $this->info('No new plugin directories found; no changes made.');
        }

        return Command::SUCCESS;
    }
}
