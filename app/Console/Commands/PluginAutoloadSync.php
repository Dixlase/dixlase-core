<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PluginAutoloadSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plugin:autoload:sync {--cleanup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize plugins with composer.json PSR-4 settings (and optionally clean up).';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1. 同期 (PSR-4に新規プラグインを追加)
        $this->call('plugin:autoload:sync-only'); // syncPluginAutoload()

        // 2. --cleanup が指定されていれば、不要エントリ削除
        if ($this->option('cleanup')) {
            $this->call('plugin:autoload:cleanup'); // cleanupPluginAutoload()
        }

        // 3. 最後に composer dump-autoload
        exec('composer dump-autoload');

        $this->info('Composer autoload has been updated (plugins synced).');
        return Command::SUCCESS;
    }
}
