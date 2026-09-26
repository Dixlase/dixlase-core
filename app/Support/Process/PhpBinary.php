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
 * Locates a CLI PHP interpreter for the subprocesses core starts.
 *
 * PHP_BINARY is the binary of the *running* process, which is only an
 * interpreter that can run a script when the current SAPI is CLI. Under
 * PHP-FPM it is the FPM binary: handing it a script makes it print its own
 * usage and exit 64, with nothing on stderr. That is what made every
 * `composer dump-autoload` core fires from a web request a silent no-op on
 * FPM — an admin-panel plugin install left the autoload map untouched, and
 * a fresh install from the release ZIP served a 500 because the bundled
 * theme's provider was never autoloadable.
 */
final class PhpBinary
{
    /**
     * Absolute path to a CLI interpreter, or null when none was found.
     *
     * The caller is expected to fall back to a form that does not need one
     * (running a script through its shebang, or a command on PATH).
     */
    public static function cli(): ?string
    {
        foreach (self::candidates() as $candidate) {
            if (self::isCliInterpreter($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private static function candidates(): array
    {
        $candidates = [];

        // Cheapest and most accurate when it applies: a CLI process already
        // knows its own interpreter.
        if (PHP_SAPI === 'cli') {
            $candidates[] = PHP_BINARY;
        }

        // The bin/ of this build. On the official php:*-fpm images the CLI
        // sits here even though PHP_BINARY points at sbin/php-fpm.
        $candidates[] = PHP_BINDIR.'/php';
        $candidates[] = PHP_BINDIR.'/php'.PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;

        foreach (SubprocessEnvironment::pathDirectories() as $directory) {
            $candidates[] = $directory.'/php';
        }

        $candidates[] = '/usr/local/bin/php';
        $candidates[] = '/usr/bin/php';

        return array_values(array_unique($candidates));
    }

    /**
     * Whether the path is an executable that runs a script when given one.
     *
     * The name check is what keeps php-fpm and php-cgi out: both are
     * executable PHP binaries that ignore a script argument.
     */
    private static function isCliInterpreter(string $path): bool
    {
        if ($path === '' || ! is_file($path) || ! is_executable($path)) {
            return false;
        }

        $name = basename($path);

        return ! str_contains($name, 'fpm') && ! str_contains($name, 'cgi');
    }
}
