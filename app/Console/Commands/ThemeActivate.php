<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Theme;

class ThemeActivate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:theme:activate {themeName? : ' . 'command.theme_activate.theme_name_prompt' . '}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'command.theme_activate.description';

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
            $this->error(__('command.theme_activate.theme_not_found', ['themeName' => $themeName]));
            return Command::FAILURE;
        }

        // Check if theme is installed
        if (!$theme->isInstalled()) {
            $this->error(__('command.theme_activate.not_installed', ['themeName' => $theme->name]));
            $this->info(__('command.theme_activate.install_first', ['themeName' => $theme->name]));
            return Command::FAILURE;
        }

        // Get the current active theme
        $currentActive = Theme::whereNotNull('activated_at')->first();
        
        // Deactivate current theme if exists and it's different from the target
        if ($currentActive && $currentActive->id !== $theme->id) {
            $currentActive->update(['activated_at' => null]);
            $this->info(__('command.theme_activate.deactivated', ['themeName' => $currentActive->name]));
        } elseif ($currentActive && $currentActive->id === $theme->id) {
            $this->info(__('command.theme_activate.already_active', ['themeName' => $theme->name]));
            return Command::SUCCESS;
        }
        
        // Activate the new theme
        $theme->update(['activated_at' => now()]);
        $this->info(__('command.theme_activate.activated', ['themeName' => $theme->name]));
        
        return Command::SUCCESS;
    }

    /**
     * List all available themes
     *
     * @return int
     */
    protected function listThemes()
    {
        $themes = Theme::all(['name', 'slug', 'installed_at', 'activated_at']);
        
        if ($themes->isEmpty()) {
            $this->info(__('command.theme_activate.no_themes'));
            return Command::SUCCESS;
        }

        $this->table(
            __('command.theme_activate.list_headers'),
            $themes->map(function ($theme) {
                return [
                    $theme->name,
                    $theme->slug,
                    !is_null($theme->installed_at) 
                        ? '<fg=green>' . __('command.theme_activate.installed') . '</>' 
                        : '<fg=yellow>' . __('command.theme_activate.not_installed_status') . '</>', 
                    !is_null($theme->activated_at) 
                        ? '<fg=green>' . __('command.theme_activate.status_active') . '</>' 
                        : __('command.theme_activate.status_inactive')
                ];
            })
        );

        return Command::SUCCESS;
    }
}
