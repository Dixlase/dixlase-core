<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PluginCacheClearCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plugin:cache:clear {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $pluginName = $this->argument('name');
        cache()->forget("plugin_{$pluginName}_cache");
        $this->info("Cache for plugin '{$pluginName}' has been cleared.");
    }
}
