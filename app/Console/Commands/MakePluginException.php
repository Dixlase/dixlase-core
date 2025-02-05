<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Services\FileGenerator;
use App\Console\Traits\MakeExceptionTrait;

class MakePluginException extends Command
{
    use MakeExceptionTrait;

    protected $signature = 'make:plugin:exception
        {plugin : The plugin name (e.g. MyPlugin)}
        {name : The exception class name (optionally with subfolders, e.g. Admin/MyCustomException)}
        {--force : Overwrite if the exception class already exists}';

    protected $description = 'Create a new custom Exception class in the specified plugin directory';

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
        $pluginName = Str::studly($this->argument('plugin'));
        $this->pluginName = $pluginName;

        // 2) parse subDirs + className
        [$subDirs, $className] = $this->fileGenerator->parseClassName($this->argument('name'));

        // 3) --force
        $force = (bool) $this->option('force');

        // 4) Trait method
        $this->makeFile($className, $subDirs, $force);

        return 0;
    }

    /**
     * (B)パターン: getExceptionDirectory/Namespace
     */
    protected function getExceptionDirectory(array $subDirs): string
    {
        // e.g. plugins/MyPlugin/app/Exceptions
        $base = base_path("plugins/{$this->pluginName}/app/Exceptions");
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getExceptionNamespace(array $subDirs): string
    {
        // e.g. Plugins\MyPlugin\App\Exceptions
        $base = "Plugins\\{$this->pluginName}\\App\\Exceptions";
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
