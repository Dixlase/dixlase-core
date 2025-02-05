<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FileGenerator;
use App\Console\Traits\MakeEnumTrait;

class MakeCustomEnum extends Command
{
    use MakeEnumTrait;

    protected $signature = 'make:custom:enum
        {name : The enum class name (optionally with subfolders, e.g. Admin/MyEnum)}
        {--backed= : Create a backed enum with the given type (e.g. string or int)}
        {--force : Overwrite if the enum class already exists}';

    protected $description = 'Create a new enum class in the custom directory (PHP 8.1+)';

    protected FileGenerator $fileGenerator;

    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    public function handle()
    {
        // 1) parse subdirs + className
        [$subDirs, $className] = $this->fileGenerator->parseClassName($this->argument('name'));

        // 2) --backed=?
        $backedType = $this->option('backed') ?: null;

        // 3) --force
        $force = (bool)$this->option('force');

        // 4) Trait method
        $this->makeFile($className, $subDirs, $force, $backedType);

        return 0;
    }

    /**
     * (B)パターン: getEnumDirectory/Namespace
     */
    protected function getEnumDirectory(array $subDirs): string
    {
        $base = base_path('custom/app/Enums');
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getEnumNamespace(array $subDirs): string
    {
        $base = 'Custom\\App\\Enums';
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
