<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Console\Traits\MakeBladeTrait;

class MakeCustomBlade extends Command
{
    use MakeBladeTrait;

    protected $signature = 'make:custom:blade
        {file : The blade file name (e.g. admin/dashboard)}
        {--force : Overwrite if the blade file already exists}';

    protected $description = 'Create a new Blade template in the custom/views directory';

    public function handle()
    {
        $file  = $this->argument('file');
        $force = (bool) $this->option('force');

        $this->makeBlade($file, $force);

        return 0;
    }

    /**
     * カスタム用 => custom/views
     */
    protected function getBladeBasePath(): string
    {
        return base_path('custom/views');
    }
}
