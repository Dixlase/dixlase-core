<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Theme;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Helpers\GitExcludeHelper;
use App\Helpers\ComposerLocalHelper;

class ThemeInstall extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'theme:install {themeName : ' . 'command.theme_install.theme_name_prompt' . '}';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'command.theme_install.description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $themeName = $this->argument('themeName');
        $themeDir = base_path("themes/" . Str::studly($themeName));
        
        // Check if theme directory exists
        if (!file_exists($themeDir)) {
            $this->error(__('command.theme_install.theme_not_found', ['themeName' => $themeName]));
            return Command::FAILURE;
        }

        // Check if theme is already registered
        if (Theme::where('slug', Str::slug($themeName))->exists()) {
            $this->error(__('command.theme_install.already_registered', ['themeName' => $themeName]));
            return Command::FAILURE;
        }

        // テーマ情報を読み取る（theme.json → composer.json → デフォルト値の順）
        $themeJsonPath = "{$themeDir}/theme.json";
        $composerPath = "{$themeDir}/composer.json";
        
        $packageName = null;
        $namespace = null;
        $description = null;
        $license = null;
        $author = null;
        $email = null;
        $web = null;
        $version = '1.0.0';

        // 1. theme.jsonから読み取り（最優先）
        if (file_exists($themeJsonPath)) {
            $themeData = json_decode(file_get_contents($themeJsonPath), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $packageName = $themeData['package_name'] ?? $themeData['name'] ?? null;
                $namespace = $themeData['namespace'] ?? null;
                $description = $themeData['description'] ?? null;
                // 多言語対応の場合は英語を優先
                if (is_array($description)) {
                    $description = $description['en'] ?? $description['ja'] ?? null;
                }
                $license = $themeData['license'] ?? null;
                $author = $themeData['author'] ?? null;
                $email = $themeData['email'] ?? null;
                $web = $themeData['url'] ?? $themeData['homepage'] ?? $themeData['web'] ?? null;
                $version = $themeData['version'] ?? '1.0.0';
            }
        }

        // 2. composer.jsonからフォールバック
        if (file_exists($composerPath)) {
            $composerData = json_decode(file_get_contents($composerPath), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $packageName = $packageName ?? $composerData['name'] ?? null;
                
                // namespaceはcomposer.jsonのautoloadから取得
                if (!$namespace && isset($composerData['autoload']['psr-4'])) {
                    $namespace = array_key_first($composerData['autoload']['psr-4']);
                    $namespace = rtrim($namespace, '\\');
                }
                
                $description = $description ?? $composerData['description'] ?? null;
                $license = $license ?? $composerData['license'] ?? null;
                
                // authors配列から情報を取得
                if (!$author && isset($composerData['authors']) && is_array($composerData['authors']) && count($composerData['authors']) > 0) {
                    $author = $composerData['authors'][0]['name'] ?? null;
                    $email = $email ?? $composerData['authors'][0]['email'] ?? null;
                    $web = $web ?? $composerData['authors'][0]['homepage'] ?? null;
                }
                
                // versionはextra.dixlase.versionから取得、なければルートのもの
                if ($version === '1.0.0') {
                    $version = $composerData['extra']['dixlase']['version'] ?? $composerData['version'] ?? '1.0.0';
                }
            }
        }

        // デフォルト値の設定
        $namespace = $namespace ?? "Themes\\{$themeName}";

        // Register the theme
        $theme = Theme::create([
            'name' => $themeName,
            'package_name' => $packageName,
            'directory' => $themeName,
            'slug' => Str::slug($themeName),
            'namespace' => $namespace,
            'description' => $description,
            'license' => $license,
            'author' => $author,
            'email' => $email,
            'web' => $web,
            'version' => $version,
            'installed_at' => now(),
        ]);

        // .git/info/excludeにテーマの除外ルールを追加
        GitExcludeHelper::addThemeExclusion($themeName);
        $this->info("Added {$themeName} to .git/info/exclude");

        // composer.local.jsonを更新
        ComposerLocalHelper::syncAutoload();
        $this->info("Updated composer.local.json");

        $this->info(__('command.theme_install.registered', ['themeName' => $themeName]));
        $this->info(__('command.theme_install.activate_help', ['themeName' => $themeName]));

        return Command::SUCCESS;
    }
}
