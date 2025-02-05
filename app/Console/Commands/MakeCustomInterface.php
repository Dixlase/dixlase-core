<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Services\FileGenerator;
use App\Console\Traits\MakeInterfaceTrait;

class MakeCustomInterface extends Command
{
    use MakeInterfaceTrait;

    protected $signature = 'make:custom:interface
        {name : The interface name (with optional subfolders, e.g. Admin/MyInterface)}
        {--force : Overwrite if the interface already exists}';

    protected $description = 'Create a new interface in the custom directory';

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
     * getInterfaceDirectory/Namespace
     */
    protected function getInterfaceDirectory(array $subDirs): string
    {
        $base = base_path('custom/contracts');
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getInterfaceNamespace(array $subDirs): string
    {
        $base = 'Custom\\Contracts';
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
