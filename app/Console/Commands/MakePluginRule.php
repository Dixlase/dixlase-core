<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Services\FileGenerator;
use App\Console\Traits\MakeRuleTrait;

class MakePluginRule extends Command
{
    use MakeRuleTrait;

    protected $signature = 'make:plugin:rule
        {plugin : The plugin name (e.g. MyPlugin)}
        {name : The rule class name (optionally with subfolders, e.g. Admin/MyCustomRule)}
        {--force : Overwrite if the rule class already exists}';

    protected $description = 'Create a new custom ValidationRule class in the specified plugin directory';

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
        $plugin = Str::studly($this->argument('plugin'));
        $this->pluginName = $plugin;

        // 2) parse subdirs + className
        [$subDirs, $className] = $this->fileGenerator->parseClassName($this->argument('name'));

        // 3) --force
        $force = (bool)$this->option('force');

        // 4) Trait method
        $this->makeFile($className, $subDirs, $force);

        return 0;
    }

    /**
     * (B)パターン: getRuleDirectory/Namespace
     */
    protected function getRuleDirectory(array $subDirs): string
    {
        // e.g. "plugins/MyPlugin/app/Rules"
        $base = base_path("plugins/{$this->pluginName}/app/Rules");
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getRuleNamespace(array $subDirs): string
    {
        // e.g. "Plugins\MyPlugin\App\Rules"
        $base = "Plugins\\{$this->pluginName}\\App\\Rules";
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
