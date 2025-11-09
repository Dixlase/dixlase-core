<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use App\Models\Plugin;
use App\Helpers\GitExcludeHelper;
use App\Helpers\ComposerLocalHelper;

class SyncGitExclusions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'git:sync-exclusions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync .git/info/exclude with plugin and theme directories';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // .git/info/excludeの現在の内容を取得
        $currentContent = GitExcludeHelper::getExcludeContent();
        $currentPlugins = [];
        
        if ($currentContent) {
            // 現在登録されているプラグインを抽出
            preg_match_all('/!plugins\/([^\s]+)/', $currentContent, $matches);
            $currentPlugins = $matches[1] ?? [];
        }

        // plugins/ディレクトリから実際のプラグインディレクトリを検出
        $pluginsPath = base_path('plugins');
        $actualPlugins = [];
        
        if (File::exists($pluginsPath)) {
            $directories = File::directories($pluginsPath);
            foreach ($directories as $directory) {
                $pluginName = basename($directory);
                // .で始まるディレクトリは除外
                if (!str_starts_with($pluginName, '.')) {
                    $actualPlugins[] = $pluginName;
                }
            }
        }

        if (empty($actualPlugins) && empty($currentPlugins)) {
            $this->info('No plugin directories found in plugins/ folder and no exclusions in .git/info/exclude');
            return 0;
        }

        // 差分を計算
        $toAdd = array_diff($actualPlugins, $currentPlugins);
        $toRemove = array_diff($currentPlugins, $actualPlugins);

        $this->info('Syncing .git/info/exclude with plugin directories...');
        $this->newLine();

        // 現在の状態を表示
        if (!empty($currentPlugins)) {
            $this->info('Current exclusions in .git/info/exclude:');
            foreach ($currentPlugins as $plugin) {
                $this->line('  - ' . $plugin);
            }
            $this->newLine();
        }

        // 実際のプラグインディレクトリを表示
        if (!empty($actualPlugins)) {
            $this->info('Plugin directories in plugins/ folder:');
            foreach ($actualPlugins as $plugin) {
                $this->line('  - ' . $plugin);
            }
            $this->newLine();
        }

        // 追加されるプラグインを表示
        if (!empty($toAdd)) {
            $this->info('Will add to .git/info/exclude:');
            foreach ($toAdd as $plugin) {
                $this->line('  <fg=green>+ ' . $plugin . '</>');
            }
            $this->newLine();
        }

        // 削除されるプラグインを表示
        if (!empty($toRemove)) {
            $this->warn('Will remove from .git/info/exclude:');
            foreach ($toRemove as $plugin) {
                $this->line('  <fg=red>- ' . $plugin . '</>');
            }
            $this->newLine();
        }

        // テーマの同期も実行
        $currentThemes = [];
        if ($currentContent) {
            preg_match_all('/!themes\/([^\s]+)/', $currentContent, $matches);
            $currentThemes = $matches[1] ?? [];
        }

        // themes/ディレクトリから実際のテーマディレクトリを検出
        $themesPath = base_path('themes');
        $actualThemes = [];
        
        if (File::exists($themesPath)) {
            $directories = File::directories($themesPath);
            foreach ($directories as $directory) {
                $themeName = basename($directory);
                // .で始まるディレクトリは除外
                if (!str_starts_with($themeName, '.')) {
                    $actualThemes[] = $themeName;
                }
            }
        }

        $themesToAdd = array_diff($actualThemes, $currentThemes);
        $themesToRemove = array_diff($currentThemes, $actualThemes);

        // テーマの状態を表示
        if (!empty($actualThemes)) {
            $this->info('Theme directories in themes/ folder:');
            foreach ($actualThemes as $theme) {
                $this->line('  - ' . $theme);
            }
            $this->newLine();
        }

        if (!empty($themesToAdd)) {
            $this->info('Will add themes to .git/info/exclude:');
            foreach ($themesToAdd as $theme) {
                $this->line('  <fg=green>+ ' . $theme . '</>');
            }
            $this->newLine();
        }

        if (!empty($themesToRemove)) {
            $this->warn('Will remove themes from .git/info/exclude:');
            foreach ($themesToRemove as $theme) {
                $this->line('  <fg=red>- ' . $theme . '</>');
            }
            $this->newLine();
        }

        // 変更がない場合でもcomposer.local.jsonは同期する
        $hasGitChanges = !empty($toAdd) || !empty($toRemove) || !empty($themesToAdd) || !empty($themesToRemove);
        
        if (!$hasGitChanges) {
            $this->info('✓ .git/info/exclude already in sync. No changes needed.');
        }

        // .git/info/excludeを同期（nullを渡すと自動検出）
        $pluginResult = true;
        if ($hasGitChanges) {
            $pluginResult = GitExcludeHelper::syncPluginExclusions();
        }

        // テーマの同期
        $themeResult = true;
        foreach ($themesToAdd as $theme) {
            GitExcludeHelper::addThemeExclusion($theme);
        }
        foreach ($themesToRemove as $theme) {
            GitExcludeHelper::removeThemeExclusion($theme);
        }

        if ($pluginResult && $themeResult) {
            if ($hasGitChanges) {
                $this->newLine();
                $this->info('✓ Successfully synced .git/info/exclude');
                
                if (!empty($toAdd)) {
                    $this->info('  Plugins added: ' . count($toAdd));
                }
                if (!empty($toRemove)) {
                    $this->info('  Plugins removed: ' . count($toRemove));
                }
                if (!empty($themesToAdd)) {
                    $this->info('  Themes added: ' . count($themesToAdd));
                }
                if (!empty($themesToRemove)) {
                    $this->info('  Themes removed: ' . count($themesToRemove));
                }
                
                $this->info('  Total plugin exclusions: ' . count($actualPlugins));
                $this->info('  Total theme exclusions: ' . count($actualThemes));
            }
            
            // composer.local.jsonも同期（常に実行）
            $this->newLine();
            $this->info('Syncing composer.local.json...');
            
            $composerResult = ComposerLocalHelper::syncAutoload();
            
            if ($composerResult) {
                $this->info('✓ Successfully synced composer.local.json');
                $this->info('  Plugins: ' . count($actualPlugins));
                $this->info('  Themes: ' . count($actualThemes));
            } else {
                $this->warn('⚠ Failed to sync composer.local.json');
            }
            
            return 0;
        } else {
            $this->error('✗ Failed to sync .git/info/exclude');
            $this->error('  Git repository may not exist or file is not writable');
            return 1;
        }
    }
}
