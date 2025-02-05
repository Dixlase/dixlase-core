<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Services\FileGenerator;
use App\Console\Traits\MakeApiResourceTrait;

class MakeCustomApiResource extends Command
{
    use MakeApiResourceTrait;

    protected $signature = 'make:custom:api-resource
        {name : The API resource class name (with optional subfolders, e.g. Admin/MyResource)}
        {--force : Overwrite if the file already exists}';

    protected $description = 'Create a new API resource in the custom directory';

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
     * (B)パターン: getApiResourceDirectory/Namespace
     */
    protected function getApiResourceDirectory(array $subDirs): string
    {
        $base = base_path('custom/http/resources');
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getApiResourceNamespace(array $subDirs): string
    {
        $base = 'Custom\\Http\\Resources';
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
