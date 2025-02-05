<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FileGenerator;
use App\Console\Traits\MakeInboundCastTrait;

class MakeCustomInboundCast extends Command
{
    use MakeInboundCastTrait;

    protected $signature = 'make:custom:inbound-cast
        {name : The inbound cast class name (optionally with subfolders, e.g. Admin/MyInboundCast)}
        {--force : Overwrite if the cast already exists}';

    protected $description = 'Create a new inbound cast (CastsInboundAttributes) in the custom directory';

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
     * (B)パターン: getInboundCastDirectory/Namespace
     */
    protected function getInboundCastDirectory(array $subDirs): string
    {
        $base = base_path('custom/app/Casts/Inbound');
        // 例: inbound 専用にサブフォルダを分けてもよいし、
        //     'custom/app/Casts' 下にまとめてもOK
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getInboundCastNamespace(array $subDirs): string
    {
        $base = 'Custom\\App\\Casts\\Inbound';
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
