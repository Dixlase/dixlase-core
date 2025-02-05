<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FileGenerator;
use App\Console\Traits\MakeRuleTrait;

class MakeCustomRule extends Command
{
    use MakeRuleTrait;

    protected $signature = 'make:custom:rule
        {name : The rule class name (optionally with subfolders, e.g. Admin/MyCustomRule)}
        {--force : Overwrite if the rule class already exists}';

    protected $description = 'Create a new custom ValidationRule class in the custom directory';

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

        // 2) --force
        $force = (bool)$this->option('force');

        // 3) Trait method
        $this->makeFile($className, $subDirs, $force);

        return 0;
    }

    /**
     * (B)パターン: getRuleDirectory/Namespace
     */
    protected function getRuleDirectory(array $subDirs): string
    {
        $base = base_path('custom/app/Rules');
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getRuleNamespace(array $subDirs): string
    {
        $base = 'Custom\\App\\Rules';
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
