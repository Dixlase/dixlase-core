<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Services\FileGenerator;
use App\Console\Traits\MakeEventTrait;

class MakePluginEvent extends Command
{
    use MakeEventTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:plugin:event
                            {plugin : The plugin name}
                            {name : The name of the event}
                            {--force : Create the class even if the event already exists}';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new event class for the specified plugin.';

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
        // 1) plugin名
        $pluginName = Str::studly($this->argument('plugin'));

        // 2) subDirs と className を parseClassName(...) で取得
        [$subDirs, $className] = $this->fileGenerator->parseClassName($this->argument('name'));

        // 3) --force
        $force = (bool) $this->option('force');

        // 4) MakeEventTrait の makeFile($className, $subDirs, $force) を呼ぶ
        $this->makeFile($className, $subDirs, $force);

        return 0;
    }

    /**
     * イベント用ディレクトリ/名前空間
     * -> (B)パターン: getDirectory($subDirs)/getNamespace($subDirs) で呼ばれる
     */
    protected function getEventDirectory(array $subDirs): string
    {
        $pluginName = Str::studly($this->argument('plugin'));
        $base = base_path("plugins/{$pluginName}/app/Events");
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getEventNamespace(array $subDirs): string
    {
        $pluginName = Str::studly($this->argument('plugin'));
        $base = "Plugins\\{$pluginName}\\App\\Events";
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
