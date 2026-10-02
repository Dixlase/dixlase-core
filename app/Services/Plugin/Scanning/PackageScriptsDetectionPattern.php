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

namespace App\Services\Plugin\Scanning;

/**
 * @internal For Core use only. Do not reference from plugins/themes
 *
 * Flags npm lifecycle scripts that run at install time.
 *
 * An extension's package.json may declare scripts npm runs by itself when
 * packages are installed (preinstall / install / postinstall / prepare and
 * their pre / post hooks). They execute arbitrary commands as the web
 * server user. Core installs with `--ignore-scripts`, so a Dixlase
 * extension never needs one -- shipping one is either a mistake or an
 * attempt to run code before the extension is scanned (security review
 * X6). Build hooks such as `build` / `prebuild` are not flagged: core runs
 * `npm run build` on purpose.
 *
 * Reported as a dangerous API (`dangerous_api.npm_lifecycle_scripts`), so
 * the health score treats it like a call to exec().
 */
class PackageScriptsDetectionPattern extends DetectionPattern
{
    /**
     * Scripts npm runs during `npm install` / `npm ci` of the package.
     */
    public const INSTALL_TIME_SCRIPTS = [
        'preinstall',
        'install',
        'postinstall',
        'preprepare',
        'prepare',
        'postprepare',
        'prepublish',
        'dependencies',
    ];

    public function permissionKey(): string
    {
        return 'dangerous_api.npm_lifecycle_scripts';
    }

    /**
     * @return array<string>
     */
    public function detectFiles(string $extensionDir): array
    {
        $path = $extensionDir.'/package.json';
        if (! is_file($path)) {
            return [];
        }

        $data = json_decode((string) file_get_contents($path), true);
        $scripts = is_array($data) && is_array($data['scripts'] ?? null) ? $data['scripts'] : [];

        $found = [];
        foreach (self::INSTALL_TIME_SCRIPTS as $name) {
            if (isset($scripts[$name])) {
                $found[] = "package.json (scripts.{$name})";
            }
        }

        return $found;
    }
}
