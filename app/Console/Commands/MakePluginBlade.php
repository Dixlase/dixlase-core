<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Console\Traits\MakeBladeTrait;

class MakePluginBlade extends Command
{
    use MakeBladeTrait;

    protected $signature = 'make:plugin:blade
        {plugin : The plugin name (e.g. MyPlugin)}
        {file : The blade file name (e.g. admin/dashboard)}
        {--force : Overwrite if the blade file already exists}';

    protected $description = 'Create a new Blade template in the specified plugin\'s resources/views directory';

    public function handle()
    {
        $plugin = Str::studly($this->argument('plugin'));
        $file   = $this->argument('file');
        $force  = (bool) $this->option('force');

        $this->pluginName = $plugin; // 後で getBladeBasePath() で使用

        $this->makeBlade($file, $force);

        return 0;
    }

    /**
     * プラグイン用 => "plugins/{Plugin}/resources/views"
     */
    protected function getBladeBasePath(): string
    {
        return base_path("plugins/{$this->pluginName}/resources/views");
    }
}
