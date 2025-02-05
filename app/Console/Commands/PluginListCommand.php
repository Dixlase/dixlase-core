<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Plugin;


class PluginListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plugin:list';

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
        $plugins = Plugin::all();
        $this->table(['ID', 'Name', 'Status'], $plugins->toArray());
    }
}
