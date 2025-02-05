<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Services\FileGenerator;
use App\Console\Traits\MakeTestTrait;

class MakePluginTest extends Command
{
    use MakeTestTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:plugin:test
        {plugin : The plugin name (e.g. "MyPlugin")}
        {name : The test class name (e.g. "UserControllerTest")}
        {--force : Overwrite if test already exists}
        {--unit : Create a unit test}
        {--pest : Create a Pest test}
        {--phpunit : Create a PHPUnit test (disable Pest even if installed)}';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected FileGenerator $fileGenerator;

    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    public function handle()
    {
        // 1) plugin名
        $pluginName = Str::studly($this->argument('plugin'));

        // 2) parse subDirs + className
        [$subDirs, $className] = $this->fileGenerator->parseClassName($this->argument('name'));

        // 3) --force, --unit
        $force   = (bool)$this->option('force');
        $isUnit  = (bool)$this->option('unit');
        // Pest / PHPUnit
        $usingPest = $this->usingPest();

        // 4) makeFile => Trait method
        //    (className, subDirs, force, isUnit, usingPest)
        $this->makeFile($className, $subDirs, $force, $isUnit, $usingPest);

        return 0;
    }

    /**
     * Pest を使うかどうかを判定
     *
     * --phpunit が指定されれば false
     * --pest が指定されれば true
     * それ以外は pest がインストール＆ tests/Pest.php が存在すれば true
     */
    protected function usingPest(): bool
    {
        if ($this->option('phpunit')) {
            return false;
        }

        if ($this->option('pest')) {
            return true;
        }

        // pest がインストール済みかどうかを簡易チェック
        return function_exists('\Pest\version') && file_exists(base_path('tests/Pest.php'));
    }

    /**
     * (B)パターンで getTestDirectory/Namespace
     */
    protected function getTestDirectory(array $subDirs): string
    {
        // e.g. "plugins/MyPlugin/tests/Feature" or "plugins/MyPlugin/tests/Unit"
        // ここでは --unit かどうかを再判定し、"Unit" or "Feature" ディレクトリにしたい場合は
        // handle() 内で $isUnit → 変数にし、それをプロパティに保存しここで参照してもOK
        // 例: $this->testTypeDir = $isUnit ? 'Unit' : 'Feature';

        // ここではシンプルに既定 "tests/Feature" としておく例
        $plugin = Str::studly($this->argument('plugin'));
        return base_path("plugins/{$plugin}/tests/Feature");
    }

    protected function getTestNamespace(array $subDirs): string
    {
        // e.g. "Plugins\MyPlugin\Tests\Feature"
        // 同様に "Unit" にしたければ handle() からフラグを参照
        $plugin = Str::studly($this->argument('plugin'));
        return "Plugins\\{$plugin}\\Tests\\Feature";
    }
}
