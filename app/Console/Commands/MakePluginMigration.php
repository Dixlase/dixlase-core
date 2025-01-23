<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Services\FileGenerator;

class MakePluginMigration extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plugin:make:migration
        {plugin : The name of the plugin (e.g. EventsPlugin)}
        {name : The name of the migration (e.g. create_events_table, add_columns_to_events_table)}
        {--create= : The table to be created}
        {--table= : The table to migrate}
        {--path= : The location where the file should be created}
        {--realpath : Indicate that the provided migration file paths are pre-resolved absolute paths}
        {--fullpath : Output the full path of the migration}
        {--force : Force the operation to run when in production}
    ';

    /**
     * The console command description.
     *
     * @var string
     */

    protected $description = 'Create a new migration file in the specified plugin directory';

    protected FileGenerator $fileGenerator;

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
        // 1) 引数・オプション取得
        $pluginNameInput = $this->argument('plugin');
        $migrationName = $this->argument('name');

        $tableOption = $this->option('table');
        $createOption = $this->option('create');
        $customPath = $this->option('path');
        $realpathOption = $this->option('realpath');
        $fullpathOption = $this->option('fullpath');
        $forceOption = $this->option('force');

        // 2) プラグイン名を StudlyCase 化
        $studlyPluginName = Str::studly($pluginNameInput);

        // 3) タイムスタンプ付きのマイグレーションファイル名を作成
        //    (Laravel と同様に YYYY_MM_DD_HHmmss_ のフォーマット)
        $timestamp = date('Y_m_d_His');
        $fileName = $timestamp . '_' . $migrationName . '.php';

        // 4) 実際に配置するディレクトリを決定
        //    --path オプション指定があればそれを使い、無ければプラグイン既定フォルダへ
        if ($customPath) {
            // path が絶対パスかどうかを realpath オプションを見て判定
            $targetDirectory = $realpathOption
                ? $customPath
                : base_path($customPath);
        } else {
            $targetDirectory = base_path("plugins/{$studlyPluginName}/database/migrations");
        }

        $filePath = rtrim($targetDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;

        // 5) どのスタブファイルを使うか判定
        //    ここでは create, update, それ以外の３分岐にしている例。
        $stubFileName = 'migration.stub'; // デフォルト

        if ($createOption) {
            $stubFileName = 'migration.create.stub';
        } elseif ($tableOption) {
            $stubFileName = 'migration.update.stub';
        }

        // 6) スタブファイルの取得先を決定
        //    ※ stub:publish 後の custom ディレクトリ、またはデフォルト stubs などを想定。
        //      実際のパスは環境に合わせて調整してください。
        $customStubPaths = [base_path('stubs/custom')];
        // Laravel標準のマイグレーションスタブとディレクトリ構造が異なる場合は
        // vendorフォルダ内のパスや、自作の stubs フォルダを指定するなど変更してください。
        $defaultStubPath = base_path("vendor/laravel/framework/src/Illuminate/Database/Console/Factories/stubs/{$stubFileName}");
        // ↑ 本来 Laravel の標準マイグレーション stub は `Illuminate/Database/Console/Migrations/stubs` 下にあるケースが多いです。
        //    プロジェクトのバージョンに合わせてパスを変更してください。

        $stub = $this->fileGenerator->getStubContent($stubFileName, $defaultStubPath, $customStubPaths);

        // 7) クラス名の自動生成 (create_users_table → CreateUsersTable など)
        //    Laravel の make:migration と同等に、スネークケース→StudlyCase 変換する例です。
        $className = Str::studly($migrationName);

        // 8) スタブのプレースホルダ埋め込み
        //    作成するテーブル名やクラス名が {{ table }} や {{ class }} などに入る想定。
        //    ※ ここは各 stub ファイルの中身に合わせてプレースホルダを調整してください。
        $replacements = [
            '{{ class }}' => $className,
            '{{ table }}' => $createOption ?: $tableOption, // createオプションがあればそちら優先
        ];

        $stub = $this->fileGenerator->embedLicense($stub, $replacements);
        // ↑ embedLicense はライセンス文を差し込むメソッドの例として記載されていますが、
        //   ここでは単にプレースホルダ置換のサンプルメソッドとして使っています。
        //   あるいは $this->fileGenerator->replacePlaceholders($stub, $replacements); のように処理してもOKです。

        // 9) 実際にファイルを生成
        try {
            // ディレクトリ作成 & 重複チェックなどをしたい場合は、FileGenerator 側で行う想定
            $this->fileGenerator->prepareFilePath(
                $filePath,
                "Migration [{$className}] already exists for plugin [{$studlyPluginName}]."
            );

            $this->fileGenerator->generateFile($filePath, $stub);
        } catch (\Exception $e) {
            $this->error("Failed to create migration file: {$e->getMessage()}");
            return Command::FAILURE;
        }

        // 10) コンソール出力
        if ($fullpathOption) {
            $this->info($filePath);
        } else {
            $this->info("Migration [{$fileName}] created successfully in [{$targetDirectory}].");
        }

        return Command::SUCCESS;
    }
}
