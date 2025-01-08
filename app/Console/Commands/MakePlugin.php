<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use App\Models\Plugin;

class MakePlugin extends Command
{
    protected $signature = 'make:plugin {name} {--namespace=Vendor} {--install} {--enable}';
    protected $description = 'Create a new plugin with a predefined structure';

    public function handle()
    {
        $pluginName = $this->argument('name'); // 人間が認識する名前
        $namespace = $this->option('namespace') . '\\' . $pluginName;

        // ケバブケースでディレクトリ名を生成
        $pluginDirName = Str::kebab($pluginName);
        $pluginDir = base_path("plugins/{$pluginDirName}");

        if (File::exists($pluginDir)) {
            $this->error("The plugin '{$pluginName}' already exists.");
            return Command::FAILURE;
        }

        // Create directories and default files
        $this->createPluginDirectories($pluginDir, $pluginName, $namespace, $pluginDirName);

        // Optionally install and enable the plugin
        if ($this->option('install')) {
            $this->installPlugin($pluginName, $pluginDirName);

            if ($this->option('enable')) {
                $this->enablePlugin($pluginName);
            }
        }

        $this->info("Plugin {$pluginName} has been created successfully!");
        return Command::SUCCESS;
    }

    protected function createPluginDirectories(string $pluginDir, string $pluginName, string $namespace, string $pluginDirName)
    {
        $directories = [
            "app/Http",
            "app/Models",
            "app/Providers",
            "config",
            "routes",
            "resources/views",
            "test",            // テスト用ディレクトリ
            "lang/en",         // 英語用言語ファイル
            "lang/jp",         // 日本語用言語ファイル
            "migrations",      // マイグレーション用ディレクトリ
            "resources/assets/css",      // CSS用ディレクトリ
            "resources/assets/js",       // JavaScript用ディレクトリ
            "resources/assets/images",   // 画像用ディレクトリ
            "resources/src/js",          // ES6用ディレクトリ
            "resources/src/sass",        // Sass用ディレクトリ
            "resources/src/images",      // 画像用ディレクトリ
        ];

        foreach ($directories as $dir) {
            File::makeDirectory("{$pluginDir}/{$dir}", 0755, true);
        }

        // Create initial js and scss files
        File::put("{$pluginDir}/resources/src/js/app.js", "// JavaScript for {$pluginDirName}");
        File::put("{$pluginDir}/resources/src/scss/style.scss", "/* SCSS for {$pluginDirName} */");

        // Create vite.config.js
        $viteConfigContent = <<<JS
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'plugins/{$pluginDirName}/resources/src/js/app.js',
                'plugins/{$pluginDirName}/resources/src/scss/style.scss',
            ],
            refresh: true,
        }),
    ],
    build: {
        outDir: 'plugins/{$pluginDirName}/resources/assets',
    },
});
JS;
        File::put("{$pluginDir}/vite.config.js", $viteConfigContent);

        $composerContent = [
            'name' => "plugins/{$pluginName}",
            'description' => "This is the {$pluginName} plugin.",
            'author' => 'Your Name or Company',
            'email' => 'your-email@example.com',
            'web' => 'https://yourwebsite.com',
            'version' => '1.0.0',
            'license' => 'AGPL-3.0',
            'autoload' => [
                'psr-4' => [
                    "{$namespace}\\" => 'app/',
                ],
            ],
        ];

        File::put("{$pluginDir}/composer.json", json_encode($composerContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    protected function installPlugin($pluginName, $pluginDirName)
    {
        // Register plugin in database
        Plugin::create([
            'name' => $pluginName,
            'directory' => $pluginDirName, // ケバブケースのディレクトリ名を登録
            'namespace' => "Plugins\\$pluginDirName",
            'version' => '1.0.0',
            'status' => 0,
        ]);

        // Run migrations if exist
        $pluginMigrationPath = base_path("plugins/{$pluginDirName}/migrations");
        if (is_dir($pluginMigrationPath)) {
            Artisan::call('migrate', [
                '--path' => "plugins/{$pluginDirName}/migrations",
                '--force' => true,
            ]);
        }

        $this->info("Plugin {$pluginName} has been installed.");
    }

    protected function enablePlugin($pluginName)
    {
        // Enable the plugin
        $plugin = Plugin::where('name', $pluginName)->first();
        if ($plugin) {
            $plugin->update(['status' => 1]);
            $this->createPluginSymlink($plugin->directory);
            $this->info("Plugin '{$pluginName}' has been enabled.");
        } else {
            $this->error("Plugin '{$pluginName}' not found in the database.");
        }
    }

    protected function createPluginSymlink(string $pluginDirName)
    {
        $target = base_path("plugins/{$pluginDirName}/resources/assets");
        $link = public_path("assets/plugins/{$pluginDirName}");

        if (File::exists($target) && is_dir($target)) {
            if (File::exists($link) || is_link($link)) {
                unlink($link);
            }
            symlink($target, $link);
        } else {
            $this->warn("No assets directory found for plugin '{$pluginDirName}'.");
        }
    }
}
