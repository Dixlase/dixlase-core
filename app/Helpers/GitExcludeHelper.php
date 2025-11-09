<?php

namespace App\Helpers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class GitExcludeHelper
{
    /**
     * .git/info/excludeファイルのパス
     */
    protected static function getExcludeFilePath(): string
    {
        return base_path('.git/info/exclude');
    }

    /**
     * プラグインの除外ルールを追加
     *
     * @param string $pluginName プラグイン名（例: DixlaseMenu）
     * @return bool 成功したかどうか
     */
    public static function addPluginExclusion(string $pluginName): bool
    {
        try {
            $excludePath = self::getExcludeFilePath();
            
            // .gitディレクトリが存在しない場合はスキップ
            if (!File::exists(base_path('.git'))) {
                Log::info("Git repository not found. Skipping .git/info/exclude update.");
                return false;
            }

            // .git/info/excludeファイルが存在しない場合は作成
            if (!File::exists($excludePath)) {
                $directory = dirname($excludePath);
                if (!File::exists($directory)) {
                    File::makeDirectory($directory, 0755, true);
                }
                File::put($excludePath, "# Git exclude rules\n");
            }

            $content = File::get($excludePath);
            $pluginPath = "!plugins/{$pluginName}";

            // 既に追加されている場合はスキップ
            if (str_contains($content, $pluginPath)) {
                Log::info("Plugin exclusion already exists: {$pluginPath}");
                return true;
            }

            // プラグインセクションを探す
            $lines = explode("\n", $content);
            $pluginSectionStart = -1;
            $pluginSectionEnd = -1;

            foreach ($lines as $index => $line) {
                if (str_contains($line, '=== Plugin exclusions (auto-managed) ===')) {
                    $pluginSectionStart = $index;
                } elseif ($pluginSectionStart !== -1 && str_contains($line, '=== End plugin exclusions ===')) {
                    $pluginSectionEnd = $index;
                    break;
                }
            }

            // プラグインセクションが存在しない場合は作成
            if ($pluginSectionStart === -1) {
                $content .= "\n# === Plugin exclusions (auto-managed) ===\n";
                $content .= "# Do not edit this section manually\n";
                $content .= "{$pluginPath}\n";
                $content .= "# === End plugin exclusions ===\n";
            } else {
                // プラグインセクション内に追加
                array_splice($lines, $pluginSectionEnd, 0, [$pluginPath]);
                $content = implode("\n", $lines);
            }

            File::put($excludePath, $content);
            Log::info("Added plugin exclusion to .git/info/exclude: {$pluginPath}");

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to add plugin exclusion: " . $e->getMessage());
            return false;
        }
    }

    /**
     * プラグインの除外ルールを削除
     *
     * @param string $pluginName プラグイン名
     * @return bool 成功したかどうか
     */
    public static function removePluginExclusion(string $pluginName): bool
    {
        try {
            $excludePath = self::getExcludeFilePath();

            if (!File::exists($excludePath)) {
                return true; // ファイルが存在しない場合は成功とみなす
            }

            $content = File::get($excludePath);
            $pluginPath = "!plugins/{$pluginName}";

            // 該当行を削除
            $lines = explode("\n", $content);
            $lines = array_filter($lines, function ($line) use ($pluginPath) {
                return trim($line) !== $pluginPath;
            });

            $content = implode("\n", $lines);
            File::put($excludePath, $content);

            Log::info("Removed plugin exclusion from .git/info/exclude: {$pluginPath}");

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to remove plugin exclusion: " . $e->getMessage());
            return false;
        }
    }

    /**
     * すべてのプラグインディレクトリの除外ルールを同期
     *
     * @param array $pluginDirectories プラグインディレクトリ名の配列（nullの場合は自動検出）
     * @return bool 成功したかどうか
     */
    public static function syncPluginExclusions(?array $pluginDirectories = null): bool
    {
        try {
            $excludePath = self::getExcludeFilePath();

            // .gitディレクトリが存在しない場合はスキップ
            if (!File::exists(base_path('.git'))) {
                return false;
            }

            // プラグインディレクトリが指定されていない場合は自動検出
            if ($pluginDirectories === null) {
                $pluginDirectories = self::detectPluginDirectories();
            }

            // 既存の内容を読み込む
            $content = File::exists($excludePath) ? File::get($excludePath) : "# Git exclude rules\n";
            $lines = explode("\n", $content);

            // プラグインセクションを削除
            $newLines = [];
            $inPluginSection = false;

            foreach ($lines as $line) {
                if (str_contains($line, '=== Plugin exclusions (auto-managed) ===')) {
                    $inPluginSection = true;
                    continue;
                } elseif (str_contains($line, '=== End plugin exclusions ===')) {
                    $inPluginSection = false;
                    continue;
                }

                if (!$inPluginSection) {
                    $newLines[] = $line;
                }
            }

            // 新しいプラグインセクションを追加
            $newLines[] = '';
            $newLines[] = '# === Plugin exclusions (auto-managed) ===';
            $newLines[] = '# Do not edit this section manually';

            foreach ($pluginDirectories as $pluginName) {
                $newLines[] = "!plugins/{$pluginName}";
            }

            $newLines[] = '# === End plugin exclusions ===';

            $content = implode("\n", $newLines);
            File::put($excludePath, $content);

            Log::info("Synced plugin exclusions in .git/info/exclude", ['plugins' => $pluginDirectories]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to sync plugin exclusions: " . $e->getMessage());
            return false;
        }
    }

    /**
     * pluginsディレクトリ内のプラグインディレクトリを自動検出
     *
     * @return array プラグインディレクトリ名の配列
     */
    protected static function detectPluginDirectories(): array
    {
        $pluginsPath = base_path('plugins');
        
        if (!File::exists($pluginsPath)) {
            return [];
        }

        $directories = File::directories($pluginsPath);
        $pluginNames = [];

        foreach ($directories as $directory) {
            $pluginName = basename($directory);
            
            // .で始まるディレクトリは除外
            if (str_starts_with($pluginName, '.')) {
                continue;
            }

            $pluginNames[] = $pluginName;
        }

        return $pluginNames;
    }

    /**
     * .git/info/excludeの内容を取得
     *
     * @return string|null
     */
    public static function getExcludeContent(): ?string
    {
        $excludePath = self::getExcludeFilePath();

        if (!File::exists($excludePath)) {
            return null;
        }

        return File::get($excludePath);
    }

    /**
     * プラグインの除外ルールが存在するか確認
     *
     * @param string $pluginName プラグイン名
     * @return bool
     */
    public static function hasPluginExclusion(string $pluginName): bool
    {
        $content = self::getExcludeContent();

        if ($content === null) {
            return false;
        }

        return str_contains($content, "!plugins/{$pluginName}");
    }

    /**
     * テーマの除外ルールを追加
     *
     * @param string $themeName テーマ名
     * @return bool 成功したかどうか
     */
    public static function addThemeExclusion(string $themeName): bool
    {
        try {
            $excludePath = self::getExcludeFilePath();
            
            // .gitディレクトリが存在しない場合はスキップ
            if (!File::exists(base_path('.git'))) {
                Log::info("Git repository not found. Skipping .git/info/exclude update.");
                return false;
            }

            // .git/info/excludeファイルが存在しない場合は作成
            if (!File::exists($excludePath)) {
                $directory = dirname($excludePath);
                if (!File::exists($directory)) {
                    File::makeDirectory($directory, 0755, true);
                }
                File::put($excludePath, "# Git exclude rules\n");
            }

            $content = File::get($excludePath);
            $themePath = "!themes/{$themeName}";

            // 既に追加されている場合はスキップ
            if (str_contains($content, $themePath)) {
                Log::info("Theme exclusion already exists: {$themePath}");
                return true;
            }

            // テーマセクションを探す
            $lines = explode("\n", $content);
            $themeSectionStart = -1;
            $themeSectionEnd = -1;

            foreach ($lines as $index => $line) {
                if (str_contains($line, '=== Theme exclusions (auto-managed) ===')) {
                    $themeSectionStart = $index;
                } elseif ($themeSectionStart !== -1 && str_contains($line, '=== End theme exclusions ===')) {
                    $themeSectionEnd = $index;
                    break;
                }
            }

            // テーマセクションが存在しない場合は作成
            if ($themeSectionStart === -1) {
                $content .= "\n# === Theme exclusions (auto-managed) ===\n";
                $content .= "# Do not edit this section manually\n";
                $content .= "{$themePath}\n";
                $content .= "# === End theme exclusions ===\n";
            } else {
                // テーマセクション内に追加
                array_splice($lines, $themeSectionEnd, 0, [$themePath]);
                $content = implode("\n", $lines);
            }

            File::put($excludePath, $content);
            Log::info("Added theme exclusion to .git/info/exclude: {$themePath}");

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to add theme exclusion: " . $e->getMessage());
            return false;
        }
    }

    /**
     * テーマの除外ルールを削除
     *
     * @param string $themeName テーマ名
     * @return bool 成功したかどうか
     */
    public static function removeThemeExclusion(string $themeName): bool
    {
        try {
            $excludePath = self::getExcludeFilePath();

            if (!File::exists($excludePath)) {
                return true; // ファイルが存在しない場合は成功とみなす
            }

            $content = File::get($excludePath);
            $themePath = "!themes/{$themeName}";

            // 該当行を削除
            $lines = explode("\n", $content);
            $lines = array_filter($lines, function ($line) use ($themePath) {
                return trim($line) !== $themePath;
            });

            $content = implode("\n", $lines);
            File::put($excludePath, $content);

            Log::info("Removed theme exclusion from .git/info/exclude: {$themePath}");

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to remove theme exclusion: " . $e->getMessage());
            return false;
        }
    }

    /**
     * テーマの除外ルールが存在するか確認
     *
     * @param string $themeName テーマ名
     * @return bool
     */
    public static function hasThemeExclusion(string $themeName): bool
    {
        $content = self::getExcludeContent();

        if ($content === null) {
            return false;
        }

        return str_contains($content, "!themes/{$themeName}");
    }
}
