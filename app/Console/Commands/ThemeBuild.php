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
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class ThemeBuild extends Command
{
    /**
     * Timeout for each npm step, in seconds.
     *
     * A cold `npm install` (empty npm cache, first install from a release ZIP)
     * took longer than the old 600 seconds on a real install; the same step
     * finishes in about 20 seconds once the cache is warm.
     */
    private const NPM_TIMEOUT = 1800;

    /**
     * The name and signature of the console command.
     */
    protected $signature = 'dls:theme:build
                            {theme : The directory name of the theme to build}
                            {--no-install : Skip installing npm packages (use existing node_modules)}
                            {--no-symlink : Skip dls:theme:symlink invocation after build}';

    /**
     * The console command description.
     */
    protected $description = 'Install npm packages (npm ci when a lock file is shipped) and run npm run build for a theme, then create its public symlink';

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
        // the aggregator file listing every enabled plugin's Tailwind
        // content sources. Rebuild it before every theme build instead of
        // trusting what is on disk: a core update or rollback writes the
        // release's empty placeholder over it, and building against that
        // placeholder silently drops every class only a plugin's content
        // uses (Pages body markup, for example). When the aggregator cannot
        // run — no database yet, as when the install wizard's "Download
        // theme" button fires — fall back to seeding an empty stub if the
        // file is missing, so vite does not abort with
        // `Unable to resolve @import "..."` mid-build.
        $aggregatorPath = base_path(\App\Services\Tailwind\PluginSourceAggregator::OUTPUT_PATH);
        try {
            app(\App\Services\Tailwind\PluginSourceAggregator::class)->regenerate();
        } catch (\Throwable) {
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
            // npm ci when the theme ships a lock file: npm install rewrites
            // package-lock.json, which is a signed file, and a signed theme
            // would then fail verification. See BuildsExtensionAssets.
            $installCommand = File::exists("{$themePath}/package-lock.json")
                || File::exists("{$themePath}/npm-shrinkwrap.json")
                ? 'npm ci'
                : 'npm install';
            $this->info("Running {$installCommand} in themes/{$theme}...");
            $install = $this->runNpm($themePath, $npmEnv, $installCommand, $theme);

            if ($install === null) {
                return self::FAILURE;
            }

            if ($install->failed()) {
                $this->error($install->errorOutput() ?: $install->output());

                return self::FAILURE;
            }
        }

        $this->info("Running npm run build in themes/{$theme}...");
        $build = $this->runNpm($themePath, $npmEnv, 'npm run build', $theme);

        if ($build === null) {
            return self::FAILURE;
        }

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

    /**
     * Run one npm step. On a timeout, print what happened and how to retry
     * instead of letting the exception surface as a stack trace — this runs at
     * the end of a first install, where a trace is all the operator would see.
     *
     * @param  array<string, string>  $env
     */
    private function runNpm(string $path, array $env, string $command, string $theme): ?ProcessResult
    {
        try {
            return Process::path($path)->env($env)->timeout(self::NPM_TIMEOUT)->run($command);
        } catch (ProcessTimedOutException) {
            $this->error("`{$command}` did not finish within ".self::NPM_TIMEOUT.' seconds.');
            $this->line('The site itself is installed. Build the theme again with:');
            $this->line("  php artisan dls:theme:build {$theme}");
            $this->line('A second run is usually much faster because npm has cached the packages.');

            return null;
        }
    }
}
