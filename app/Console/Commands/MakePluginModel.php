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

class MakePluginModel extends Command
{
    protected $signature = 'plugin:make:model
        {plugin : The plugin name}
        {name : The name of the model (optionally with subfolders, e.g. Admin/MyModel)}
        {--pivot : Create a pivot model}
    ';

    protected $description = 'Create a new Eloquent model class for the specified plugin.';

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

        // 2) モデル名をサブディレクトリとクラス名に分割
        [$subDirs, $className] = $this->parseClassName($this->argument('name'));

        // 3) ベース名前空間とディレクトリ
        $baseNamespace   = "Plugins\\{$studlyPluginName}\\App\\Models";
        $baseModelFolder = base_path("plugins/{$studlyPluginName}/app/Models");

        // サブディレクトリ付きなら、namespace と生成先パスに付加
        $namespace = $baseNamespace
            . ($subDirs ? '\\' . implode('\\', $subDirs) : '');
        $targetDirectory = $baseModelFolder
            . ($subDirs ? '/' . implode('/', $subDirs) : '');

        // 4) pivotオプションがあれば、pivot用stubを使う
        $isPivot  = $this->option('pivot');
        $stubFile = $isPivot ? 'model.pivot.stub' : 'model.stub';

        // 5) 実ファイルパス
        $filePath = "{$targetDirectory}/{$className}.php";

        // ファイルパス準備（既存チェック & ディレクトリ作成）
        try {
            $this->fileGenerator->prepareFilePath(
                $filePath,
                "Model [{$className}] already exists in plugin [{$studlyPluginName}]."
            );
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return Command::FAILURE;
        }

        // 6) スタブファイル取得
        $customStubPaths = config('console.custom_stub_paths');
        // 既存の Laravel コアのモデルstub は vendor/laravel/framework/... にありますが、
        // 今回は独自のstubsフォルダのみを参照
        $stub = $this->fileGenerator->getStubContent($stubFile, null, $customStubPaths);

        // 7) プレースホルダを埋め込む
        //    まずはライセンス情報などを埋め込み
        $stub = $this->fileGenerator->embedLicense($stub, [
            '{{ namespace }}' => $namespace,
            '{{ class }}'     => $className,
            // その他 {{ license }} は embedLicense() の内部で置換
        ]);

        // 追加の置換（pivotモデルでない場合に factoryImport/factory をどうするか等はプロジェクト次第）
        $factoryImport = '';
        $factoryCode   = '';
        // 例: Factoryを生成したい場合などに応じてココで文字列を作る

        $stub = $this->fileGenerator->replacePlaceholders($stub, [
            '{{ factoryImport }}' => $factoryImport,
            '{{ factory }}'       => $factoryCode,
        ]);

        // 8) ファイル生成
        $this->fileGenerator->generateFile($filePath, $stub);

        $this->info("Model [{$className}] created successfully in plugin [{$studlyPluginName}].");
        return Command::SUCCESS;
    }

    /**
     * "Admin/MyModel" → [["Admin"], "MyModel"] に分解
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
