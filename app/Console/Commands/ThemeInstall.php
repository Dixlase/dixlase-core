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
use Illuminate\Support\Str;
use App\Models\Theme;
use App\Helpers\ComposerLocalHelper;
use App\Services\ThemeMigrator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Database\ConnectionResolverInterface;

class ThemeInstall extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:theme:install {themeName : ' . 'command.theme_install.theme_name_prompt' . '} {--force : Force reinstall even if already registered}';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'command.theme_install.description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $themeName = $this->argument('themeName');
        $themeDirName = Str::studly($themeName);
        $themeDir = base_path("themes/" . $themeDirName);

        // Check if theme directory exists
        if (!file_exists($themeDir)) {
            $this->error(__('admin/command.theme_install.theme_not_found', ['themeName' => $themeName]));
            return Command::FAILURE;
        }

        // Check if theme is already registered
        $slug = Str::kebab($themeName);
        $exists = Theme::where('slug', $slug)->exists();
        
        if ($exists && !$this->option('force')) {
            $this->error(__('admin/command.theme_install.already_registered', ['themeName' => $themeName]));
            return Command::FAILURE;
        }
        
        // --forceオプションが指定されている場合は、マイグレーションとシーダーのみ実行
        if ($exists && $this->option('force')) {
            $this->info("Theme already registered. Running migrations and seeders only...");
            
            // マイグレーションを実行
            $migrationPath = base_path("themes/{$themeDirName}/database/migrations");
            
            if (file_exists($migrationPath) && is_dir($migrationPath)) {
                try {
                    $migrator = new ThemeMigrator(
                        app(Filesystem::class),
                        app(ConnectionResolverInterface::class),
                        'theme_migrations',
                        $slug
                    );
                    $migrator->migrate($themeDirName);
                    $this->info("Theme migrations executed successfully");
                } catch (\Exception $e) {
                    $this->warn("Failed to execute theme migrations: " . $e->getMessage());
                }
            }
            
            // シーダーを実行
            try {
                $seederClass = "Themes\\{$themeDirName}\\Database\\Seeders\\DatabaseSeeder";
                
                if (class_exists($seederClass)) {
                    $seeder = new $seederClass();
                    $seeder->setCommand($this);
                    $seeder->run();
                    $this->info("Theme seeder executed successfully");
                }
            } catch (\Exception $e) {
                $this->warn("Failed to execute theme seeder: " . $e->getMessage());
            }
            
            $this->info("Theme migrations and seeders completed.");
            return Command::SUCCESS;
        }

        // テーマ情報を読み取る（theme.json → composer.json → デフォルト値の順）
        $themeJsonPath = "{$themeDir}/theme.json";
        $composerPath = "{$themeDir}/composer.json";
        
        $displayName = null;
        $packageName = null;
        $namespace = null;
        $description = null;
        $license = null;
        $author = null;
        $email = null;
        $web = null;
        $version = '1.0.0';

        // 1. theme.jsonから読み取り（最優先）
        if (file_exists($themeJsonPath)) {
            $themeData = json_decode(file_get_contents($themeJsonPath), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $displayName = $themeData['name'] ?? null;
                $packageName = $themeData['package_name'] ?? null;
                $namespace = $themeData['namespace'] ?? null;
                $description = $themeData['description'] ?? null;
                if (is_array($description)) {
                    $description = $description['en'] ?? $description['ja'] ?? null;
                }
                $license = $themeData['license'] ?? null;
                $author = $themeData['author'] ?? null;
                $email = $themeData['email'] ?? null;
                $web = $themeData['url'] ?? $themeData['homepage'] ?? $themeData['web'] ?? null;
                $version = $themeData['version'] ?? '1.0.0';
            }
        }

        // 2. composer.jsonからフォールバック
        if (file_exists($composerPath)) {
            $composerData = json_decode(file_get_contents($composerPath), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                // display-nameをextra.dixlaseから取得
                $displayName = $displayName ?? $composerData['extra']['dixlase']['display-name'] ?? null;
                $packageName = $packageName ?? $composerData['name'] ?? null;
                
                // namespaceはcomposer.jsonのautoloadから取得
                if (!$namespace && isset($composerData['autoload']['psr-4'])) {
                    $namespace = array_key_first($composerData['autoload']['psr-4']);
                    $namespace = rtrim($namespace, '\\');
                }
                
                $description = $description ?? $composerData['description'] ?? null;
                $license = $license ?? $composerData['license'] ?? null;
                
                // authors配列から情報を取得
                if (!$author && isset($composerData['authors']) && is_array($composerData['authors']) && count($composerData['authors']) > 0) {
                    $author = $composerData['authors'][0]['name'] ?? null;
                    $email = $email ?? $composerData['authors'][0]['email'] ?? null;
                    $web = $web ?? $composerData['authors'][0]['homepage'] ?? null;
                }
                
                // versionはextra.dixlase.versionから取得、なければルートのもの
                if ($version === '1.0.0') {
                    $version = $composerData['extra']['dixlase']['version'] ?? $composerData['version'] ?? '1.0.0';
                }
            }
        }

        // デフォルト値の設定
        $displayName = $displayName ?? $themeDirName;
        $namespace = $namespace ?? "Themes\\{$themeDirName}";
        
        // テーマ設定ページの有無をチェック
        $hasSettings = file_exists("{$themeDir}/app/Http/Controllers/Admin/Settings/Themes/ThemeSettingsController.php");

        // Register the theme
        $theme = Theme::create([
            'name' => $displayName,
            'package_name' => $packageName,
            'directory' => $themeDirName,
            'slug' => Str::kebab($themeName),
            'namespace' => $namespace,
            'description' => $description,
            'license' => $license,
            'author' => $author,
            'email' => $email,
            'url' => $web,
            'version' => $version,
            'has_settings' => $hasSettings,
            'installed_at' => now(),
        ]);

        // composer.local.jsonを更新
        ComposerLocalHelper::syncAutoload();
        $this->info("Updated composer.local.json");

        // マイグレーションを実行（ThemeMigratorを使用）
        $migrationPath = base_path("themes/{$themeDirName}/database/migrations");
        
        if (file_exists($migrationPath) && is_dir($migrationPath)) {
            try {
                
                $migrator = new ThemeMigrator(
                    app(Filesystem::class),
                    app(ConnectionResolverInterface::class),
                    'theme_migrations',
                    $slug
                );
                $migrator->migrate($themeDirName);
                $this->info("Theme migrations executed successfully");
            } catch (\Exception $e) {
                \Log::error('ThemeInstall: Migration failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $this->warn("Failed to execute theme migrations: " . $e->getMessage());
            }
        }

        // シーダーを実行
        try {
            $seederClass = "Themes\\{$themeDirName}\\Database\\Seeders\\DatabaseSeeder";
            
            if (class_exists($seederClass)) {
                $seeder = new $seederClass();
                // コマンドインスタンスをセット
                $seeder->setCommand($this);
                $seeder->run();
                $this->info("Theme seeder executed successfully");
            }
        } catch (\Exception $e) {
            $this->warn("Failed to execute theme seeder: " . $e->getMessage());
        }

        $this->info(__('admin/command.theme_install.registered', ['themeName' => $themeName]));
        $this->info(__('admin/command.theme_install.activate_help', ['themeName' => $themeName]));

        return Command::SUCCESS;
    }
}
