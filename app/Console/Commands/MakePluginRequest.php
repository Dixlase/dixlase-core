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
use Illuminate\Support\Str;
use App\Services\FileGenerator;

class MakePluginRequest extends Command
{
    protected $signature = 'plugin:make:request
        {plugin : The plugin name}
        {name : The name of the FormRequest (optionally with subfolders, e.g. Admin/StoreMyDataRequest)}
    ';

    protected $description = 'Create a new FormRequest class for the specified plugin.';

    protected FileGenerator $fileGenerator;

    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    public function handle()
    {
        // 1) プラグイン名を studly 変換しておく
        $pluginNameInput  = $this->argument('plugin');
        $studlyPluginName = Str::studly($pluginNameInput);

        // 2) リクエスト名をサブディレクトリとクラス名に分割
        [$subDirs, $className] = $this->parseClassName($this->argument('name'));

        // 3) ベースの namespace とフォルダ
        $baseNamespace = "Plugins\\{$studlyPluginName}\\App\\Http\\Requests";
        $baseFolder    = base_path("plugins/{$studlyPluginName}/app/Http/Requests");

        // サブディレクトリ付の場合
        $namespace       = $baseNamespace . ($subDirs ? '\\' . implode('\\', $subDirs) : '');
        $targetDirectory = $baseFolder    . ($subDirs ? '/' . implode('/', $subDirs) : '');

        // 4) 出力ファイルパス
        $filePath = "{$targetDirectory}/{$className}.php";

        try {
            $this->fileGenerator->prepareFilePath(
                $filePath,
                "FormRequest [{$className}] already exists in plugin [{$studlyPluginName}]."
            );
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return Command::FAILURE;
        }

        // 5) stub ファイル (今回は常に request.stub を使用)
        $stubFile = 'request.stub';
        $customStubPaths = config('console.custom_stub_paths');
        $stub = $this->fileGenerator->getStubContent($stubFile, null, $customStubPaths);

        // 6) プレースホルダ埋め込み
        $stub = $this->fileGenerator->embedLicense($stub, [
            '{{ namespace }}' => $namespace,
            '{{ class }}'     => $className,
        ]);

        // 7) 出力
        $this->fileGenerator->generateFile($filePath, $stub);

        $this->info("FormRequest [{$className}] created successfully in plugin [{$studlyPluginName}].");
        return Command::SUCCESS;
    }

    /**
     * "Admin/StorePageRequest" → [["Admin"], "StorePageRequest"] に分解
     */
    protected function parseClassName(string $input): array
    {
        $path = str_replace('\\', '/', $input);
        $parts = explode('/', $path);

        $className = array_pop($parts);
        $subDirs   = $parts;

        return [$subDirs, $className];
    }
}
