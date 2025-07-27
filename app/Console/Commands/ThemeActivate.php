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
    protected $signature = 'theme:activate {themeName? : ' . 'command.theme_activate.theme_name_prompt' . '}';

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

        // Get the current active theme
        $currentActive = Theme::where('is_active', true)->first();
        
        // Deactivate current theme if exists and it's different from the target
        if ($currentActive && $currentActive->id !== $theme->id) {
            $currentActive->update(['is_active' => false]);
            $this->info(__('command.theme_activate.deactivated', ['themeName' => $currentActive->name]));
        } elseif ($currentActive && $currentActive->id === $theme->id) {
            $this->info(__('command.theme_activate.already_active', ['themeName' => $theme->name]));
            return Command::SUCCESS;
        }
        
        // Activate the new theme
        $theme->update(['is_active' => true]);
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
        $themes = Theme::all(['name', 'slug', 'is_active']);
        
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
                    $theme->is_active 
                        ? '<fg=green>' . __('command.theme_activate.status_active') . '</>' 
                        : __('command.theme_activate.status_inactive')
                ];
            })
        );

        return Command::SUCCESS;
    }
}
