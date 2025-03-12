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
use Illuminate\Support\Str;
use App\Services\FileGenerator;
use App\Console\Traits\MakeBladeTrait;

class MakePluginBlade extends Command
{
    use MakeBladeTrait;

    protected $signature = 'make:plugin:blade
        {plugin : The plugin name (e.g. MyPlugin)}
        {file : The blade file name (e.g. admin/dashboard)}
        {--force : Overwrite if the blade file already exists}
        {--type=front : The type of Blade file (front/admin)}';

    protected $description = 'Create a new Blade template in the specified plugin\'s resources/views directory';

    protected FileGenerator $fileGenerator;

    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }


    public function handle()
    {
        $plugin = Str::studly($this->argument('plugin'));
        $file = $this->argument('file');

        $options = [
            'force' => (bool) $this->option('force'),
            'type' => $this->option('type'),
        ];

        if (!in_array($options['type'], ['front', 'admin'])) {
            $this->error("Invalid type: '{$options['type']}'. Choose 'front' or 'admin'.");
            return 1;
        }

        // ここでMakeBladeTraitのmakeFile()を呼ぶ
        $this->makeFile($file, [], $options);

        return 0;
    }

    /**
     * プラグイン用 => "plugins/{Plugin}/resources/views"
     */
    protected function getDirectory(array $subDirs): string
    {
        $pluginName = Str::studly($this->argument('plugin'));
        $base = base_path("plugins/{$pluginName}/resources/views");
        if (!empty($subDirs)) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    /**
     * Blade ファイルにはネームスペースは不要なので空文字を返す
     */
    protected function getNamespace(array $subDirs): string
    {
        return '';
    }
}
