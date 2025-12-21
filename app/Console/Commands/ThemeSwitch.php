<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Theme;
use Illuminate\Support\Facades\DB;

class ThemeSwitch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:theme:switch {themeName? : ' . 'command.theme_switch.theme_name_prompt' . '}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'command.theme_switch.description';

    /**
     * Alias for backward compatibility
     */
    protected $aliases = ['dls:theme:enable'];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $themeName = $this->argument('themeName');
        
        // If no theme name provided, show interactive choice
        if (!$themeName) {
            return $this->interactiveSwitch();
        }

        // Find the theme
        $theme = Theme::where('name', $themeName)
                     ->orWhere('slug', $themeName)
                     ->first();

        if (!$theme) {
            $this->error(__('admin/command.theme_switch.theme_not_found', ['themeName' => $themeName]));
            return Command::FAILURE;
        }

        // Check if theme is installed
        if (!$theme->isInstalled()) {
            $this->error(__('admin/command.theme_switch.not_installed', ['themeName' => $theme->name]));
            $this->info(__('admin/command.theme_switch.install_first', ['themeName' => $theme->name]));
            return Command::FAILURE;
        }

        return $this->switchTheme($theme);
    }

    /**
     * Switch to the specified theme
     *
     * @param Theme $theme
     * @return int
     */
    protected function switchTheme(Theme $theme)
    {
        // Get the current enabled theme ID from theme_settings
        $currentSetting = DB::table('theme_settings')
            ->where('key', 'enabled_theme_id')
            ->first();
        
        $currentThemeId = $currentSetting ? $currentSetting->value : null;
        
        // Check if already enabled
        if ($currentThemeId && $currentThemeId == $theme->id) {
            $this->info(__('admin/command.theme_switch.already_enabled', ['themeName' => $theme->name]));
            return Command::SUCCESS;
        }
        
        // Get current theme for display message
        if ($currentThemeId) {
            $currentTheme = Theme::find($currentThemeId);
            if ($currentTheme) {
                $this->info(__('admin/command.theme_switch.disabled', ['themeName' => $currentTheme->name]));
            }
        }
        
        // Update or create the enabled_theme_id setting
        DB::table('theme_settings')
            ->updateOrInsert(
                ['key' => 'enabled_theme_id'],
                ['value' => $theme->id, 'updated_at' => now()]
            );
        
        $this->info(__('admin/command.theme_switch.switched', ['themeName' => $theme->name]));
        
        // Update symlink
        try {
            \Artisan::call('dls:theme:symlink', [
                'action' => 'create',
                'theme' => $theme->directory
            ]);
        } catch (\Exception $e) {
            $this->warn(__('admin/command.theme_switch.symlink_warning'));
        }
        
        return Command::SUCCESS;
    }

    /**
     * Interactive theme selection
     *
     * @return int
     */
    protected function interactiveSwitch()
    {
        $themes = Theme::whereNotNull('installed_at')->get();
        
        if ($themes->isEmpty()) {
            $this->error(__('admin/command.theme_switch.no_installed_themes'));
            return Command::FAILURE;
        }

        // Get current enabled theme ID from theme_settings
        $currentSetting = DB::table('theme_settings')
            ->where('key', 'enabled_theme_id')
            ->first();
        
        $currentThemeId = $currentSetting ? $currentSetting->value : null;
        $currentTheme = $currentThemeId ? Theme::find($currentThemeId) : null;
        
        // Create choices array
        $choices = $themes->mapWithKeys(function ($theme) use ($currentThemeId) {
            $label = $theme->name;
            if ($currentThemeId && $currentThemeId == $theme->id) {
                $label .= ' ' . __('admin/command.theme_switch.current_marker');
            }
            return [$theme->slug => $label];
        })->toArray();

        $selected = $this->choice(
            __('admin/command.theme_switch.select_prompt'),
            $choices,
            $currentTheme ? $currentTheme->slug : null
        );

        // Find selected theme by the choice value (which is the label)
        $selectedSlug = array_search($selected, $choices);
        $theme = $themes->firstWhere('slug', $selectedSlug);

        if (!$theme) {
            $this->error(__('admin/command.theme_switch.selection_error'));
            return Command::FAILURE;
        }

        return $this->switchTheme($theme);
    }

}
