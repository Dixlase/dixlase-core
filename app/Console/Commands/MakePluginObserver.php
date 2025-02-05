<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Services\FileGenerator;
use App\Console\Traits\MakeObserverTrait;

class MakePluginObserver extends Command
{
    use MakeObserverTrait;

    protected $signature = 'make:plugin:observer
        {plugin : The plugin name (e.g. MyPlugin)}
        {name : The observer class name (optionally with subfolders, e.g. Admin/UserObserver)}
        {--model= : The model that the observer applies to}
        {--force : Overwrite if the observer already exists}';

    protected $description = 'Create a new Eloquent observer class in the specified plugin directory';

    protected FileGenerator $fileGenerator;

    protected string $pluginName;

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

        // 3) --model
        $modelOption = $this->option('model');
        // 4) --force
        $force = (bool) $this->option('force');

        // 5) stash pluginName if needed for getObserverDirectory
        $this->pluginName = $pluginName;

        // 6) Trait method
        $this->makeFile($className, $subDirs, $force, $modelOption);

        return 0;
    }

    /**
     * (B)パターン: getObserverDirectory/Namespace
     */
    protected function getObserverDirectory(array $subDirs): string
    {
        // e.g. "plugins/MyPlugin/app/Observers"
        $base = base_path("plugins/{$this->pluginName}/app/Observers");
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getObserverNamespace(array $subDirs): string
    {
        // e.g. "Plugins\MyPlugin\App\Observers"
        $base = "Plugins\\{$this->pluginName}\\App\\Observers";
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
