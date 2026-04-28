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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
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

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class SyncGitIgnore extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:sync-gitignore 
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
    protected $description = 'Sync .gitignore with plugin and theme directories';

    /**
     * .gitignoreファイルのパス
     */
    protected function getGitIgnorePath(): string
    {
        return base_path('.gitignore');
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // .gitignoreが存在しない場合はエラー
        if (! File::exists($this->getGitIgnorePath())) {
            $this->error(__('admin/command.git_sync.gitignore_not_found'));

            return self::FAILURE;
        }

        // 単一操作モード
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

        // 同期モード
        return $this->syncAll();
    }

    /**
     * すべてのプラグイン・テーマを同期
     */
    protected function syncAll(): int
    {
        $gitIgnorePath = $this->getGitIgnorePath();
        $dryRun = $this->option('dry-run');
        $pluginsOnly = $this->option('plugins-only');
        $themesOnly = $this->option('themes-only');
        $force = $this->option('force');

        $this->info(__('admin/command.git_sync.scanning'));
        $this->newLine();

        // 現在の.gitignoreの内容を取得
        $currentContent = File::get($gitIgnorePath);

        // 現在登録されているプラグインとテーマを抽出
        $currentPlugins = [];
        $currentThemes = [];

        // !plugins/PluginName/ 形式を検出
        preg_match_all('/!plugins\/([^\s\/]+)\//', $currentContent, $matches);
        $currentPlugins = $matches[1] ?? [];

        preg_match_all('/!themes\/([^\s\/]+)\//', $currentContent, $matches);
        $currentThemes = $matches[1] ?? [];

        // 実際のディレクトリを検出
        $actualPlugins = $this->detectDirectories(base_path('plugins'));
        $actualThemes = $this->detectDirectories(base_path('themes'), ['DixlaseOnePage']);

        // 差分を計算
        $pluginsToAdd = array_diff($actualPlugins, $currentPlugins);
        $pluginsToRemove = array_diff($currentPlugins, $actualPlugins);
        $themesToAdd = array_diff($actualThemes, $currentThemes);
        $themesToRemove = array_diff($currentThemes, $actualThemes);

        // 現在の状態を表示
        $this->displayCurrentState($currentPlugins, $currentThemes, $actualPlugins, $actualThemes);

        // 変更内容を表示
        $hasPluginChanges = ! empty($pluginsToAdd) || ! empty($pluginsToRemove);
        $hasThemeChanges = ! empty($themesToAdd) || ! empty($themesToRemove);

        if (! $themesOnly && $hasPluginChanges) {
            $this->displayChanges('plugins', $pluginsToAdd, $pluginsToRemove);
        }

        if (! $pluginsOnly && $hasThemeChanges) {
            $this->displayChanges('themes', $themesToAdd, $themesToRemove);
        }

        // 変更がない場合
        if ((! $hasPluginChanges || $themesOnly) && (! $hasThemeChanges || $pluginsOnly)) {
            $this->info(__('admin/command.git_sync.gitignore_in_sync'));

            return self::SUCCESS;
        }

        // ドライランの場合は終了
        if ($dryRun) {
            $this->newLine();
            $this->warn(__('admin/command.git_sync.dry_run'));

            return self::SUCCESS;
        }

        // 確認
        if (! $force && ! $this->confirm(__('admin/command.git_sync.confirm_apply'), true)) {
            $this->info(__('admin/command.git_sync.cancelled'));

            return self::SUCCESS;
        }

        // 変更を適用
        $this->applyChanges(
            $gitIgnorePath,
            $currentContent,
            $pluginsOnly ? [] : $themesToAdd,
            $pluginsOnly ? [] : $themesToRemove,
            $themesOnly ? [] : $pluginsToAdd,
            $themesOnly ? [] : $pluginsToRemove
        );

        $this->newLine();
        $this->info(__('admin/command.git_sync.gitignore_synced'));

        return self::SUCCESS;
    }

    /**
     * プラグインの除外ルールを追加
     */
    public function addPluginExclusion(string $pluginName): bool
    {
        return $this->addExclusion('plugins', $pluginName);
    }

    /**
     * プラグインの除外ルールを削除
     */
    public function removePluginExclusion(string $pluginName): bool
    {
        return $this->removeExclusion('plugins', $pluginName);
    }

    /**
     * テーマの除外ルールを追加
     */
    public function addThemeExclusion(string $themeName): bool
    {
        return $this->addExclusion('themes', $themeName);
    }

    /**
     * テーマの除外ルールを削除
     */
    public function removeThemeExclusion(string $themeName): bool
    {
        return $this->removeExclusion('themes', $themeName);
    }

    /**
     * 除外ルールを追加
     */
    protected function addExclusion(string $type, string $name): bool
    {
        try {
            $gitIgnorePath = $this->getGitIgnorePath();

            if (! File::exists($gitIgnorePath)) {
                $this->warn(__('admin/command.git_sync.gitignore_not_found'));

                return false;
            }

            $content = File::get($gitIgnorePath);
            $exclusionLine = "!{$type}/{$name}/";

            // 既に追加されている場合はスキップ
            if (str_contains($content, $exclusionLine)) {
                $this->info(__('admin/command.git_sync.already_exists', ['path' => $exclusionLine]));

                return true;
            }

            // 挿入位置を探す
            $lines = explode("\n", $content);
            $insertIndex = $this->findInsertIndex($lines, $type, $name);

            if ($insertIndex === -1) {
                $content = rtrim($content)."\n{$exclusionLine}\n";
            } else {
                array_splice($lines, $insertIndex, 0, [$exclusionLine]);
                $content = implode("\n", $lines);
            }

            File::put($gitIgnorePath, $content);

            return true;
        } catch (\Exception $e) {
            $this->error(__('admin/command.git_sync.failed', ['error' => $e->getMessage()]));
            Log::error("Failed to add {$type} exclusion to .gitignore: ".$e->getMessage());

            return false;
        }
    }

    /**
     * 除外ルールを削除
     */
    protected function removeExclusion(string $type, string $name): bool
    {
        try {
            $gitIgnorePath = $this->getGitIgnorePath();

            if (! File::exists($gitIgnorePath)) {
                return true;
            }

            $content = File::get($gitIgnorePath);
            $exclusionLine = "!{$type}/{$name}/";

            $lines = explode("\n", $content);
            $filteredLines = array_filter($lines, fn ($line) => trim($line) !== $exclusionLine);

            $content = implode("\n", array_values($filteredLines));
            File::put($gitIgnorePath, $content);

            return true;
        } catch (\Exception $e) {
            $this->error(__('admin/command.git_sync.failed', ['error' => $e->getMessage()]));
            Log::error("Failed to remove {$type} exclusion from .gitignore: ".$e->getMessage());

            return false;
        }
    }

    /**
     * 挿入位置を探す
     */
    protected function findInsertIndex(array $lines, string $type, string $name): int
    {
        $basePattern = "{$type}/*";
        $nextSectionPattern = ($type === 'plugins') ? 'themes/*' : null;

        $baseIndex = -1;
        $lastExclusionIndex = -1;
        $nextSectionIndex = -1;

        foreach ($lines as $index => $line) {
            $trimmedLine = trim($line);

            if ($trimmedLine === $basePattern) {
                $baseIndex = $index;
            }

            if (str_starts_with($trimmedLine, "!{$type}/")) {
                $lastExclusionIndex = $index;
            }

            if ($nextSectionPattern && $trimmedLine === $nextSectionPattern) {
                $nextSectionIndex = $index;
                break;
            }
        }

        if ($lastExclusionIndex !== -1) {
            return $this->findSortedInsertIndex($lines, $type, $name, $baseIndex, $nextSectionIndex);
        } elseif ($baseIndex !== -1) {
            return $baseIndex + 1;
        }

        return -1;
    }

    /**
     * アルファベット順でソートされた挿入位置を探す
     */
    protected function findSortedInsertIndex(array $lines, string $type, string $name, int $startIndex, int $endIndex): int
    {
        $endIndex = ($endIndex === -1) ? count($lines) : $endIndex;
        $newExclusion = "!{$type}/{$name}/";

        for ($i = $startIndex + 1; $i < $endIndex; $i++) {
            $line = trim($lines[$i]);

            if (! str_starts_with($line, "!{$type}/")) {
                return $i;
            }

            if (strcasecmp($line, $newExclusion) > 0) {
                return $i;
            }
        }

        return $endIndex;
    }

    /**
     * ディレクトリを検出
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
     * 現在の状態を表示
     */
    protected function displayCurrentState(array $currentPlugins, array $currentThemes, array $actualPlugins, array $actualThemes): void
    {
        $this->table(
            ['Type', 'In .gitignore', 'In Directory'],
            [
                ['Plugins', count($currentPlugins), count($actualPlugins)],
                ['Themes', count($currentThemes), count($actualThemes)],
            ]
        );
        $this->newLine();

        if (! empty($actualPlugins)) {
            $this->info(__('admin/command.git_sync.plugins_found'));
            foreach ($actualPlugins as $plugin) {
                $status = in_array($plugin, $currentPlugins) ? '<fg=green>✓</>' : '<fg=yellow>○</>';
                $this->line("  {$status} {$plugin}");
            }
            $this->newLine();
        }

        if (! empty($actualThemes)) {
            $this->info(__('admin/command.git_sync.themes_found'));
            foreach ($actualThemes as $theme) {
                $status = in_array($theme, $currentThemes) ? '<fg=green>✓</>' : '<fg=yellow>○</>';
                $this->line("  {$status} {$theme}");
            }
            $this->newLine();
        }
    }

    /**
     * 変更内容を表示
     */
    protected function displayChanges(string $type, array $toAdd, array $toRemove): void
    {
        $typeName = ucfirst($type);

        if (! empty($toAdd)) {
            $this->info("{$typeName} ".__('admin/command.git_sync.to_add'));
            foreach ($toAdd as $name) {
                $this->line("  <fg=green>+ !{$type}/{$name}/</>");
            }
        }

        if (! empty($toRemove)) {
            $this->warn("{$typeName} ".__('admin/command.git_sync.to_remove'));
            foreach ($toRemove as $name) {
                $this->line("  <fg=red>- !{$type}/{$name}/</>");
            }
        }

        if (! empty($toAdd) || ! empty($toRemove)) {
            $this->newLine();
        }
    }

    /**
     * 変更を適用
     */
    protected function applyChanges(
        string $gitIgnorePath,
        string $currentContent,
        array $themesToAdd,
        array $themesToRemove,
        array $pluginsToAdd,
        array $pluginsToRemove
    ): void {
        $lines = explode("\n", $currentContent);

        // 削除処理
        foreach ($pluginsToRemove as $plugin) {
            $lines = array_filter($lines, fn ($line) => trim($line) !== "!plugins/{$plugin}/");
        }
        foreach ($themesToRemove as $theme) {
            $lines = array_filter($lines, fn ($line) => trim($line) !== "!themes/{$theme}/");
        }

        $lines = array_values($lines);

        // プラグインを追加
        if (! empty($pluginsToAdd)) {
            $this->addExclusions($lines, 'plugins', $pluginsToAdd);
        }

        // テーマを追加
        if (! empty($themesToAdd)) {
            $this->addExclusions($lines, 'themes', $themesToAdd);
        }

        // ファイルに書き込み
        $content = implode("\n", $lines);
        File::put($gitIgnorePath, $content);
    }

    /**
     * 除外ルールを一括追加
     */
    protected function addExclusions(array &$lines, string $type, array $toAdd): void
    {
        $basePattern = "{$type}/*";
        $nextSectionPattern = ($type === 'plugins') ? 'themes/*' : null;

        $baseIndex = -1;
        $lastExclusionIndex = -1;
        $nextSectionIndex = -1;

        foreach ($lines as $index => $line) {
            $trimmedLine = trim($line);

            if ($trimmedLine === $basePattern) {
                $baseIndex = $index;
            }

            if (str_starts_with($trimmedLine, "!{$type}/")) {
                $lastExclusionIndex = $index;
            }

            if ($nextSectionPattern && $trimmedLine === $nextSectionPattern) {
                $nextSectionIndex = $index;
                break;
            }
        }

        $insertIndex = -1;

        if ($lastExclusionIndex !== -1) {
            $insertIndex = $lastExclusionIndex + 1;
        } elseif ($baseIndex !== -1) {
            $insertIndex = $baseIndex + 1;
        }

        if ($insertIndex === -1) {
            foreach ($toAdd as $name) {
                $lines[] = "!{$type}/{$name}/";
            }
        } else {
            sort($toAdd);
            foreach (array_reverse($toAdd) as $name) {
                array_splice($lines, $insertIndex, 0, ["!{$type}/{$name}/"]);
            }
        }
    }

    /**
     * プラグインの除外ルールが存在するか確認
     */
    public static function hasPluginExclusion(string $pluginName): bool
    {
        $gitIgnorePath = base_path('.gitignore');

        if (! File::exists($gitIgnorePath)) {
            return false;
        }

        $content = File::get($gitIgnorePath);

        return str_contains($content, "!plugins/{$pluginName}/");
    }

    /**
     * テーマの除外ルールが存在するか確認
     */
    public static function hasThemeExclusion(string $themeName): bool
    {
        $gitIgnorePath = base_path('.gitignore');

        if (! File::exists($gitIgnorePath)) {
            return false;
        }

        $content = File::get($gitIgnorePath);

        return str_contains($content, "!themes/{$themeName}/");
    }
}
