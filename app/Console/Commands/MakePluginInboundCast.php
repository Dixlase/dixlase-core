<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Services\FileGenerator;
use App\Console\Traits\MakeInboundCastTrait;

class MakePluginInboundCast extends Command
{
    use MakeInboundCastTrait;

    protected $signature = 'make:plugin:inbound-cast
        {plugin : The plugin name (e.g. MyPlugin)}
        {name : The inbound cast class name (e.g. Admin/MyInboundCast)}
        {--force : Overwrite if the cast already exists}';

    protected $description = 'Create a new inbound cast (CastsInboundAttributes) in the specified plugin directory';

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
     * (B)パターン: getInboundCastDirectory/Namespace
     */
    protected function getInboundCastDirectory(array $subDirs): string
    {
        // e.g. "plugins/MyPlugin/app/Casts/Inbound"
        $base = base_path("plugins/{$this->pluginName}/app/Casts/Inbound");
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getInboundCastNamespace(array $subDirs): string
    {
        // e.g. "Plugins\MyPlugin\App\Casts\Inbound"
        $base = "Plugins\\{$this->pluginName}\\App\\Casts\\Inbound";
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
