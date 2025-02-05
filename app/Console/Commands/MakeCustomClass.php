<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FileGenerator;
use App\Console\Traits\MakeClassTrait;

class MakeCustomClass extends Command
{
    use MakeClassTrait;

    protected $signature = 'make:custom:class
        {name : The class name (optionally with subfolders, e.g. Admin/UtilityClass)}
        {--invokable : Generate an invokable class (__invoke())}
        {--force : Overwrite if the class already exists}';

    protected $description = 'Create a new generic class in the custom directory';

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

        // 2) --invokable
        $isInvokable = (bool)$this->option('invokable');

        // 3) --force
        $force = (bool)$this->option('force');

        // 4) trait method
        $this->makeFile($className, $subDirs, $force, $isInvokable);

        return 0;
    }

    /**
     * (B)パターン: getClassDirectory/Namespace
     */
    protected function getClassDirectory(array $subDirs): string
    {
        // 例: "custom/app/Classes" とか "custom/app/Services" とか、チーム規約に合わせて
        $base = base_path('custom/app/Classes');
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getClassNamespace(array $subDirs): string
    {
        $base = 'Custom\\App\\Classes';
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
