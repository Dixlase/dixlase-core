<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Services\FileGenerator;
use App\Console\Traits\MakeResourceCollectionTrait;

class MakePluginApiResourceCollection extends Command
{
    use MakeResourceCollectionTrait;

    protected $signature = 'make:plugin:api-resource-collection
        {plugin : The plugin name (e.g. MyPlugin)}
        {name : The ResourceCollection class name (e.g. Admin/MyResourceCollection)}
        {--force : Overwrite if the collection class already exists}';

    protected $description = 'Create a new ResourceCollection class in the specified plugin directory';

    protected FileGenerator $fileGenerator;
    protected string $pluginName;

    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    public function handle()
    {
        // 1) plugin
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
     * (B)パターン: getResourceCollectionDirectory/Namespace
     */
    protected function getResourceCollectionDirectory(array $subDirs): string
    {
        // e.g. "plugins/MyPlugin/app/Http/Resources"
        $base = base_path("plugins/{$this->pluginName}/app/Http/Resources");
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getResourceCollectionNamespace(array $subDirs): string
    {
        // e.g. "Plugins\MyPlugin\App\Http\Resources"
        $base = "Plugins\\{$this->pluginName}\\App\\Http\\Resources";
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
