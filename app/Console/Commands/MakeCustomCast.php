<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Services\FileGenerator;
use App\Console\Traits\MakeCastTrait;

class MakeCustomCast extends Command
{
    use MakeCastTrait;

    protected $signature = 'make:custom:cast
        {name : The cast class name (optionally with subfolders, e.g. Admin/MyCustomCast)}
        {--force : Overwrite if the cast class already exists}';

    protected $description = 'Create a new Eloquent cast (CastsAttributes) in the custom directory';

    protected FileGenerator $fileGenerator;

    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    public function handle()
    {
        // 1) parseClassName
        [$subDirs, $className] = $this->fileGenerator->parseClassName($this->argument('name'));

        // 2) --force
        $force = (bool)$this->option('force');

        // 3) Trait method
        $this->makeFile($className, $subDirs, $force);

        return 0;
    }

    /**
     * (B)パターン: getCastDirectory/Namespace
     */
    protected function getCastDirectory(array $subDirs): string
    {
        $base = base_path('custom/app/Casts');
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getCastNamespace(array $subDirs): string
    {
        $base = 'Custom\\App\\Casts';
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
