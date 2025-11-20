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

        // composer.jsonから情報を読み取る
        $composerPath = "{$themeDir}/composer.json";
        $packageName = null;
        $namespace = null;
        $description = null;
        $license = null;
        $author = null;
        $email = null;
        $web = null;
        $version = '1.0.0';

        if (file_exists($composerPath)) {
            $composerData = json_decode(file_get_contents($composerPath), true);
            $packageName = $composerData['name'] ?? null;
            
            // namespaceは複数の場所から取得を試みる
            if (isset($composerData['autoload']['psr-4'])) {
                $namespace = array_key_first($composerData['autoload']['psr-4']);
                $namespace = rtrim($namespace, '\\'); // 末尾の\\を削除
            } else {
                $namespace = "Themes\\{$themeName}";
            }
            
            $description = $composerData['description'] ?? null;
            $license = $composerData['license'] ?? null;
            
            // authors配列から情報を取得
            if (isset($composerData['authors']) && is_array($composerData['authors']) && count($composerData['authors']) > 0) {
                $author = $composerData['authors'][0]['name'] ?? null;
                $email = $composerData['authors'][0]['email'] ?? null;
                $web = $composerData['authors'][0]['homepage'] ?? null;
            } else {
                // フォールバック: 直接指定されている場合
                $author = $composerData['author'] ?? null;
                $email = $composerData['email'] ?? null;
                $web = $composerData['web'] ?? null;
            }
            
            // versionはextra.dixlase.versionから取得、なければルートのもの
            $version = $composerData['extra']['dixlase']['version'] ?? $composerData['version'] ?? '1.0.0';
        }

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
