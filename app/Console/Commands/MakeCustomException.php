<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FileGenerator;
use App\Console\Traits\MakeExceptionTrait;

class MakeCustomException extends Command
{
    use MakeExceptionTrait;

    protected $signature = 'make:custom:exception
        {name : The exception class name (optionally with subfolders, e.g. Admin/MyCustomException)}
        {--force : Overwrite if the exception class already exists}';

    protected $description = 'Create a new custom Exception class in the custom directory';

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
        $force = (bool) $this->option('force');

        // 3) Trait method
        $this->makeFile($className, $subDirs, $force);

        return 0;
    }

    /**
     * (B)パターン: getExceptionDirectory/Namespace
     */
    protected function getExceptionDirectory(array $subDirs): string
    {
        $base = base_path('custom/app/Exceptions');
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getExceptionNamespace(array $subDirs): string
    {
        $base = 'Custom\\App\\Exceptions';
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
