<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Console\Commands;

use App\Models\Theme;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ThemeSwitch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:theme:switch {themeName? : '.'command.theme_switch.theme_name_prompt'.'}';

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
        if (! $themeName) {
            return $this->interactiveSwitch();
        }

        // Find the theme
        $theme = Theme::where('name', $themeName)
            ->orWhere('slug', $themeName)
            ->first();

        if (! $theme) {
            $this->error(__('admin/command.theme_switch.theme_not_found', ['themeName' => $themeName]));

            return Command::FAILURE;
        }

        // Check if theme is installed
        if (! $theme->isInstalled()) {
            $this->error(__('admin/command.theme_switch.not_installed', ['themeName' => $theme->name]));
            $this->info(__('admin/command.theme_switch.install_first', ['themeName' => $theme->name]));

            return Command::FAILURE;
        }

        return $this->switchTheme($theme);
    }

    /**
     * Switch to the specified theme
     *
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
                'theme' => $theme->directory,
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
                $label .= ' '.__('admin/command.theme_switch.current_marker');
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

        if (! $theme) {
            $this->error(__('admin/command.theme_switch.selection_error'));

            return Command::FAILURE;
        }

        return $this->switchTheme($theme);
    }
}
