<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Services\FileGenerator;
use App\Console\Traits\MakeConfigTrait;

class MakePluginConfig extends Command
{
    use MakeConfigTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:plugin:config
        {plugin : The plugin name}
        {name : The name of the config file}
        {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new configuration file for a plugin';

    protected FileGenerator $fileGenerator;

    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $plugin    = Str::studly($this->argument('plugin'));
        $className = Str::snake($this->argument('name'));

        $options = [
            'force' => $this->option('force'),
        ];

        $this->makeFile($className, [], $options);

        return 0;
    }

    /**
     * @override from MakeFileTrait
     */
    protected function getDirectory(array $subDirs): string
    {
        $pluginName = Str::studly($this->argument('plugin'));
        return base_path("plugins/{$pluginName}/config");
    }

    /**
     * @override from MakeFileTrait
     */
    protected function getNamespace(array $subDirs): string
    {
        return ''; // コンフィグにはネームスペースは不要
    }
}
