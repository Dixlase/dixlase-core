<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FileGenerator;
use App\Console\Traits\MakeResourceCollectionTrait;

class MakeCustomApiResourceCollection extends Command
{
    use MakeResourceCollectionTrait;

    protected $signature = 'make:custom:api-resource-collection
        {name : The ResourceCollection class name (optionally with subfolders, e.g. Admin/MyResourceCollection)}
        {--force : Overwrite if the collection class already exists}';

    protected $description = 'Create a new ResourceCollection class in the custom directory';

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
     * (B)パターン: getResourceCollectionDirectory/Namespace
     */
    protected function getResourceCollectionDirectory(array $subDirs): string
    {
        $base = base_path('custom/app/Http/Resources');
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getResourceCollectionNamespace(array $subDirs): string
    {
        $base = 'Custom\\App\\Http\\Resources';
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
