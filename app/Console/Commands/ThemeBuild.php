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

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class ThemeBuild extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'dls:theme:build
                            {theme : The directory name of the theme to build}
                            {--no-install : Skip npm install (use existing node_modules)}
                            {--no-symlink : Skip dls:theme:symlink invocation after build}';

    /**
     * The console command description.
     */
    protected $description = 'Run npm install + npm run build for a theme and create its public symlink';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $theme = $this->argument('theme');
        $themePath = base_path("themes/{$theme}");

        if (! File::isDirectory($themePath)) {
            $this->error("Theme directory not found: themes/{$theme}");

            return self::FAILURE;
        }

        if (! File::exists("{$themePath}/package.json")) {
            $this->error("themes/{$theme}/package.json not found");

            return self::FAILURE;
        }

        // Themes' tailwind.css @imports
        // resources/src/common/css/dixlase-tailwind-plugin-sources.css —
        // the aggregator file written by
        // dls:tailwind:regenerate-plugin-sources whenever plugins change
        // (and once at install-wizard completion). On a fresh clone, or
        // when the install wizard's "Download theme" button fires
        // before the regenerate has had a chance to run, that file does
        // not exist yet and vite aborts with
        // `Unable to resolve @import "..."` mid-build. Seed an empty
        // stub in the exact format the aggregator emits when no plugin
        // contributes sources, matching the docker-installer's setup.sh
        // behaviour. If the file already exists (real aggregator output
        // from a prior regenerate run) leave it alone — overwriting
        // would clobber real plugin source declarations.
        $aggregatorPath = base_path(\App\Services\Tailwind\PluginSourceAggregator::OUTPUT_PATH);
        if (! File::exists($aggregatorPath)) {
            File::ensureDirectoryExists(dirname($aggregatorPath), 0775);
            File::put($aggregatorPath, <<<'CSS'
/*
 * AUTO-GENERATED placeholder seeded by dls:theme:build.
 * The Dixlase install wizard and plugin lifecycle commands overwrite
 * this file via php artisan dls:tailwind:regenerate-plugin-sources.
 */

/* No enabled plugin currently declares Tailwind content sources. */

CSS);
        }

        // npm defaults its cache to $HOME/.npm. In a php-fpm container
        // $HOME is typically /var/www (root-owned by the Docker image),
        // so a CLI invocation (uid = root) writes /var/www/.npm with
        // root ownership, and a subsequent web-triggered invocation
        // (uid = www-data, 33) — notably the install wizard's
        // "Download theme" button, which routes through here via
        // InstallThemeDownloader::download() → Artisan::call('dls:theme:build')
        // — then permanently fails with EACCES on mkdir /var/www/.npm
        // and leaves a partial node_modules behind. Pin npm's cache to
        // a project-local directory under storage/ that is already owned
        // by whatever user runs Laravel, so CLI and web-request paths
        // do not fight over the same root-owned home dir.
        $npmCache = storage_path('app/private/npm-cache');
        if (! File::isDirectory($npmCache)) {
            File::ensureDirectoryExists($npmCache, 0775);
        }
        $npmEnv = ['NPM_CONFIG_CACHE' => $npmCache];

        if (! $this->option('no-install')) {
            $this->info("Running npm install in themes/{$theme}...");
            $install = Process::path($themePath)->env($npmEnv)->timeout(600)->run('npm install');

            if ($install->failed()) {
                $this->error($install->errorOutput() ?: $install->output());

                return self::FAILURE;
            }
        }

        $this->info("Running npm run build in themes/{$theme}...");
        $build = Process::path($themePath)->env($npmEnv)->timeout(600)->run('npm run build');

        if ($build->failed()) {
            $this->error($build->errorOutput() ?: $build->output());

            return self::FAILURE;
        }

        // Success criterion: vite must have emitted manifest.json
        $manifest = "{$themePath}/resources/assets/manifest.json";
        if (! File::exists($manifest)) {
            $this->error('Build completed but resources/assets/manifest.json was not generated');

            return self::FAILURE;
        }

        if (! $this->option('no-symlink')) {
            $this->call('dls:theme:symlink', [
                'action' => 'create',
                'theme' => $theme,
            ]);
        }

        $this->info("Theme '{$theme}' built successfully.");

        return self::SUCCESS;
    }
}
