<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Theme;

class ThemeDisable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:theme:disable {themeName? : ' . 'command.theme_disable.theme_name_prompt' . '}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'command.theme_disable.description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $themeName = $this->argument('themeName');
        
        // If no theme name provided, show list of enabled themes
        if (!$themeName) {
            return $this->listEnabledThemes();
        }

        // Find the theme
        $theme = Theme::where('name', $themeName)
                     ->orWhere('slug', $themeName)
                     ->first();

        if (!$theme) {
            $this->error(__('command.theme_disable.theme_not_found', ['themeName' => $themeName]));
            return Command::FAILURE;
        }

        // Check if theme is installed
        if (!$theme->isInstalled()) {
            $this->error(__('command.theme_disable.not_installed', ['themeName' => $theme->name]));
            return Command::FAILURE;
        }

        // Check if the theme is already disabled
        if (!$theme->isEnabled()) {
            $this->info(__('command.theme_disable.already_disabled', ['themeName' => $theme->name]));
            return Command::SUCCESS;
        }

        // Disable the theme
        $theme->update(['enabled_at' => null]);
        $this->info(__('command.theme_disable.disabled', ['themeName' => $theme->name]));
        
        return Command::SUCCESS;
    }

    /**
     * List all enabled themes
     *
     * @return int
     */
    protected function listEnabledThemes()
    {
        $enabledThemes = Theme::whereNotNull('enabled_at')
                           ->whereNotNull('installed_at')
                           ->get(['name', 'slug']);
        
        if ($enabledThemes->isEmpty()) {
            $this->info(__('command.theme_disable.no_enabled_themes'));
            return Command::SUCCESS;
        }

        $this->table(
            __('command.theme_disable.list_headers'),
            $enabledThemes->map(function ($theme) {
                return [
                    $theme->name,
                    $theme->slug,
                ];
            })
        );

        $this->info("\n" . __('command.theme_disable.disable_help'));
        return Command::SUCCESS;
    }
}
