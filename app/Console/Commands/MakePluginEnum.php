<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Services\FileGenerator;
use App\Console\Traits\MakeEnumTrait;

class MakePluginEnum extends Command
{
    use MakeEnumTrait;

    protected $signature = 'make:plugin:enum
        {plugin : The plugin name (e.g. MyPlugin)}
        {name : The enum class name (optionally with subfolders, e.g. Admin/MyEnum)}
        {--backed= : Create a backed enum with the given type (e.g. string or int)}
        {--force : Overwrite if the enum class already exists}';

    protected $description = 'Create a new enum class in the specified plugin directory (PHP 8.1+)';

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

        // 2) parse subdirs + className
        [$subDirs, $className] = $this->fileGenerator->parseClassName($this->argument('name'));

        // 3) --backed=?
        $backedType = $this->option('backed') ?: null;

        // 4) --force
        $force = (bool)$this->option('force');

        // 5) Trait method
        $this->makeFile($className, $subDirs, $force, $backedType);

        return 0;
    }

    /**
     * (B)パターン: getEnumDirectory/Namespace
     */
    protected function getEnumDirectory(array $subDirs): string
    {
        // "plugins/MyPlugin/app/Enums"
        $base = base_path("plugins/{$this->pluginName}/app/Enums");
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getEnumNamespace(array $subDirs): string
    {
        // "Plugins\MyPlugin\App\Enums"
        $base = "Plugins\\{$this->pluginName}\\App\\Enums";
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
