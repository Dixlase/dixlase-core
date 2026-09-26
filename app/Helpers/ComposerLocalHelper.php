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
use App\Support\Process\PhpBinary;
use App\Support\Process\SubprocessEnvironment;
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
            $command = self::composerCommand(['dump-autoload', '--optimize', '--no-scripts']);

            $process = new Process(
                $command,
                base_path(),
                self::composerEnvironment(),
                null,
                60
            );
            $process->run();

            if (! $process->isSuccessful()) {
                // Both composer and php-fpm report on stdout, so a failure
                // logged from stderr alone said nothing at all. Record the
                // command too: without it an exit code cannot be traced back
                // to the binary that produced it.
                Log::warning('composer dump-autoload failed after composer.local.json sync', [
                    'command' => implode(' ', $command),
                    'exit_code' => $process->getExitCode(),
                    'stdout' => mb_substr(trim($process->getOutput()), 0, 2000),
                    'stderr' => mb_substr(trim($process->getErrorOutput()), 0, 2000),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to regenerate autoload', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Command line for a composer run.
     *
     * Composer is normally started through its `#!/usr/bin/env php` shebang,
     * which needs both composer and php on the child's PATH. When the
     * composer entry point can be located and a CLI interpreter resolved,
     * start it with that interpreter instead, so the run no longer depends
     * on how the web server's PATH is set up.
     *
     * The interpreter comes from PhpBinary, not from PHP_BINARY: under
     * PHP-FPM the latter is the FPM binary, which ignores the script it is
     * handed, prints its usage to stdout and exits 64. Every dump-autoload
     * core fired from a web request died that way — logged as a warning
     * with an empty stderr, and otherwise silent.
     *
     * @param  array<int, string>  $arguments
     * @return array<int, string>
     */
    protected static function composerCommand(array $arguments): array
    {
        $composer = self::locateComposer();

        if ($composer !== null) {
            $php = PhpBinary::cli();

            if ($php !== null) {
                return array_merge([$php, $composer], $arguments);
            }

            // No CLI interpreter: fall back to the shebang, which needs php
            // on the child's PATH (SubprocessEnvironment guarantees one).
            if (is_executable($composer)) {
                return array_merge([$composer], $arguments);
            }
        }

        return array_merge(['composer'], $arguments);
    }

    /**
     * Path to a composer entry point that is a PHP script, or null.
     */
    protected static function locateComposer(): ?string
    {
        $candidates = [];

        // Set by Composer itself when core runs inside a composer script.
        $fromEnv = getenv('COMPOSER_BINARY') ?: ($_SERVER['COMPOSER_BINARY'] ?? '');
        if (is_string($fromEnv) && $fromEnv !== '') {
            $candidates[] = $fromEnv;
        }

        $candidates[] = base_path('composer.phar');

        foreach (SubprocessEnvironment::pathDirectories() as $directory) {
            $candidates[] = $directory.'/composer';
            $candidates[] = $directory.'/composer.phar';
        }

        foreach ($candidates as $candidate) {
            if (is_file($candidate) && is_readable($candidate) && self::isPhpScript($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Whether a file is a PHP script or phar this interpreter can run.
     *
     * Guards against a `composer` that is a shell wrapper (a docker or
     * asdf shim, for instance) — handing that to PHP_BINARY would fail.
     */
    protected static function isPhpScript(string $path): bool
    {
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return false;
        }

        $head = (string) fread($handle, 256);
        fclose($handle);

        if (str_starts_with($head, '<?php')) {
            return true;
        }

        $firstLine = strtok($head, "\n");
        if (! is_string($firstLine) || ! str_starts_with($firstLine, '#!')) {
            return false;
        }

        return str_contains($firstLine, 'php');
    }

    /**
     * Environment for the composer subprocess.
     *
     * PATH is restored (see SubprocessEnvironment), and composer is given a
     * writable home when the process has none — under PHP-FPM, HOME is
     * often unset, and composer then tries to write its cache to / and
     * fails.
     *
     * @return array<string, string>
     */
    protected static function composerEnvironment(): array
    {
        $extra = [];

        $home = getenv('HOME') ?: ($_SERVER['HOME'] ?? '');
        if (! is_string($home) || $home === '' || ! is_writable($home)) {
            $composerHome = storage_path('app/private/composer-home');
            if (! is_dir($composerHome)) {
                @mkdir($composerHome, 0775, true);
            }
            if (is_dir($composerHome)) {
                $extra['COMPOSER_HOME'] = $composerHome;
            }
        }

        return SubprocessEnvironment::inherit($extra);
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
