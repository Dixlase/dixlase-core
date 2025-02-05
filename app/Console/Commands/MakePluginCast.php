<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Services\FileGenerator;
use App\Console\Traits\MakeCastTrait;

class MakePluginCast extends Command
{
    use MakeCastTrait;

    protected $signature = 'make:plugin:cast
        {plugin : The plugin name (e.g. MyPlugin)}
        {name : The cast class name (optionally with subfolders, e.g. Admin/MyCustomCast)}
        {--force : Overwrite if the cast class already exists}';

    protected $description = 'Create a new Eloquent cast (CastsAttributes) in the specified plugin directory';

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

        // 3) --force
        $force = (bool) $this->option('force');

        // stash pluginName if needed
        $this->pluginName = $pluginName;

        // 4) Trait method
        $this->makeFile($className, $subDirs, $force);

        return 0;
    }

    /**
     * (B)パターン: getCastDirectory/Namespace
     */
    protected function getCastDirectory(array $subDirs): string
    {
        // "plugins/MyPlugin/app/Casts"
        $base = base_path("plugins/{$this->pluginName}/app/Casts");
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getCastNamespace(array $subDirs): string
    {
        // "Plugins\MyPlugin\App\Casts"
        $base = "Plugins\\{$this->pluginName}\\App\\Casts";
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
