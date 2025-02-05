<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FileGenerator;
use App\Console\Traits\MakeBladeTrait;

class MakeBlade extends Command
{
    use MakeBladeTrait;

    protected $signature = 'make:blade
        {file : The blade file name (with optional subdirectories, e.g. admin/dashboard)}
        {--force : Overwrite if the blade file already exists}';

    protected $description = 'Create a new Blade template in the core resources/views directory';

    public function handle()
    {
        $file   = $this->argument('file');
        $force  = (bool) $this->option('force');

        $this->makeBlade($file, $force);

        return 0;
    }

    /**
     * コア用 => resources/views
     */
    protected function getBladeBasePath(): string
    {
        return resource_path('views');
    }
}
