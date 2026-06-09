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
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
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

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class SyncGitExclude extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:sync-git-exclude 
                            {--dry-run : Show what would be changed without making changes}
                            {--plugins-only : Only sync plugin exclusions}
                            {--themes-only : Only sync theme exclusions}
                            {--force : Apply changes without confirmation}
                            {--add-plugin= : Add a single plugin exclusion}
                            {--remove-plugin= : Remove a single plugin exclusion}
                            {--add-theme= : Add a single theme exclusion}
                            {--remove-theme= : Remove a single theme exclusion}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync .git/info/exclude with plugin and theme directories';

    /**
     * Path to .git/info/exclude file
     */
    protected function getExcludeFilePath(): string
    {
        return base_path('.git/info/exclude');
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Error if .git directory does not exist
        if (! File::exists(base_path('.git'))) {
            $this->error(__('admin/command/git-sync.git_not_found'));

            return self::FAILURE;
        }

        // Single operation mode
        if ($this->option('add-plugin')) {
            return $this->addPluginExclusion($this->option('add-plugin')) ? self::SUCCESS : self::FAILURE;
        }
        if ($this->option('remove-plugin')) {
            return $this->removePluginExclusion($this->option('remove-plugin')) ? self::SUCCESS : self::FAILURE;
        }
        if ($this->option('add-theme')) {
            return $this->addThemeExclusion($this->option('add-theme')) ? self::SUCCESS : self::FAILURE;
        }
        if ($this->option('remove-theme')) {
            return $this->removeThemeExclusion($this->option('remove-theme')) ? self::SUCCESS : self::FAILURE;
        }

        // Sync mode
        return $this->syncAll();
    }

    /**
     * Sync all plugins and themes
     */
    protected function syncAll(): int
    {
        $excludePath = $this->getExcludeFilePath();
        $dryRun = $this->option('dry-run');
        $pluginsOnly = $this->option('plugins-only');
        $themesOnly = $this->option('themes-only');
        $force = $this->option('force');

        $this->info(__('admin/command/git-sync.scanning'));
        $this->newLine();

        // Get current contents of .git/info/exclude
        $currentContent = File::exists($excludePath) ? File::get($excludePath) : '';

        // Extract currently registered plugins and themes
        $currentPlugins = [];
        $currentThemes = [];

        if ($currentContent) {
            preg_match_all('/!plugins\/([^\s\/]+)/', $currentContent, $matches);
            $currentPlugins = $matches[1] ?? [];

            preg_match_all('/!themes\/([^\s\/]+)/', $currentContent, $matches);
            $currentThemes = $matches[1] ?? [];
        }

        // Detect actual directories
        $actualPlugins = $this->detectDirectories(base_path('plugins'));
        $actualThemes = $this->detectDirectories(base_path('themes'), ['DixlaseOnePage']);

        // Calculate differences
        $pluginsToAdd = array_diff($actualPlugins, $currentPlugins);
        $pluginsToRemove = array_diff($currentPlugins, $actualPlugins);
        $themesToAdd = array_diff($actualThemes, $currentThemes);
        $themesToRemove = array_diff($currentThemes, $actualThemes);

        // Display current state
        $this->displayCurrentState($currentPlugins, $currentThemes, $actualPlugins, $actualThemes);

        // Display changes
        $hasPluginChanges = ! empty($pluginsToAdd) || ! empty($pluginsToRemove);
        $hasThemeChanges = ! empty($themesToAdd) || ! empty($themesToRemove);

        if (! $themesOnly && $hasPluginChanges) {
            $this->displayChanges('Plugins', $pluginsToAdd, $pluginsToRemove);
        }

        if (! $pluginsOnly && $hasThemeChanges) {
            $this->displayChanges('Themes', $themesToAdd, $themesToRemove);
        }

        // If there are no changes
        if ((! $hasPluginChanges || $themesOnly) && (! $hasThemeChanges || $pluginsOnly)) {
            $this->info(__('admin/command/git-sync.exclude_in_sync'));

            return self::SUCCESS;
        }

        // Exit if dry run
        if ($dryRun) {
            $this->newLine();
            $this->warn(__('admin/command/git-sync.dry_run'));

            return self::SUCCESS;
        }

        // Confirmation
        if (! $force && ! $this->confirm(__('admin/command/git-sync.confirm_apply'), true)) {
            $this->info(__('admin/command/git-sync.cancelled'));

            return self::SUCCESS;
        }

        // Apply changes
        $this->applyChanges(
            $excludePath,
            $currentContent,
            $pluginsOnly ? [] : $themesToAdd,
            $pluginsOnly ? [] : $themesToRemove,
            $themesOnly ? [] : $pluginsToAdd,
            $themesOnly ? [] : $pluginsToRemove
        );

        $this->newLine();
        $this->info(__('admin/command/git-sync.exclude_synced'));

        return self::SUCCESS;
    }

    /**
     * Add plugin exclusion rule
     */
    public function addPluginExclusion(string $pluginName): bool
    {
        try {
            $excludePath = $this->getExcludeFilePath();

            // Create .git/info/exclude file if it does not exist
            if (! File::exists($excludePath)) {
                $directory = dirname($excludePath);
                if (! File::exists($directory)) {
                    File::makeDirectory($directory, 0755, true);
                }
                File::put($excludePath, "# Git exclude rules\n");
            }

            $content = File::get($excludePath);
            $pluginPath = "!plugins/{$pluginName}";

            // Skip if already added
            if (str_contains($content, $pluginPath)) {
                $this->info(__('admin/command/git-sync.already_exists', ['path' => $pluginPath]));

                return true;
            }

            // Find plugin section
            $lines = explode("\n", $content);
            $this->addToSection($lines, 'Plugin', 'plugins', [$pluginName]);

            $content = implode("\n", $lines);
            File::put($excludePath, $content);

            return true;
        } catch (\Exception $e) {
            $this->error(__('admin/command/git-sync.failed', ['error' => $e->getMessage()]));
            Log::error('Failed to add plugin exclusion: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Remove plugin exclusion rule
     */
    public function removePluginExclusion(string $pluginName): bool
    {
        try {
            $excludePath = $this->getExcludeFilePath();

            if (! File::exists($excludePath)) {
                return true;
            }

            $content = File::get($excludePath);
            $pluginPath = "!plugins/{$pluginName}";

            $lines = explode("\n", $content);
            $filteredLines = array_filter($lines, fn ($line) => trim($line) !== $pluginPath);

            $content = implode("\n", array_values($filteredLines));
            File::put($excludePath, $content);

            return true;
        } catch (\Exception $e) {
            $this->error(__('admin/command/git-sync.failed', ['error' => $e->getMessage()]));
            Log::error('Failed to remove plugin exclusion: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Add theme exclusion rules
     */
    public function addThemeExclusion(string $themeName): bool
    {
        try {
            $excludePath = $this->getExcludeFilePath();

            if (! File::exists($excludePath)) {
                $directory = dirname($excludePath);
                if (! File::exists($directory)) {
                    File::makeDirectory($directory, 0755, true);
                }
                File::put($excludePath, "# Git exclude rules\n");
            }

            $content = File::get($excludePath);
            $themePath = "!themes/{$themeName}";

            if (str_contains($content, $themePath)) {
                $this->info(__('admin/command/git-sync.already_exists', ['path' => $themePath]));

                return true;
            }

            $lines = explode("\n", $content);
            $this->addToSection($lines, 'Theme', 'themes', [$themeName]);

            $content = implode("\n", $lines);
            File::put($excludePath, $content);

            return true;
        } catch (\Exception $e) {
            $this->error(__('admin/command/git-sync.failed', ['error' => $e->getMessage()]));
            Log::error('Failed to add theme exclusion: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Remove theme exclusion rules
     */
    public function removeThemeExclusion(string $themeName): bool
    {
        try {
            $excludePath = $this->getExcludeFilePath();

            if (! File::exists($excludePath)) {
                return true;
            }

            $content = File::get($excludePath);
            $themePath = "!themes/{$themeName}";

            $lines = explode("\n", $content);
            $filteredLines = array_filter($lines, fn ($line) => trim($line) !== $themePath);

            $content = implode("\n", array_values($filteredLines));
            File::put($excludePath, $content);

            return true;
        } catch (\Exception $e) {
            $this->error(__('admin/command/git-sync.failed', ['error' => $e->getMessage()]));
            Log::error('Failed to remove theme exclusion: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Detect directories
     */
    protected function detectDirectories(string $path, array $exclude = []): array
    {
        if (! File::exists($path)) {
            return [];
        }

        $directories = File::directories($path);
        $names = [];

        foreach ($directories as $directory) {
            $name = basename($directory);

            if (! str_starts_with($name, '.') && ! in_array($name, $exclude)) {
                $names[] = $name;
            }
        }

        sort($names);

        return $names;
    }

    /**
     * Display current state
     */
    protected function displayCurrentState(array $currentPlugins, array $currentThemes, array $actualPlugins, array $actualThemes): void
    {
        $this->table(
            ['Type', 'In .git/info/exclude', 'In Directory'],
            [
                ['Plugins', count($currentPlugins), count($actualPlugins)],
                ['Themes', count($currentThemes), count($actualThemes)],
            ]
        );
        $this->newLine();

        if (! empty($actualPlugins)) {
            $this->info(__('admin/command/git-sync.plugins_found'));
            foreach ($actualPlugins as $plugin) {
                $status = in_array($plugin, $currentPlugins) ? '<fg=green>✓</>' : '<fg=yellow>○</>';
                $this->line("  {$status} {$plugin}");
            }
            $this->newLine();
        }

        if (! empty($actualThemes)) {
            $this->info(__('admin/command/git-sync.themes_found'));
            foreach ($actualThemes as $theme) {
                $status = in_array($theme, $currentThemes) ? '<fg=green>✓</>' : '<fg=yellow>○</>';
                $this->line("  {$status} {$theme}");
            }
            $this->newLine();
        }
    }

    /**
     * Display changes
     */
    protected function displayChanges(string $type, array $toAdd, array $toRemove): void
    {
        if (! empty($toAdd)) {
            $this->info("{$type} ".__('admin/command/git-sync.to_add'));
            foreach ($toAdd as $name) {
                $typeLower = strtolower($type);
                $this->line("  <fg=green>+ !{$typeLower}/{$name}</>");
            }
        }

        if (! empty($toRemove)) {
            $this->warn("{$type} ".__('admin/command/git-sync.to_remove'));
            foreach ($toRemove as $name) {
                $typeLower = strtolower($type);
                $this->line("  <fg=red>- !{$typeLower}/{$name}</>");
            }
        }

        if (! empty($toAdd) || ! empty($toRemove)) {
            $this->newLine();
        }
    }

    /**
     * Apply changes
     */
    protected function applyChanges(
        string $excludePath,
        string $currentContent,
        array $themesToAdd,
        array $themesToRemove,
        array $pluginsToAdd,
        array $pluginsToRemove
    ): void {
        $lines = explode("\n", $currentContent);

        // Deletion process
        foreach ($pluginsToRemove as $plugin) {
            $lines = array_filter($lines, fn ($line) => trim($line) !== "!plugins/{$plugin}");
        }
        foreach ($themesToRemove as $theme) {
            $lines = array_filter($lines, fn ($line) => trim($line) !== "!themes/{$theme}");
        }

        $lines = array_values($lines);

        // Find and add to plugin section
        if (! empty($pluginsToAdd)) {
            $this->addToSection($lines, 'Plugin', 'plugins', $pluginsToAdd);
        }

        // Find and add to theme section
        if (! empty($themesToAdd)) {
            $this->addToSection($lines, 'Theme', 'themes', $themesToAdd);
        }

        // Write to file
        $content = implode("\n", $lines);

        // Create directory if it doesn't exist
        $directory = dirname($excludePath);
        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::put($excludePath, $content);
    }

    /**
     * Add to section
     */
    protected function addToSection(array &$lines, string $sectionName, string $type, array $toAdd): void
    {
        $sectionStart = "# === {$sectionName} exclusions (auto-managed) ===";
        $sectionEnd = "# === End {$type} exclusions ===";

        $startIndex = -1;
        $endIndex = -1;

        foreach ($lines as $index => $line) {
            if (str_contains($line, $sectionStart)) {
                $startIndex = $index;
            } elseif (str_contains($line, $sectionEnd)) {
                $endIndex = $index;
                break;
            }
        }

        // Create section if it doesn't exist
        if ($startIndex === -1) {
            $lines[] = '';
            $lines[] = $sectionStart;
            $lines[] = '# Do not edit this section manually';
            foreach ($toAdd as $name) {
                $lines[] = "!{$type}/{$name}";
            }
            $lines[] = $sectionEnd;
        } else {
            // Add within section
            $insertIndex = ($endIndex !== -1) ? $endIndex : count($lines);
            foreach (array_reverse($toAdd) as $name) {
                array_splice($lines, $insertIndex, 0, ["!{$type}/{$name}"]);
            }
        }
    }

    /**
     * Check if plugin exclusion rules exist
     */
    public static function hasPluginExclusion(string $pluginName): bool
    {
        $excludePath = base_path('.git/info/exclude');

        if (! File::exists($excludePath)) {
            return false;
        }

        $content = File::get($excludePath);

        return str_contains($content, "!plugins/{$pluginName}");
    }

    /**
     * Check if theme exclusion rules exist
     */
    public static function hasThemeExclusion(string $themeName): bool
    {
        $excludePath = base_path('.git/info/exclude');

        if (! File::exists($excludePath)) {
            return false;
        }

        $content = File::get($excludePath);

        return str_contains($content, "!themes/{$themeName}");
    }
}
