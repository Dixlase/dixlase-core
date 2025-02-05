<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Services\FileGenerator;
use App\Console\Traits\MakeApiResourceTrait;

class MakePluginApiResource extends Command
{
    use MakeApiResourceTrait;

    protected $signature = 'make:plugin:api-resource
        {plugin : The plugin name (e.g. "MyPlugin")}
        {name : The API resource class name (with optional subfolders, e.g. Admin/MyResource)}
        {--force : Overwrite if the file already exists}';

    protected $description = 'Create a new API resource class in the specified plugin directory';

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

        // 3) --force
        $force = (bool) $this->option('force');

        // 4) stash plugin name in a property so getApiResourceDirectory can use it
        $this->pluginName = $pluginName;

        // 5) Trait method
        $this->makeFile($className, $subDirs, $force);

        return 0;
    }

    /**
     * (B)パターン: getApiResourceDirectory/Namespace
     */
    protected function getApiResourceDirectory(array $subDirs): string
    {
        // e.g. plugins/MyPlugin/app/Http/Resources
        $base = base_path("plugins/{$this->pluginName}/app/Http/Resources");
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getApiResourceNamespace(array $subDirs): string
    {
        // e.g. Plugins\MyPlugin\App\Http\Resources
        $base = "Plugins\\{$this->pluginName}\\App\\Http\\Resources";
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
