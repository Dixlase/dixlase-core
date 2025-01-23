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

class MakeCustomController extends Command
{
    /**
     * Artisan コマンド名と引数/オプション定義
     */
    protected $signature = 'make:custom-controller
        {name : The name of the controller}
        {--resource : Generate a resource controller class}
        {--api : Generate an API controller class}
        {--invokable : Generate a single method, invokable controller class}
        {--model= : Generate a resource controller for the given model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new controller in the custom directory';

    protected FileGenerator $fileGenerator;

    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    public function handle()
    {
        // 1) コントローラ名からサブディレクトリとクラス名を分割
        [$subDirs, $className] = $this->parseControllerName($this->argument('name'));

        // 2) ベース namespace と出力先ディレクトリ
        $baseNamespace   = 'Custom\\App\\Http\\Controllers';
        $basePath        = base_path('custom/app/Http/Controllers');

        // サブディレクトリがあれば結合
        $namespace       = $baseNamespace . ($subDirs ? '\\' . implode('\\', $subDirs) : '');
        $targetDirectory = $basePath      . ($subDirs ? '/' . implode('/', $subDirs) : '');

        // 3) コントローラの PHP ファイルパス
        $filePath = "{$targetDirectory}/{$className}.php";

        try {
            // ファイルパスを準備 (ディレクトリ作成 & 存在チェック)
            $this->fileGenerator->prepareFilePath(
                $filePath,
                "Controller [{$className}] already exists in the custom directory."
            );
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return 1;
        }

        // 5) Stubファイル選択
        $stubFileName   = $this->determineStubFileName();
        $customStubPaths = config('console.custom_stub_paths');
        $defaultStubPath = config('console.default_stub_directory');

        // スタブファイルを取得
        $stub = $this->fileGenerator->getStubContent($stubFileName, $defaultStubPath, $customStubPaths);

        // ライセンス情報とプレースホルダを埋め込む
        $stub = $this->fileGenerator->embedLicense($stub, [
            '{{ rootNamespace }}' => app()->getNamespace(),
            '{{ namespace }}' => $namespace,
            '{{ class }}'     => $className,
            '{{ model }}'     => $this->option('model') ?: 'App\\Models\\Sample',
        ]);

        // 7) モデル・フォームリクエスト関連の置換
        if ($this->option('model')) {
            // モデル用の置換 (モデルがない場合は作成するか尋ねる)
            $stub = str_replace(
                array_keys($this->buildModelReplacements()),
                array_values($this->buildModelReplacements()),
                $stub
            );

            // Resource や API 指定があれば、フォームリクエスト関連を埋め込む例
            if ($this->option('resource') || $this->option('api')) {
                $stub = str_replace(
                    array_keys($this->buildFormRequestReplacements()),
                    array_values($this->buildFormRequestReplacements()),
                    $stub
                );
            }
        }

        // ファイルを生成
        $this->fileGenerator->generateFile($filePath, $stub);

        $this->info("Controller [{$className}] created successfully in the custom directory.");

        return 0;
    }

    /**
     * コントローラ名をサブディレクトリ & クラス名に分割
     * 例: "Admin/Sub/MyController" => [["Admin","Sub"], "MyController"]
     */
    protected function parseControllerName(string $input): array
    {
        $path = str_replace('\\', '/', $input);
        $parts = explode('/', $path);

        $className = array_pop($parts);
        $subDirs   = $parts;

        return [$subDirs, $className];
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

    /**
     * --model=指定時、モデルが存在しなければ作成を促しつつ、プレースホルダを返す
     */
    protected function buildModelReplacements(): array
    {
        $modelOption = $this->option('model');  // 例: "Post"
        if (Str::startsWith($modelOption, '\\')) {
            $modelOption = Str::replaceFirst('\\', '', $modelOption);
        }

        // "Post" → "App\Models\Post" にする例 （FQCNが含まれていればそのまま）
        $modelClass = Str::contains($modelOption, '\\')
            ? $modelOption
            : 'App\\Models\\' . $modelOption;

        // モデルが存在しないなら、作るかどうかを尋ねる
        if (! class_exists($modelClass)) {
            if ($this->confirm("A [{$modelClass}] model does not exist. Do you want to generate it?", true)) {
                // Laravel標準の make:model を呼ぶ例
                $this->call('make:model', [
                    'name' => $modelClass,
                    // '--migration' => true, // ついでにマイグレーションを作成したい場合
                ]);
            }
        }

        // コントローラ.stub などにある "{{ namespacedModel }}" や "{{ modelVariable }}" を置換
        return [
            '{{ namespacedModel }}' => $modelClass,
            '{{ modelVariable }}'   => lcfirst(class_basename($modelClass)),
        ];
    }

    /**
     * Resource/API対応のフォームリクエストの置換例
     */
    protected function buildFormRequestReplacements(): array
    {
        $modelOption = $this->option('model');  // 例: "Post"
        $storeClass  = 'Store' . $modelOption . 'Request';
        $updateClass = 'Update' . $modelOption . 'Request';

        // "Custom\App\Http\Requests" のような名前空間に置くことも可能
        // "plugins/xxx" に置きたい場合は自由に変更
        $requestsNamespace = 'Custom\\App\\Http\\Requests';

        return [
            '{{ namespacedRequests }}' => $requestsNamespace,
            '{{ storeRequest }}'       => $storeClass,
            '{{ updateRequest }}'      => $updateClass,
        ];
    }
}
