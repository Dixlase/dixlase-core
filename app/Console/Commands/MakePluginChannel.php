<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Services\FileGenerator;
use App\Console\Traits\MakeChannelTrait;

class MakePluginChannel extends Command
{
    use MakeChannelTrait;

    protected $signature = 'make:plugin:channel
        {plugin : The plugin name (e.g. MyPlugin)}
        {name : The channel class name (optionally with subfolders, e.g. Admin/MyChannel)}
        {--force : Overwrite if the channel class already exists}';

    protected $description = 'Create a new broadcasting channel class in the specified plugin directory';

    protected FileGenerator $fileGenerator;

    protected string $pluginName;

    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    public function handle()
    {
        // 1) plugin name
        $this->pluginName = Str::studly($this->argument('plugin'));

        // 2) parse subDirs + className
        [$subDirs, $className] = $this->fileGenerator->parseClassName($this->argument('name'));

        // 3) --force
        $force = (bool)$this->option('force');

        // 4) trait method
        $this->makeFile($className, $subDirs, $force);

        return 0;
    }

    /**
     * (B)パターン: getChannelDirectory/Namespace
     */
    protected function getChannelDirectory(array $subDirs): string
    {
        // e.g. "plugins/MyPlugin/app/Broadcasting"
        $base = base_path("plugins/{$this->pluginName}/app/Broadcasting");
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getChannelNamespace(array $subDirs): string
    {
        // e.g. "Plugins\MyPlugin\App\Broadcasting"
        $base = "Plugins\\{$this->pluginName}\\App\\Broadcasting";
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
