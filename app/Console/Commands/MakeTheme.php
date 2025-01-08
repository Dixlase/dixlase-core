<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use App\Models\Theme;
use Illuminate\Support\Facades\DB;

class MakeTheme extends Command
{
    protected $signature = 'make:theme {name} {--install} {--activate}';
    protected $description = 'Create a new theme, optionally register it in the database and activate it';

    public function handle()
    {
        $originalName = $this->argument('name');
        $themeName = $this->sanitizeThemeName($originalName);
        $directory = base_path("themes/{$themeName}");

        if (File::exists($directory)) {
            $this->error("Theme '{$themeName}' already exists.");
            return Command::FAILURE;
        }

        // テーマディレクトリの作成
        $this->createThemeDirectories($directory, $themeName);

        // テーマをデータベースに登録
        if ($this->option('install')) {
            $themeId = $this->registerThemeInDatabase($originalName, $themeName);

            // テーマを有効化
            if ($this->option('activate')) {
                $this->activateTheme($themeId, $themeName);
            }
        }

        $this->info("Theme '{$themeName}' has been created successfully.");
        return Command::SUCCESS;
    }

    /**
     * テーマ名を正規化
     *
     * @param string $name
     * @return string
     */
    protected function sanitizeThemeName(string $name): string
    {
        $name = str_replace(' ', '-', $name);
        $name = preg_replace('/[^A-Za-z0-9-_]/', '', $name);
        return strtolower($name);
    }

    /**
     * テーマディレクトリと初期ファイルを作成
     *
     * @param string $directory
     * @param string $themeName
     */
    protected function createThemeDirectories(string $directory, string $themeName)
    {
        $directories = [
            'resources/views',
            'resources/src/js',
            'resources/src/scss',
            'resources/assets/js',
            'resources/assets/css',
            'resources/assets/images',
        ];
        // ルートディレクトリの作成
        File::makeDirectory($directory, 0755, true);

        // サブディレクトリの作成
        foreach ($directories as $dir) {
            File::makeDirectory("{$directory}/{$dir}", 0755, true);
        }

        // Create initial files
        File::put("{$directory}/resources/views/index.blade.php", '<h1>Welcome to ' . $themeName . '</h1>');
        File::put("{$directory}/resources/src/scss/style.css", "/* {$themeName} theme styles */");
        File::put("{$directory}/resources/src/js/script.js", "// {$themeName} theme scripts");

        // Create vite.config.js
        $viteConfigContent = <<<JS
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'themes/{$themeName}/resources/src/js/app.js',
                'themes/{$themeName}/resources/src/scss/style.scss',
            ],
            refresh: true,
        }),
    ],
    build: {
        outDir: 'themes/{$themeName}/resources/assets',
    },
});
JS;
        File::put("{$directory}/vite.config.js", $viteConfigContent);

        // Create composer.json
        File::put("{$directory}/composer.json", json_encode([
            'name' => $themeName,
            'directory' => $themeName,
            'description' => "A new theme named {$themeName}.",
            'author' => 'Your Name or Company',
            'email' => 'your-email@example.com',
            'web' => 'https://yourwebsite.com',
            'version' => '1.0.0',
            'license' => 'AGPL-3.0',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * テーマをデータベースに登録
     *
     * @param string $originalName
     * @param string $themeName
     * @return int $themeId
     */
    protected function registerThemeInDatabase(string $originalName, string $themeName)
    {
        if (Theme::where('slug', $themeName)->exists()) {
            $this->error("Theme '{$themeName}' is already registered in the database.");
            return Command::FAILURE;
        }

        $theme = Theme::create([
            'name' => $originalName,
            'slug' => $themeName,
            'directory' => $themeName,
            'version' => '1.0.0',
        ]);

        $this->info("Theme '{$themeName}' has been registered in the database.");
        return $theme->id;
    }

    /**
     * テーマを有効化
     *
     * @param int $themeId
     */
    protected function activateTheme(int $themeId, string $themeName)
    {
        DB::table('settings_theme')->updateOrInsert(
            ['id' => 1], // 一意の設定
            ['active_theme_id' => $themeId, 'updated_at' => now()]
        );

        $themeAssetsDir = base_path("themes/{$themeName}/resources/assets");
        $linkDir = public_path("assets/theme");

        // シンボリックリンクの作成
        if (File::exists($themeAssetsDir) && is_dir($themeAssetsDir)) {
            if (File::exists($linkDir) || is_link($linkDir)) {
                unlink($linkDir); // 古いリンクがある場合は削除
            }

            symlink($themeAssetsDir, $linkDir);
            $this->info("Symlink created: {$linkDir} -> {$themeAssetsDir}");
        } else {
            $this->warn("Assets directory does not exist for theme: {$themeName}");
        }

        $this->info("Theme ID '{$themeId}' has been activated.");
    }
}
