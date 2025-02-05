<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FileGenerator;
use App\Console\Traits\MakeChannelTrait;

class MakeCustomChannel extends Command
{
    use MakeChannelTrait;

    protected $signature = 'make:custom:channel
        {name : The channel class name (optionally with subfolders, e.g. Admin/MyChannel)}
        {--force : Overwrite if the channel class already exists}';

    protected $description = 'Create a new broadcasting channel class in the custom directory';

    protected FileGenerator $fileGenerator;

    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    public function handle()
    {
        // 1) parse subDirs + className
        [$subDirs, $className] = $this->fileGenerator->parseClassName($this->argument('name'));

        // 2) --force
        $force = (bool)$this->option('force');

        // 3) trait method
        $this->makeFile($className, $subDirs, $force);

        return 0;
    }

    /**
     * (B)パターン: getChannelDirectory/Namespace
     */
    protected function getChannelDirectory(array $subDirs): string
    {
        $base = base_path('custom/app/Broadcasting');
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getChannelNamespace(array $subDirs): string
    {
        $base = 'Custom\\App\\Broadcasting';
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
