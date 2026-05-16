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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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

namespace App\Console\Traits;

use Symfony\Component\Process\Process;

/**
 * Shared logic for building front-end assets that ship with a plugin
 * or theme.
 *
 * Each Dixlase extension may carry its own package.json / vite.config.js
 * because plugins and themes are independent of core's build pipeline.
 * When such an extension is installed, the files are placed on disk but
 * the generated assets (CSS / JS) are NOT produced — running the build
 * step is up to the installer. This trait centralises that step so both
 * dls:plugin:install and dls:theme:install behave the same way.
 *
 * The trait is intentionally tolerant: if npm or the build script is
 * missing, the install does NOT fail — assets are best-effort because
 * extensions that ship no front-end pipeline are perfectly valid.
 */
trait BuildsExtensionAssets
{
    /**
     * Build front-end assets for an extension if it ships a package.json.
     *
     * Modes:
     *   - 'auto'  (default) Build only when no compiled assets are
     *             present; otherwise skip so re-installs are cheap.
     *   - 'force' Always rebuild, even when assets look up-to-date.
     *             Use after pulling fresh sources.
     *   - 'skip'  Never build. Useful when an operator handles asset
     *             pipelines outside of the install flow.
     *
     * Runs `npm install` and (when a `build` script is declared) `npm
     * run build` inside the extension directory. Returns true on
     * success or when the extension has no package.json (= nothing to
     * build); false only when a build was attempted and failed.
     *
     * Output is forwarded to the console so the user sees the same
     * stream they would get from running the commands by hand.
     */
    protected function buildExtensionAssets(string $extensionPath, string $mode = 'auto'): bool
    {
        $packageJsonPath = $extensionPath.'/package.json';

        if (! file_exists($packageJsonPath)) {
            // No front-end pipeline shipped — nothing to do.
            return true;
        }

        if ($mode === 'skip') {
            $this->info('Skipping asset build (--skip-build).');

            return true;
        }

        if ($mode === 'auto' && $this->extensionAssetsAlreadyBuilt($extensionPath)) {
            $this->info('Assets already built — skipping (use --build to force a rebuild).');

            return true;
        }

        $packageData = json_decode(file_get_contents($packageJsonPath), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->warn("Invalid package.json at {$packageJsonPath} — skipping asset build.");

            return false;
        }

        $this->info('Installing npm packages for the extension...');
        if (! $this->runProcess(['npm', 'install'], $extensionPath)) {
            $this->warn('npm install failed — extension assets may not be available.');

            return false;
        }

        // Only run build if the extension actually declares a build script.
        // Extensions that ship only runtime deps (no Vite) are valid and
        // should not be flagged as failures here.
        $scripts = $packageData['scripts'] ?? [];
        if (! isset($scripts['build'])) {
            $this->info('No npm build script declared — runtime deps installed only.');

            return true;
        }

        $this->info('Building extension front-end assets...');
        if (! $this->runProcess(['npm', 'run', 'build'], $extensionPath)) {
            $this->warn('npm run build failed — extension assets may not be available.');

            return false;
        }

        $this->info('Extension front-end assets built successfully.');

        return true;
    }

    /**
     * Derive the asset-build mode from the standard --build /
     * --skip-build flags on the calling command.
     *
     * Precedence is intentional: --skip-build wins over --build because
     * an explicit "do not build" is the safer interpretation when the
     * operator passes both (e.g. a wrapper script that always adds
     * --build but tonight's run needs to skip).
     */
    protected function resolveAssetBuildMode(): string
    {
        if ($this->option('skip-build')) {
            return 'skip';
        }

        if ($this->option('build')) {
            return 'force';
        }

        return 'auto';
    }

    /**
     * Best-effort check for whether the extension already has compiled
     * assets on disk. Used by the 'auto' mode to skip redundant
     * rebuilds on every install.
     *
     * We look at the directories Dixlase extensions conventionally use
     * as a Vite outDir. False negatives are acceptable: at worst we
     * rebuild when we could have skipped. False positives are also
     * acceptable in auto mode because the operator can pass --build to
     * force a rebuild when sources change.
     */
    private function extensionAssetsAlreadyBuilt(string $extensionPath): bool
    {
        $candidates = [
            $extensionPath.'/resources/assets',
            $extensionPath.'/public/build',
            $extensionPath.'/dist',
        ];

        foreach ($candidates as $dir) {
            if (is_dir($dir) && ! $this->isDirectoryEmpty($dir)) {
                return true;
            }
        }

        return false;
    }

    private function isDirectoryEmpty(string $dir): bool
    {
        $entries = @scandir($dir);
        if ($entries === false) {
            return true;
        }

        foreach ($entries as $entry) {
            if ($entry !== '.' && $entry !== '..') {
                return false;
            }
        }

        return true;
    }

    /**
     * Run a command in $cwd, streaming output to the console.
     *
     * We use Symfony Process directly rather than $this->call() because
     * the build step shells out to npm (an external binary), not to an
     * Artisan command. Timeout is generous because cold npm installs
     * over a slow link can take a while.
     */
    private function runProcess(array $command, string $cwd): bool
    {
        $process = new Process($command, $cwd);
        $process->setTimeout(600);

        $process->run(function ($type, $buffer) {
            $this->getOutput()->write($buffer);
        });

        return $process->isSuccessful();
    }
}
