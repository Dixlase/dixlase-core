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
        {--requests : Generate FormRequest classes (StoreXxxRequest, UpdateXxxRequest) for the model}
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
        // 引数からプラグイン名（PagesPlugin など）とコントローラ名を取得
        // 1) 引数・オプション取得
        $pluginNameInput = $this->argument('plugin');   // 例: "PagesPlugin"
        $controllerInput = $this->argument('name');     // 例: "Admin/MyController"
        $modelOption     = $this->option('model');      // 例: "Page"
        $isResource      = $this->option('resource');
        $isApi           = $this->option('api');
        $isInvokable     = $this->option('invokable');
        $withRequests    = $this->option('requests');


        // プラグイン名は必ず StudlyCase（先頭大文字）に正規化
        $studlyPluginName = Str::studly($pluginNameInput);

        // コントローラの入力をパースして、サブディレクトリ部分とクラス名部分に分解
        //    - 例) "Admin/AdminPagesPluginController" => ["Admin"], "AdminPagesPluginController"
        //    - 例) "MyAwesomeController" => [], "MyAwesomeController"
        [$subDirs, $className] = $this->fileGenerator->parseClassName($controllerInput);

        // ベースの namespace
        //    例) "Plugins\PagesPlugin\App\Http\Controllers"
        $baseNamespace = "Plugins\\{$studlyPluginName}\\App\\Http\\Controllers";

        // サブディレクトリがある場合は、namespace にも付加
        //    例) $baseNamespace . "\Admin" => "Plugins\PagesPlugin\App\Http\Controllers\Admin"
        $namespace = $baseNamespace
            . ($subDirs ? '\\' . implode('\\', $subDirs) : '');

        // 実際に配置するディレクトリパス
        //    例) "plugins/PagesPlugin/app/Http/Controllers/Admin"
        $targetDirectory = base_path("plugins/{$studlyPluginName}/app/Http/Controllers")
            . ($subDirs ? '/' . implode('/', $subDirs) : '');

        // 6) 実ファイルパス生成
        //    例) "plugins/PagesPlugin/app/Http/Controllers/Admin/AdminPagesPluginController.php"
        $filePath = "{$targetDirectory}/{$className}.php";

        try {
            // ファイルパスを準備 (ディレクトリ作成 & 存在チェック)
            $this->fileGenerator->prepareFilePath(
                $filePath,
                "Controller [{$className}] already exists in plugin [{$studlyPluginName}]."
            );
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return Command::FAILURE;
        }

        // 8) 適切なスタブファイルを判定
        $stubFileName = $this->determineStubFileName(
            $modelOption,
            $isResource,
            $isApi,
            $isInvokable
        );


        // 適切なスタブファイルを選択
        $stubFileName = $this->determineStubFileName();
        $customStubPaths = [base_path('stubs/custom')];
        $defaultStubPath = base_path("vendor/laravel/framework/src/Illuminate/Routing/Console/stubs/{$stubFileName}");

        // スタブファイルを取得
        $stub = $this->fileGenerator->getStubContent($stubFileName, $defaultStubPath, $customStubPaths);


        // ライセンス情報とプレースホルダを埋め込む
        $stub = $this->fileGenerator->embedLicense($stub, [
            '{{ rootNamespace }}' => app()->getNamespace(),
            '{{ namespace }}' => $namespace,
            '{{ class }}'     => $className,
            '{{ model }}'     => $this->option('model') ?: 'App\\Models\\Sample',
        ]);

        // 追加で、モデルやフォームリクエスト用のプレースホルダを置換
        if ($modelOption) {
            // モデルReplacementsを取得 (未存在なら作るかどうか確認)
            $modelReplacements = $this->buildModelReplacements($modelOption);

            // stubに適用
            $stub = str_replace(
                array_keys($modelReplacements),
                array_values($modelReplacements),
                $stub
            );

            // 12) --resource / --api などを指定しており、さらに --requests があればフォームリクエストを生成
            if (($isResource || $isApi) && $withRequests) {
                $formRequestReplacements = $this->buildFormRequestReplacements($modelOption);
                $stub = str_replace(
                    array_keys($formRequestReplacements),
                    array_values($formRequestReplacements),
                    $stub
                );
            }
        }


        // ファイルを生成
        $this->fileGenerator->generateFile($filePath, $stub);

        $this->info("Controller [{$className}] created successfully in plugin [{$studlyPluginName}].");

        return Command::SUCCESS;
    }

    /**
     * --modelオプションで指定されたモデルが存在しなければ、プラグイン用モデル作成コマンドを呼び出す
     * そのうえでコントローラstubに必要な置換配列を返す
     */
    protected function buildModelReplacements(string $modelOption): array
    {
        // モデルのFQCNを解析
        $modelClass = $this->parseModelClass($modelOption);

        // クラスが存在しなければ生成確認
        if (! class_exists($modelClass)) {
            if ($this->confirm("A [{$modelClass}] model does not exist. Do you want to generate it?", true)) {
                // "plugin:make:model" を呼び出し (Laravel標準 make:model にしたいなら 'make:model')
                $this->call('plugin:make:model', [
                    'plugin' => $this->argument('plugin'), // 例: PagesPlugin
                    'name'   => class_basename($modelClass),
                    // 他に --migration オプション等を付けてもOK
                ]);
            }
        }


        return [
            // use {{ namespacedModel }}
            '{{ namespacedModel }}' => $modelClass,
            // コントローラ内で (Model $modelVariable)
            '{{ modelVariable }}'   => lcfirst(class_basename($modelClass)),
        ];
    }

    /**
     * --requests オプションがある場合に、フォームリクエストを自動生成して置換配列を返す
     * 例: StorePageRequest, UpdatePageRequest など
     */
    protected function buildFormRequestReplacements(string $modelOption): array
    {
        // 例: "Page" -> "App\Models\Page"
        $modelClass = $this->parseModelClass($modelOption);
        $modelBase  = class_basename($modelClass); // "Page"

        // リクエストクラス名
        $storeRequestClass  = 'Store' . $modelBase . 'Request';
        $updateRequestClass = 'Update' . $modelBase . 'Request';

        // plugin:make:request コマンドで実際にファイルを生成する
        // （標準の make:request でもOK）
        $this->call('plugin:make:request', [
            'plugin' => $this->argument('plugin'),
            'name'   => $storeRequestClass,
        ]);
        $this->call('plugin:make:request', [
            'plugin' => $this->argument('plugin'),
            'name'   => $updateRequestClass,
        ]);

        // 完全修飾クラス名
        $pluginStudly = Str::studly($this->argument('plugin'));
        $namespace    = "Plugins\\{$pluginStudly}\\App\\Http\\Requests";

        // 複数リクエストクラスを use する場合
        // 例: "use Plugins\MyPlugin\App\Http\Requests\StorePageRequest;"
        $namespacedRequests = "{$namespace}\\{$storeRequestClass};";
        if ($storeRequestClass !== $updateRequestClass) {
            $namespacedRequests .= PHP_EOL . "use {$namespace}\\{$updateRequestClass};";
        }

        // コントローラstub側のプレースホルダを置換するための配列を返す
        return [
            '{{ namespacedRequests }}' => $namespacedRequests,     // "Plugins\MyPlugin\App\Http\Requests\StorePageRequest;\nuse ... "
            '{{ storeRequest }}'       => $storeRequestClass,      // "StorePageRequest"
            '{{ updateRequest }}'      => $updateRequestClass,     // "UpdatePageRequest"
        ];
    }


    /**
     * モデル名をFQCNに変換
     * 例: "Page" => "App\Models\Page"
     *     "Plugins\PagesPlugin\App\Models\Page" => そのまま
     */
    protected function parseModelClass(string $modelOption): string
    {
        // 先頭がバックスラッシュなら取り除く
        if (Str::startsWith($modelOption, '\\')) {
            $modelOption = Str::replaceFirst('\\', '', $modelOption);
        }

        // 既に FQCN (App\～ など) で指定されているならそのまま使う
        // そうでなければデフォルトとして "App\Models\<modelOption>" にする例
        if (Str::contains($modelOption, '\\')) {
            return $modelOption;
        }

        // デフォルトはプラグイン配下のモデル
        $pluginStudly = Str::studly($this->argument('plugin'));
        return "Plugins\\{$pluginStudly}\\App\\Models\\{$modelOption}";
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
     * 入力されたコントローラ名を「サブディレクトリ」「クラス名」に分解する
     *
     * 例:
     *   "Admin/AdminPagesPluginController" => [["Admin"], "AdminPagesPluginController"]
     *   "Api/V2/MyController" => [["Api", "V2"], "MyController"]
     *   "MyController" => [[], "MyController"]
     */
    protected function parseControllerName(string $input): array
    {
        // バックスラッシュもフォワードスラッシュに揃える
        $path = str_replace('\\', '/', $input);

        // "/" で分割
        $parts = explode('/', $path);

        // 最後の要素がクラス名、それ以外はサブディレクトリとみなす
        $className = array_pop($parts);
        $subDirs   = $parts; // 例) ["Admin"] など

        return [$subDirs, $className];
    }
}
