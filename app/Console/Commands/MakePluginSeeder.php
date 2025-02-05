<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Services\FileGenerator;
use App\Console\Traits\MakeSeederTrait;

class MakePluginSeeder extends Command
{
    use MakeSeederTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */

    protected $signature = 'make:plugin:seeder
        {plugin : The name of the plugin (e.g. "EventsPlugin")}
        {name : The name of the seeder class (e.g. "EventSeeder" or "Event")}
        {--force : Overwrite if the seeder file already exists}';

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

    public function handle()
    {
        // 1) plugin名
        $pluginInput = $this->argument('plugin');
        $studlyPluginName = Str::studly($pluginInput);

        // 2) parse subDirs + className
        [$subDirs, $className] = $this->fileGenerator->parseClassName($this->argument('name'));

        // 3) シーダー名の末尾が "Seeder" でなければ付ける
        if (! Str::endsWith($className, 'Seeder')) {
            $className .= 'Seeder';
        }

        // 4) --force
        $force = (bool)$this->option('force');

        // 5) call trait method
        $this->makeFile($className, $subDirs, $force);

        return 0;
    }

    /**
     * getSeederDirectory/Namespace
     */
    protected function getSeederDirectory(array $subDirs): string
    {
        $plugin = Str::studly($this->argument('plugin'));
        $base = base_path("plugins/{$plugin}/database/seeders");
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getSeederNamespace(array $subDirs): string
    {
        $plugin = Str::studly($this->argument('plugin'));
        $base = "Plugins\\{$plugin}\\Database\\Seeders";
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
