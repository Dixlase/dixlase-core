<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Theme;
use Illuminate\Support\Facades\File;
use App\Helpers\GitExcludeHelper;
use App\Helpers\ComposerLocalHelper;

class ThemeUninstall extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'theme:uninstall 
                            {themeName : ' . 'command.theme_uninstall.theme_name_prompt' . '}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'command.theme_uninstall.description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $themeName = $this->argument('themeName');
        
        // Find the theme
        $theme = Theme::where('name', $themeName)
                     ->orWhere('slug', $themeName)
                     ->first();

        if (!$theme) {
            $this->error(__('command.theme_uninstall.theme_not_found', ['themeName' => $themeName]));
            return Command::FAILURE;
        }

        // Check if theme is installed
        if (!$theme->isInstalled()) {
            $this->error(__('command.theme_uninstall.not_installed', ['themeName' => $theme->name]));
            return Command::FAILURE;
        }

        // Check if theme is active
        if ($theme->isActivated()) {
            $this->error(__('command.theme_uninstall.cannot_uninstall_active', ['themeName' => $theme->name]));
            $this->warn(__('command.theme_uninstall.deactivate_first'));
            return Command::FAILURE;
        }

        // Ask for confirmation
        if (!$this->confirm(__('command.theme_uninstall.confirmation', ['themeName' => $theme->name]))) {
            $this->info(__('command.theme_uninstall.cancelled'));
            return Command::SUCCESS;
        }

        // .git/info/excludeからテーマの除外ルールを削除
        GitExcludeHelper::removeThemeExclusion($theme->directory);
        $this->info("Removed {$theme->directory} from .git/info/exclude");

        // composer.local.jsonを更新
        ComposerLocalHelper::syncAutoload();
        $this->info("Updated composer.local.json");

        // Mark theme as uninstalled (but keep in database)
        $theme->update(['installed_at' => null]);
        $this->info(__('command.theme_uninstall.uninstalled', ['themeName' => $theme->name]));
        $this->info(__('command.theme_uninstall.files_preserved'));
        $this->info(__('command.theme_uninstall.delete_hint'));
        
        return Command::SUCCESS;
    }
}
