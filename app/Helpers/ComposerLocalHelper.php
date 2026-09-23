<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

namespace App\Helpers;

use App\Support\ComposerLocalManifest;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class ComposerLocalHelper
{
    /**
     * Path to composer.local.json file
     */
    protected static function getComposerLocalPath(): string
    {
        return base_path('composer.local.json');
    }

    /**
     * Automatically generate composer.local.json from plugins/ and themes/ directories
     *
     * @return bool Whether it succeeded
     */
    public static function syncAutoload(): bool
    {
        try {
            $composerLocalPath = self::getComposerLocalPath();

            // Generation lives in ComposerLocalManifest, shared with
            // scripts/sync-local-autoload.php. Both write this same file, so
            // any difference between them is silently decided by whichever ran
            // last -- see the class docblock for what that cost us.
            $manifest = ComposerLocalManifest::build(base_path());

            file_put_contents($composerLocalPath, ComposerLocalManifest::encode($manifest));

            // Regenerate autoload to reflect composer.local.json
            // Continue with only a warning even if it fails, since plugin placement itself is already complete
            self::regenerateAutoload();

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to sync composer.local.json: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Rebuild bootstrap/cache/packages.php after vendor/ has been swapped
     *
     * Laravel's package-discovery manifest lists the service providers of
     * every auto-discovered package in vendor/. Core update, core rollback
     * and a --refetch-vendor backup restore all replace vendor/ wholesale,
     * so a package the old tree had and the new one lacks stays listed —
     * for example a dev-only package on a baseline installed with dev
     * dependencies, swapped for a release's --no-dev vendor/. The next boot
     * then dies with `Class "...ServiceProvider" not found` and the whole
     * site returns 500.
     *
     * This must run in-process through Artisan::call(), never as a new
     * `php artisan` process. PackageManifest::build() only reads
     * vendor/composer/installed.json and never instantiates a provider, so
     * it succeeds here; a fresh process would boot the framework from the
     * stale manifest and die on the missing provider before the command ran.
     * bootstrap/cache/services.php needs no handling: ProviderRepository
     * recompiles it on the next boot once the provider list has changed.
     *
     * @return bool Whether it succeeded (failures are logged, not thrown)
     */
    public static function rebuildPackageManifest(): bool
    {
        try {
            $exitCode = Artisan::call('package:discover', ['--no-interaction' => true]);

            if ($exitCode !== 0) {
                Log::warning('package:discover failed after vendor swap', [
                    'exit_code' => $exitCode,
                    'output' => Artisan::output(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('Failed to rebuild the package manifest after vendor swap', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Execute composer dump-autoload to regenerate autoload classmap / psr-4
     *
     * Call immediately after adding/removing plugins/themes to reflect new PSR-4 mappings
     * to the Laravel runtime. In environments where the composer binary is not available,
     * continue with a warning log (do not treat as a fatal error)
     */
    protected static function regenerateAutoload(): void
    {
        try {
            $process = new Process(
                ['composer', 'dump-autoload', '--optimize', '--no-scripts'],
                base_path(),
                null,
                null,
                60
            );
            $process->run();

            if (! $process->isSuccessful()) {
                Log::warning('composer dump-autoload failed after composer.local.json sync', [
                    'exit_code' => $process->getExitCode(),
                    'stderr' => $process->getErrorOutput(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to regenerate autoload', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get the contents of composer.local.json
     */
    public static function getComposerLocalContent(): ?array
    {
        $composerLocalPath = self::getComposerLocalPath();

        if (! File::exists($composerLocalPath)) {
            return null;
        }

        $content = File::get($composerLocalPath);

        return json_decode($content, true);
    }

    /**
     * Check if composer.local.json exists
     */
    public static function exists(): bool
    {
        return File::exists(self::getComposerLocalPath());
    }

    /**
     * Check if a specific plugin is included in composer.local.json
     *
     * @param  string  $pluginName  Plugin name
     */
    public static function hasPlugin(string $pluginName): bool
    {
        $content = self::getComposerLocalContent();

        if ($content === null || ! isset($content['autoload']['psr-4'])) {
            return false;
        }

        $namespace = "Plugins\\{$pluginName}\\App\\";

        return isset($content['autoload']['psr-4'][$namespace]);
    }

    /**
     * Check if a specific theme is included in composer.local.json
     *
     * @param  string  $themeName  Theme name
     */
    public static function hasTheme(string $themeName): bool
    {
        $content = self::getComposerLocalContent();

        if ($content === null || ! isset($content['autoload']['psr-4'])) {
            return false;
        }

        $namespace = "Themes\\{$themeName}\\App\\";

        return isset($content['autoload']['psr-4'][$namespace]);
    }
}
