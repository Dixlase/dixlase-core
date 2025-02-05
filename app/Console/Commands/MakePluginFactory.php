<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Services\FileGenerator;
use App\Console\Traits\MakeFactoryTrait;

class MakePluginFactory extends Command
{
    use MakeFactoryTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:plugin:factory
                            {plugin : The name of the plugin (e.g. "EventsPlugin")}
                            {name : The name of the factory class (e.g. "EventFactory" or just "Event")}
                            {--model= : The name of the model (FQCN or relative) for this factory}
                            {--force : Overwrite the factory if it already exists}';
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

    public function handle()
    {
        $plugin    = Str::studly($this->argument('plugin'));
        $className = $this->argument('name');
        if (! Str::endsWith($className, 'Factory')) {
            $className .= 'Factory';
        }

        $model = $this->option('model');
        $force = (bool) $this->option('force');

        // ファクトリ作成
        $this->makeFile($className, $model, $force);

        return 0;
    }

    /**
     * サブクラスで実装: getFactoryDirectory(), getFactoryNamespace()
     */
    protected function getFactoryDirectory(): string
    {
        $pluginName = Str::studly($this->argument('plugin'));
        return base_path("plugins/{$pluginName}/database/factories");
    }

    protected function getFactoryNamespace(): string
    {
        $pluginName = Str::studly($this->argument('plugin'));
        return "Plugins\\{$pluginName}\\Database\\Factories";
    }
}
