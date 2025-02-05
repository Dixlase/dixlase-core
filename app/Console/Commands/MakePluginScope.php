<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Services\FileGenerator;
use App\Console\Traits\MakeScopeTrait;

class MakePluginScope extends Command
{
    use MakeScopeTrait;

    protected $signature = 'make:plugin:scope
        {plugin : The plugin name (e.g. MyPlugin)}
        {name : The scope class name (e.g. Admin/ApprovedScope)}
        {--force : Overwrite if the scope already exists}';

    protected $description = 'Create a new Eloquent Global Scope class in the specified plugin directory';

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

        // 4) Trait method
        $this->makeFile($className, $subDirs, $force);

        return 0;
    }

    /**
     * (B)パターン: getScopeDirectory/Namespace
     */
    protected function getScopeDirectory(array $subDirs): string
    {
        // "plugins/MyPlugin/app/Scopes"
        $base = base_path("plugins/{$this->pluginName}/app/Scopes");
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getScopeNamespace(array $subDirs): string
    {
        // "Plugins\MyPlugin\App\Scopes"
        $base = "Plugins\\{$this->pluginName}\\App\\Scopes";
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
