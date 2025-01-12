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
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use App\Services\FileGenerator;

class MakePluginController extends Command
{
    /**
     * Artisan コマンド名と引数/オプション定義
     * 例: php artisan plugin:make:controller my-plugin MyController
     */
    protected $signature = 'plugin:make:controller
        {plugin : The plugin name}
        {name : The name of the controller}
        {--resource : Generate a resource controller class}
        {--api : Generate an API controller class}
        {--invokable : Generate a single method, invokable controller class}
        {--model= : Generate a resource controller for the given model}
    ';

    /**
     * コマンドの簡単な説明
     */
    protected $description = 'Create a new controller class for the specified plugin.';

    /**
     * ファイル操作用のインスタンス
     */

    protected FileGenerator $fileGenerator;

    /**
     * コンストラクタ（FilesystemのDIなどに利用）
     */
    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 引数を取得
        $pluginName      = $this->argument('plugin');
        $fileName  = $this->argument('name');

        // ネームスペースを生成
        $namespace = $this->fileGenerator->generateNamespace($pluginName, "Plugins") . "\\App\\Http\\Controllers";

        // ファイルパスを準備
        $targetDirectory = base_path("plugins/{$pluginName}/app/Http/Controllers");
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

        // 適切なスタブファイルを選択
        $stubFileName = $this->determineStubFileName();
        $customStubPaths = [base_path('stubs')];
        $defaultStubPath = base_path("vendor/laravel/framework/src/Illuminate/Routing/Console/stubs/{$stubFileName}");

        // スタブファイルを取得
        $stub = $this->fileGenerator->getStubContent($stubFileName, $defaultStubPath, $customStubPaths);


        // ライセンス情報とプレースホルダを埋め込む
        $stub = $this->fileGenerator->embedLicense($stub, [
            '{{ rootNamespace }}' => app()->getNamespace(),
            '{{ namespace }}' => $namespace,
            '{{ class }}'     => $fileName,
            '{{ model }}'     => $this->option('model') ?: 'App\\Models\\Sample',
        ]);

        // ファイルを生成
        $this->fileGenerator->generateFile($filePath, $stub);

        $this->info("Controller [{$fileName}] created successfully in plugin [{$pluginName}].");

        return 0;
    }

    /**
     * 適切なスタブファイル名を判定
     */
    protected function determineStubFileName(): string
    {
        $isResource = $this->option('resource');
        $isApi = $this->option('api');
        $isInvokable = $this->option('invokable');
        $model = $this->option('model');

        if ($model) {
            return $isApi ? 'controller.model.api.stub' : 'controller.model.stub';
        }

        if ($isInvokable) {
            return 'controller.invokable.stub';
        }

        if ($isResource && $isApi) {
            return 'controller.api.stub';
        }

        if ($isResource) {
            return 'controller.stub';
        }

        if ($isApi) {
            return 'controller.api.stub';
        }

        return 'controller.plain.stub';
    }
}
