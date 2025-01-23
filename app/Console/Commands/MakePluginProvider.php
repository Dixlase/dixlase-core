<?php

/**
 * This file is part of Your Software Name.
 *
 * Copyright (C) 2024 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FileGenerator;
use Illuminate\Support\Str;

class MakePluginProvider extends Command
{


    protected $signature = 'plugin:make:provider
        {plugin : The plugin name}
        {name : The name of the service provider}
        {--plugin : Use the plugin-specific provider template}';

    protected $description = 'Create a new service provider for the specified plugin.';
    protected FileGenerator $fileGenerator;

    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    public function handle()
    {
        $pluginName = $this->argument('plugin');
        $fileName = $this->argument('name');
        $usePluginStub = $this->option('plugin'); // プラグイン固有のスタブを使用するか

        $namespace = $this->fileGenerator->generateNamespace($pluginName, "Plugins") . "\\App\\Providers";
        $pluginAlias = Str::kebab($pluginName);
        $pluginBaseName = Str::studly($pluginName);

        $targetDirectory = base_path("plugins/{$pluginName}/app/Providers");
        $filePath = "{$targetDirectory}/{$fileName}.php";

        try {
            // ファイルパスを準備 (ディレクトリ作成 & 存在チェック)
            $this->fileGenerator->prepareFilePath(
                $filePath,
                "Service provider [{$fileName}] already exists in plugin [{$pluginName}]."
            );
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return 1;
        }

        // 使用するスタブファイルを選択
        $stubFileName = $usePluginStub ? 'provider.plugin.stub' : 'provider.stub';
        $customStubPaths = config('console.custom_stub_paths');
        $defaultStubPath = config('console.default_stub_directory');

        $stub = $this->fileGenerator->getStubContent($stubFileName, $defaultStubPath, $customStubPaths);

        $stub = $this->fileGenerator->embedLicense($stub, [
            '{{ namespace }}' => $namespace,
            '{{ class }}'     => $fileName,
            '{{ pluginName }}' => $pluginBaseName,
            '{{ pluginAlias }}' => $pluginAlias,
        ]);

        $this->fileGenerator->generateFile($filePath, $stub);

        $this->info("Service provider [{$fileName}] created successfully in plugin [{$pluginName}].");

        return 0;
    }
}
