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


use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PluginAutoloadCleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plugin:autoload:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove non-existent plugin directories from composer.json autoload.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $composerJsonPath = base_path('composer.json');
        $composerConfig = json_decode(file_get_contents($composerJsonPath), true);

        $psr4 = $composerConfig['autoload']['psr-4'] ?? [];

        // 現存するプラグイン (ディレクトリ名) を取得
        $pluginDirectories = glob(base_path('plugins/*'), GLOB_ONLYDIR);
        $existingPlugins = array_map('basename', $pluginDirectories); // ["MyPlugin","SomeOtherPlugin"]

        $hasChanges = false;

        foreach ($psr4 as $namespace => $path) {
            // "Plugins\MyPlugin\..." に該当するかチェック
            if (preg_match('/^Plugins\\\\([A-Za-z0-9_]+)\\\\(.*)/', $namespace, $matches)) {
                $foundPluginName = $matches[1];

                // plugins/内に該当ディレクトリがない -> 削除
                if (!in_array($foundPluginName, $existingPlugins)) {
                    unset($psr4[$namespace]);
                    $hasChanges = true;
                } else {
                    // ディレクトリ名はあるが、subDir も本当にあるか？
                    $fullPath = base_path($path);
                    if (!is_dir($fullPath)) {
                        unset($psr4[$namespace]);
                        $hasChanges = true;
                    }
                }
            }
        }

        if ($hasChanges) {
            $composerConfig['autoload']['psr-4'] = $psr4;

            file_put_contents(
                $composerJsonPath,
                json_encode($composerConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );

            $this->info('Removed non-existent plugin directories from composer.json autoload.');
        } else {
            $this->info('No obsolete plugin directories found; no changes made.');
        }

        return Command::SUCCESS;
    }
}
