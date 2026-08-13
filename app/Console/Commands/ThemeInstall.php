<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

use App\Console\Traits\BuildsExtensionAssets;
use App\Helpers\ComposerLocalHelper;
use App\Models\Theme;
use App\Services\Extension\ExtensionSourceManager;
use App\Services\ThemeMigrator;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class ThemeInstall extends Command
{
    use BuildsExtensionAssets;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:theme:install {themeName : '.'command.theme_install.theme_name_prompt'.'} {--force : Force reinstall even if already registered} {--build : Force a front-end asset rebuild even when compiled assets already exist} {--skip-build : Skip the npm install / build step entirely}';

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
        $themeDir = base_path('themes/'.$themeDirName);

        // Check if theme directory exists
        if (! file_exists($themeDir)) {
            $this->error(__('admin/command/theme-install.theme_not_found', ['themeName' => $themeName]));

            return Command::FAILURE;
        }

        // Check if theme is already registered
        $slug = Theme::resolveSlug($themeDirName);
        $exists = Theme::where('slug', $slug)->exists();

        if ($exists && ! $this->option('force')) {
            $this->error(__('admin/command/theme-install.already_registered', ['themeName' => $themeName]));

            return Command::FAILURE;
        }

        // If --force option is specified, only run migrations and seeders
        if ($exists && $this->option('force')) {
            $this->info('Theme already registered. Running migrations and seeders only...');

            // Run migrations
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
                    $this->info('Theme migrations executed successfully');
                } catch (\Exception $e) {
                    $this->warn('Failed to execute theme migrations: '.$e->getMessage());
                }
            }

            // Run seeders
            try {
                $seederClass = "Themes\\{$themeDirName}\\Database\\Seeders\\DatabaseSeeder";

                if (class_exists($seederClass)) {
                    $seeder = new $seederClass();
                    $seeder->setCommand($this);
                    $seeder->run();
                    $this->info('Theme seeder executed successfully');
                }
            } catch (\Exception $e) {
                $this->warn('Failed to execute theme seeder: '.$e->getMessage());
            }

            // Rebuild assets too so a forced reinstall picks up any source
            // changes that landed since the last install.
            $this->buildExtensionAssets($themeDir, $this->resolveAssetBuildMode());
            $this->call('dls:theme:symlink', [
                'action' => 'create',
                'theme' => $themeDirName,
            ]);

            $this->info('Theme migrations and seeders completed.');

            return Command::SUCCESS;
        }

        // Read theme information (in order: theme.json → composer.json → default values)
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

        // 1. Read from theme.json (highest priority)
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

        // 2. Fallback to composer.json
        if (file_exists($composerPath)) {
            $composerData = json_decode(file_get_contents($composerPath), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                // Get display-name from extra.dixlase
                $displayName = $displayName ?? $composerData['extra']['dixlase']['display-name'] ?? null;
                $packageName = $packageName ?? $composerData['name'] ?? null;

                // Get namespace from autoload in composer.json
                if (! $namespace && isset($composerData['autoload']['psr-4'])) {
                    $namespace = array_key_first($composerData['autoload']['psr-4']);
                    $namespace = rtrim($namespace, '\\');
                }

                $description = $description ?? $composerData['description'] ?? null;
                $license = $license ?? $composerData['license'] ?? null;

                // Get information from authors array
                if (! $author && isset($composerData['authors']) && is_array($composerData['authors']) && count($composerData['authors']) > 0) {
                    $author = $composerData['authors'][0]['name'] ?? null;
                    $email = $email ?? $composerData['authors'][0]['email'] ?? null;
                    $web = $web ?? $composerData['authors'][0]['homepage'] ?? null;
                }

                // Get version from extra.dixlase.version, or use root version if not present
                if ($version === '1.0.0') {
                    $version = $composerData['extra']['dixlase']['version'] ?? $composerData['version'] ?? '1.0.0';
                }
            }
        }

        // Set default values
        $displayName = $displayName ?? $themeDirName;
        $namespace = $namespace ?? "Themes\\{$themeDirName}";

        // Check for theme settings page
        $hasSettings = file_exists("{$themeDir}/app/Http/Controllers/Admin/Settings/Themes/ThemeSettingsController.php");

        // Register the theme
        $slug = Theme::resolveSlug($themeDirName);

        // Default an official-vendor theme to the official source so it
        // is updatable out of the box. A theme installed from a
        // configured source has its real source recorded afterwards via
        // the sidecar (persistSupplyChainMetadata), which overrides this
        // default; a third-party theme is left unlinked (officialLinkage
        // returns null when the package_name is not under the official
        // vendor).
        $linkage = app(ExtensionSourceManager::class)
            ->officialLinkage($slug, 'theme', $packageName);

        $theme = Theme::create([
            'name' => $displayName,
            'package_name' => $packageName,
            'directory' => $themeDirName,
            'slug' => $slug,
            'namespace' => $namespace,
            'description' => $description,
            'license' => $license,
            'author' => $author,
            'email' => $email,
            'url' => $web,
            'version' => $version,
            'has_settings' => $hasSettings,
            'source_id' => $linkage['source_id'] ?? null,
            'source_repo' => $linkage['source_repo'] ?? null,
            'installed_from_url' => $linkage['installed_from_url'] ?? null,
            'installation_method' => $linkage['installation_method'] ?? null,
            'installed_at' => now(),
        ]);

        // Update composer.local.json
        ComposerLocalHelper::syncAutoload();
        $this->info('Updated composer.local.json');

        // Run migrations (using ThemeMigrator)
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
                $this->info('Theme migrations executed successfully');
            } catch (\Exception $e) {
                \Log::error('ThemeInstall: Migration failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $this->warn('Failed to execute theme migrations: '.$e->getMessage());
            }
        }

        // Run seeders
        try {
            $seederClass = "Themes\\{$themeDirName}\\Database\\Seeders\\DatabaseSeeder";

            if (class_exists($seederClass)) {
                $seeder = new $seederClass();
                // Set command instance
                $seeder->setCommand($this);
                $seeder->run();
                $this->info('Theme seeder executed successfully');
            }
        } catch (\Exception $e) {
            $this->warn('Failed to execute theme seeder: '.$e->getMessage());
        }

        // Build front-end assets and link them into public/ so the rendered
        // pages can resolve theme JS/CSS. Without this step, freshly
        // installed themes throw Alpine "is not defined" errors and serve
        // unstyled pages until the operator runs the build by hand.
        $this->buildExtensionAssets($themeDir);
        $this->call('dls:theme:symlink', [
            'action' => 'create',
            'theme' => $themeDirName,
        ]);

        // Backfill any settings defaults the theme declares via
        // ProvidesSettingsDefaultsInterface. On a fresh install this is
        // typically a no-op (the theme's own DatabaseSeeder above already
        // filled the table) but the contract makes install / update
        // symmetric, and covers re-installs / --force where the seeder
        // was skipped or the table was hand-modified. Idempotent by design.
        $sync = app(\App\Services\Extension\ExtensionSettingsDefaultsSync::class)
            ->syncForExtension($themeDir, 'theme');
        if ($sync['synced_keys'] !== []) {
            $this->line(sprintf(
                'Settings defaults synced into %s: %d key(s).',
                $sync['table'],
                count($sync['synced_keys']),
            ));
        }

        // Discard any Blade views compiled against a prior version of this
        // theme (relevant for re-install / --force re-install where the
        // .blade.php files were replaced). Update / rollback paths clear the
        // view cache too — this keeps install consistent with them.
        \Illuminate\Support\Facades\Artisan::call('view:clear');

        $this->info(__('admin/command/theme-install.registered', ['themeName' => $themeName]));
        $this->info(__('admin/command/theme-install.activate_help', ['themeName' => $themeName]));

        return Command::SUCCESS;
    }
}
