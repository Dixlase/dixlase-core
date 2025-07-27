<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Theme;

class ThemeDeactivate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'theme:deactivate {themeName? : ' . 'command.theme_deactivate.theme_name_prompt' . '}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'command.theme_deactivate.description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $themeName = $this->argument('themeName');
        
        // If no theme name provided, show list of active themes
        if (!$themeName) {
            return $this->listActiveThemes();
        }

        // Find the theme
        $theme = Theme::where('name', $themeName)
                     ->orWhere('slug', $themeName)
                     ->first();

        if (!$theme) {
            $this->error(__('command.theme_deactivate.theme_not_found', ['themeName' => $themeName]));
            return Command::FAILURE;
        }

        // Check if the theme is already inactive
        if (!$theme->is_active) {
            $this->info(__('command.theme_deactivate.already_inactive', ['themeName' => $theme->name]));
            return Command::SUCCESS;
        }

        // Deactivate the theme
        $theme->update(['is_active' => false]);
        $this->info(__('command.theme_deactivate.deactivated', ['themeName' => $theme->name]));
        
        return Command::SUCCESS;
    }

    /**
     * List all active themes
     *
     * @return int
     */
    protected function listActiveThemes()
    {
        $activeThemes = Theme::where('is_active', true)
                           ->get(['name', 'slug']);
        
        if ($activeThemes->isEmpty()) {
            $this->info(__('command.theme_deactivate.no_active_themes'));
            return Command::SUCCESS;
        }

        $this->table(
            __('command.theme_deactivate.list_headers'),
            $activeThemes->map(function ($theme) {
                return [
                    $theme->name,
                    $theme->slug,
                ];
            })
        );

        $this->info("\n" . __('command.theme_deactivate.deactivate_help'));
        return Command::SUCCESS;
    }
}
