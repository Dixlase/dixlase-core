<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Services\FileGenerator;
use App\Console\Traits\MakeObserverTrait;

class MakeCustomObserver extends Command
{
    use MakeObserverTrait;

    protected $signature = 'make:custom:observer
        {name : The observer class name (optionally with subfolders, e.g. Admin/UserObserver)}
        {--model= : The model that the observer applies to}
        {--force : Overwrite if the observer class already exists}';

    protected $description = 'Create a new Eloquent observer in the custom directory';

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

        // 2) --model
        $modelOption = $this->option('model');
        // 3) --force
        $force = (bool) $this->option('force');

        // 4) Trait method
        $this->makeFile($className, $subDirs, $force, $modelOption);

        return 0;
    }

    /**
     * (B)パターン: getObserverDirectory/Namespace
     */
    protected function getObserverDirectory(array $subDirs): string
    {
        $base = base_path('custom/app/Observers');
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getObserverNamespace(array $subDirs): string
    {
        $base = 'Custom\\App\\Observers';
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
