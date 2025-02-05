<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FileGenerator;
use App\Console\Traits\MakeScopeTrait;

class MakeCustomScope extends Command
{
    use MakeScopeTrait;

    protected $signature = 'make:custom:scope
        {name : The scope class name (optionally with subfolders, e.g. Admin/ApprovedScope)}
        {--force : Overwrite if the scope already exists}';

    protected $description = 'Create a new Eloquent Global Scope class in the custom directory';

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

        // 3) Trait method
        $this->makeFile($className, $subDirs, $force);

        return 0;
    }

    /**
     * (B)パターン: getScopeDirectory/Namespace
     */
    protected function getScopeDirectory(array $subDirs): string
    {
        $base = base_path('custom/app/Scopes');
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getScopeNamespace(array $subDirs): string
    {
        $base = 'Custom\\App\\Scopes';
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
