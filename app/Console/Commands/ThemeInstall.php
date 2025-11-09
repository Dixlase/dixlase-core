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

        // Register the theme
        $theme = Theme::create([
            'name' => $themeName,
            'slug' => Str::slug($themeName),
            'directory' => $themeName,
            'version' => '1.0.0',
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
