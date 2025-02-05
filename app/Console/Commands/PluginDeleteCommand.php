<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PluginDeleteCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plugin:delete {name}';

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
        $pluginDir = base_path("plugins/{$pluginName}");

        if (!File::exists($pluginDir)) {
            $this->error("Plugin '{$pluginName}' does not exist.");
            return Command::FAILURE;
        }

        File::deleteDirectory($pluginDir);
        $this->info("Plugin '{$pluginName}' deleted successfully.");
        return Command::SUCCESS;
    }
}
