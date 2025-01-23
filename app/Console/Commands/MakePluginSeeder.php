<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Services\FileGenerator;

class MakePluginSeeder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plugin:make:seeder
                            {plugin : The name of the plugin (e.g. EventsPlugin)}
                            {name : The name of the seeder class (e.g. EventSeeder or just Event)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new database seeder in the specified plugin directory';

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
        // 引数からプラグイン名とシーダー名を取得
        $pluginNameInput = $this->argument('plugin');
        $seederInput = $this->argument('name');

        // プラグイン名は必ず StudlyCase（先頭大文字）に正規化
        $studlyPluginName = Str::studly($pluginNameInput);

        // シーダー名からサブディレクトリ部分とクラス名部分に分解
        [$subDirs, $className] = $this->fileGenerator->parseClassName($seederInput);

        // ユーザが "Event" のみ入力した場合にも "EventSeeder" に補正
        if (!Str::endsWith($className, 'Seeder')) {
            $className .= 'Seeder';
        }

        // ベースの namespace (Plugins\MyPlugin\Database\Seeders)
        $baseNamespace = "Plugins\\{$studlyPluginName}\\Database\\Seeders";

        // サブディレクトリがある場合は、namespace にも付加
        $namespace = $baseNamespace
            . ($subDirs ? '\\' . implode('\\', $subDirs) : '');

        // 実際に配置するディレクトリパス
        $targetDirectory = base_path("plugins/{$studlyPluginName}/database/seeders")
            . ($subDirs ? '/' . implode('/', $subDirs) : '');

        // ファイルパス
        $filePath = "{$targetDirectory}/{$className}.php";

        try {
            // ファイルパスを準備 (ディレクトリ作成 & 存在チェック)
            $this->fileGenerator->prepareFilePath(
                $filePath,
                "Seeder [{$className}] already exists in plugin [{$studlyPluginName}]."
            );
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
            return Command::FAILURE;
        }

        // スタブファイルを取得
        $stubFileName = 'seeder.stub';
        $customStubPaths = [base_path('stubs/custom')]; // カスタムstubがある場合
        // ここはお使いのLaravelバージョンや構成に合わせてパスを調整してください
        $defaultStubPath = base_path("vendor/laravel/framework/src/Illuminate/Routing/Console/stubs/{$stubFileName}");

        $stub = $this->fileGenerator->getStubContent(
            $stubFileName,
            $defaultStubPath,
            $customStubPaths
        );

        // ライセンス情報埋め込み＆置換
        $stub = $this->fileGenerator->embedLicense($stub, [
            '{{ namespace }}' => $namespace,
            '{{ class }}'     => $className,
        ]);

        // ファイル作成
        try {
            $this->fileGenerator->generateFile($filePath, $stub);
        } catch (\Exception $e) {
            $this->error("Failed to create seeder file: {$e->getMessage()}");
            return Command::FAILURE;
        }

        // 完了メッセージ
        $this->info("Seeder [{$className}] created successfully at [{$filePath}].");

        return Command::SUCCESS;
    }
}
