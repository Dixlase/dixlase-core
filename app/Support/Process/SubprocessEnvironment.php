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

namespace App\Support\Process;

/**
 * @internal For Core use only. Do not reference from plugins/themes
 *
 * Environment for subprocesses core starts (composer, npm).
 *
 * Symfony's Process builds a child environment from the intersection of
 * getenv() and $_SERVER (Process::getDefaultEnv()). Laravel writes every
 * .env entry into $_SERVER, so on a SAPI that does not publish the real
 * environment there — the built-in server, `php artisan serve`, PHP-FPM
 * with the default clear_env=yes — the intersection collapses to the .env
 * keys and PATH disappears from the child.
 *
 * The failure is quiet: `composer` itself is still found, but its
 * `#!/usr/bin/env php` shebang cannot find php, so the run dies with
 * exit 127 and core only logs a warning. Installing a plugin from the
 * admin panel then leaves the autoload map untouched and the plugin's
 * classes unresolvable.
 */
final class SubprocessEnvironment
{
    /**
     * Used when neither the process environment nor $_SERVER carries a PATH.
     */
    public const FALLBACK_PATH = '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin';

    /**
     * Variables to hand a subprocess, with PATH guaranteed.
     *
     * Symfony merges this on top of the environment it derives itself, so
     * only the keys that need repairing have to be listed.
     *
     * @param  array<string, string>  $extra
     * @return array<string, string>
     */
    public static function inherit(array $extra = []): array
    {
        return array_merge(['PATH' => self::path()], $extra);
    }

    /**
     * The PATH the parent process was started with.
     */
    public static function path(): string
    {
        $candidates = [
            getenv('PATH'),
            $_SERVER['PATH'] ?? null,
            $_ENV['PATH'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return $candidate;
            }
        }

        return self::FALLBACK_PATH;
    }

    /**
     * Directories on PATH, in order.
     *
     * @return array<int, string>
     */
    public static function pathDirectories(): array
    {
        $directories = [];

        foreach (explode(PATH_SEPARATOR, self::path()) as $directory) {
            $directory = rtrim(trim($directory), '/');
            if ($directory !== '') {
                $directories[] = $directory;
            }
        }

        return $directories;
    }
}
