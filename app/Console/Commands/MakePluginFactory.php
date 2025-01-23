<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Services\FileGenerator;

class MakePluginFactory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plugin:make:factory
                            {plugin : The name of the plugin (e.g. EventsPlugin)}
                            {name : The name of the factory class (e.g. EventFactory or just Event)}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new model factory in the specified plugin directory';

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
        $pluginNameInput = $this->argument('plugin');
        $factoryInput = $this->argument('name');

        // プラグイン名は必ず StudlyCase（先頭大文字）に正規化
        $studlyPluginName = Str::studly($pluginNameInput);

        // コントローラの入力をパースして、サブディレクトリ部分とクラス名部分に分解
        [$subDirs, $className] = $this->fileGenerator->parseClassName($factoryInput);

        // 3.1) ユーザが "Event" のみ入力した場合にも "EventFactory" に補正
        if (!Str::endsWith($className, 'Factory')) {
            $className .= 'Factory';
        }

        // ベースの namespace
        //    例) "Plugins\PagesPlugin\App\Http\Controllers"
        $baseNamespace = "Plugins\\{$studlyPluginName}\\Database\\Factories";

        // サブディレクトリがある場合は、namespace にも付加
        $namespace = $baseNamespace
            . ($subDirs ? '\\' . implode('\\', $subDirs) : '');


        // 実際に配置するディレクトリパス
        $targetDirectory = base_path("plugins/{$studlyPluginName}/database/factories")
            . ($subDirs ? '/' . implode('/', $subDirs) : '');

        // 6) 実ファイルパス生成
        $filePath = "{$targetDirectory}/{$className}.php";


        try {
            // ファイルパスを準備 (ディレクトリ作成 & 存在チェック)
            $this->fileGenerator->prepareFilePath(
                $filePath,
                "Factory [{$className}] already exists in plugin [{$studlyPluginName}]."
            );
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return Command::FAILURE;
        }

        // 適切なスタブファイルを選択
        $stubFileName = 'factory.stub';
        $customStubPaths = [base_path('stubs/custom')];
        $defaultStubPath = base_path("vendor/laravel/framework/src/Illuminate/Routing/Console/stubs/{$stubFileName}");

        // スタブファイルを取得
        $stub = $this->fileGenerator->getStubContent($stubFileName, $defaultStubPath, $customStubPaths);

        // ...
        // 1) ファクトリー名から "Factory" を除去し、モデル名を推測
        $modelName = Str::replaceLast('Factory', '', $className);

        // 8.1) サブディレクトリもモデルに反映したい場合はここで組み立て
        //    例: "Plugins\EventsPlugin\App\Models\Blog\Post"
        $modelNamespace = "Plugins\\{$studlyPluginName}\\App\\Models";
        if (!empty($subDirs)) {
            $modelNamespace .= "\\" . implode("\\", $subDirs);
        }
        $namespacedModel = "{$modelNamespace}\\{$modelName}";

        // ライセンス情報とプレースホルダを埋め込む
        $stub = $this->fileGenerator->embedLicense($stub, [
            '{{ factoryNamespace }}' => $namespace,
            '{{ factory }}' => $className,
            '{{ namespacedModel }}' => $namespacedModel,
        ]);

        // 10) ファイル生成
        try {
            $this->fileGenerator->generateFile(
                $filePath,
                $stub
            );
        } catch (\Exception $e) {
            $this->error("Failed to create factory file: {$e->getMessage()}");
            return Command::FAILURE;
        }

        // 11) 成功メッセージ
        $this->info("Factory [{$className}] created successfully at [{$filePath}].");

        return Command::SUCCESS;
    }
}
