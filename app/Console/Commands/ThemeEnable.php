<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Theme;

class ThemeEnable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:theme:enable {themeName? : ' . 'command.theme_enable.theme_name_prompt' . '}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'command.theme_enable.description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $themeName = $this->argument('themeName');
        
        // If no theme name provided, show list of available themes
        if (!$themeName) {
            return $this->listThemes();
        }

        // Find the theme
        $theme = Theme::where('name', $themeName)
                     ->orWhere('slug', $themeName)
                     ->first();

        if (!$theme) {
            $this->error(__('command.theme_enable.theme_not_found', ['themeName' => $themeName]));
            return Command::FAILURE;
        }

        // Check if theme is installed
        if (!$theme->isInstalled()) {
            $this->error(__('command.theme_enable.not_installed', ['themeName' => $theme->name]));
            $this->info(__('command.theme_enable.install_first', ['themeName' => $theme->name]));
            return Command::FAILURE;
        }

        // Get the current enabled theme
        $currentEnabled = Theme::whereNotNull('enabled_at')->first();
        
        // Disable current theme if exists and it's different from the target
        if ($currentEnabled && $currentEnabled->id !== $theme->id) {
            $currentEnabled->update(['enabled_at' => null]);
            $this->info(__('command.theme_enable.disabled', ['themeName' => $currentEnabled->name]));
        } elseif ($currentEnabled && $currentEnabled->id === $theme->id) {
            $this->info(__('command.theme_enable.already_enabled', ['themeName' => $theme->name]));
            return Command::SUCCESS;
        }
        
        // Enable the new theme
        $theme->update(['enabled_at' => now()]);
        $this->info(__('command.theme_enable.enabled', ['themeName' => $theme->name]));
        
        return Command::SUCCESS;
    }

    /**
     * List all available themes
     *
     * @return int
     */
    protected function listThemes()
    {
        $themes = Theme::all(['name', 'slug', 'installed_at', 'enabled_at']);
        
        if ($themes->isEmpty()) {
            $this->info(__('command.theme_enable.no_themes'));
            return Command::SUCCESS;
        }

        $this->table(
            __('command.theme_enable.list_headers'),
            $themes->map(function ($theme) {
                return [
                    $theme->name,
                    $theme->slug,
                    !is_null($theme->installed_at) 
                        ? '<fg=green>' . __('command.theme_enable.installed') . '</>' 
                        : '<fg=yellow>' . __('command.theme_enable.not_installed_status') . '</>', 
                    !is_null($theme->enabled_at) 
                        ? '<fg=green>' . __('command.theme_enable.status_enabled') . '</>' 
                        : __('command.theme_enable.status_disabled')
                ];
            })
        );

        return Command::SUCCESS;
    }
}
