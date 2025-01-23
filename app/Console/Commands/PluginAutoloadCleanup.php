<?php

namespace App\Console\Commands;


use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PluginAutoloadCleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plugin:autoload:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove non-existent plugin directories from composer.json autoload.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $composerJsonPath = base_path('composer.json');
        $composerConfig = json_decode(file_get_contents($composerJsonPath), true);

        $psr4 = $composerConfig['autoload']['psr-4'] ?? [];

        // 現存するプラグイン (ディレクトリ名) を取得
        $pluginDirectories = glob(base_path('plugins/*'), GLOB_ONLYDIR);
        $existingPlugins = array_map('basename', $pluginDirectories); // ["MyPlugin","SomeOtherPlugin"]

        $hasChanges = false;

        foreach ($psr4 as $namespace => $path) {
            // "Plugins\MyPlugin\..." に該当するかチェック
            if (preg_match('/^Plugins\\\\([A-Za-z0-9_]+)\\\\(.*)/', $namespace, $matches)) {
                $foundPluginName = $matches[1];

                // plugins/内に該当ディレクトリがない -> 削除
                if (!in_array($foundPluginName, $existingPlugins)) {
                    unset($psr4[$namespace]);
                    $hasChanges = true;
                } else {
                    // ディレクトリ名はあるが、subDir も本当にあるか？
                    $fullPath = base_path($path);
                    if (!is_dir($fullPath)) {
                        unset($psr4[$namespace]);
                        $hasChanges = true;
                    }
                }
            }
        }

        if ($hasChanges) {
            $composerConfig['autoload']['psr-4'] = $psr4;

            file_put_contents(
                $composerJsonPath,
                json_encode($composerConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );

            $this->info('Removed non-existent plugin directories from composer.json autoload.');
        } else {
            $this->info('No obsolete plugin directories found; no changes made.');
        }

        return Command::SUCCESS;
    }
}
