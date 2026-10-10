<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
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

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Log;

/**
 * Decides which directory an extension must be installed into, from its own
 * manifest.
 *
 * The directory name is not cosmetic: it is the middle segment of the PHP
 * namespace the extension's classes are autoloaded under. Core generates
 * `composer.local.json` from the directory basename alone
 * ({@see ComposerLocalManifest}), emitting `Plugins\{Name}\App\` for
 * `plugins/{Name}/app`. Composer matches a PSR-4 prefix case-sensitively, so a
 * directory whose spelling differs from the namespace the files declare
 * produces a mapping no class can be found through — `plugins/DixlaseSeo/`
 * holding `namespace Plugins\DixlaseSEO\App;` resolves only through an
 * optimized classmap, and only until someone dumps the autoloader without
 * `--optimize` (#488).
 *
 * So the name has to come from the manifest, which is the only place that
 * states the namespace, and never from the slug: `Str::studly('dixlase-seo')`
 * is `DixlaseSeo`, and no acronym survives that transform.
 *
 * The values are attacker-controlled — they come out of an uploaded or
 * downloaded archive, and the caller interpolates the result straight into
 * `base_path("plugins/{$dir}")` before any security scan runs — so every
 * candidate goes through {@see self::sanitise()}.
 */
final class ExtensionDirectoryName
{
    public const KIND_PLUGIN = 'plugin';

    public const KIND_THEME = 'theme';

    /**
     * The directory name a manifest asks for, or null when it names none that
     * can be trusted.
     *
     * Priority:
     *   1. `package` — an explicit statement of the install destination
     *   2. the final segment of `namespace` (`Plugins\MyPlugin` → `MyPlugin`)
     *   3. the final segment of `package_name` (`vendor/my-plugin` → `my-plugin`)
     *
     * Null means "this manifest does not decide"; the caller keeps whatever
     * name it already had in hand. Returning null rather than a corrected
     * name is deliberate — see {@see self::sanitise()}.
     *
     * @param  array<string, mixed>|null  $manifest  decoded plugin.json / theme.json
     * @param  self::KIND_*  $kind
     */
    public static function fromManifest(?array $manifest, string $kind): ?string
    {
        if (! is_array($manifest)) {
            return null;
        }

        $package = $manifest['package'] ?? null;
        if (is_string($package) && $package !== '') {
            return self::sanitise($package, $kind);
        }

        $namespace = $manifest['namespace'] ?? null;
        if (is_string($namespace) && $namespace !== '') {
            $lastSegment = self::lastSegment($namespace, '\\');
            if ($lastSegment !== null) {
                return self::sanitise($lastSegment, $kind);
            }
        }

        $packageName = $manifest['package_name'] ?? null;
        if (is_string($packageName) && $packageName !== '') {
            $lastSegment = self::lastSegment($packageName, '/');
            if ($lastSegment !== null) {
                return self::sanitise($lastSegment, $kind);
            }
        }

        return null;
    }

    /**
     * Constrain a manifest-supplied directory name to a single path segment.
     *
     * The caller does:
     *
     *     $correctPath = base_path("plugins/{$correctDir}");
     *     File::move($destinationPath, $correctPath);
     *
     * so `"package": "../public/shell"` resolved to the document root — the
     * extracted tree of attacker PHP would land somewhere the web server
     * executes it. That move happens at UPLOAD time, before the health and
     * security scan runs at install time, so the extension safety model never
     * got a chance to look at it.
     *
     * Returning null rather than a corrected name is deliberate: the caller
     * treats null as "keep the directory the archive already used", which is
     * the safe outcome. Silently rewriting `../public/shell` into
     * `publicshell` would install the extension under a name the manifest
     * never asked for.
     *
     * @param  self::KIND_*  $kind
     */
    public static function sanitise(string $candidate, string $kind): ?string
    {
        $candidate = trim($candidate);

        // A directory name, not a path: no separators, no traversal, no
        // absolute paths, no NUL. `.` and `..` are rejected by the pattern.
        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/', $candidate) !== 1) {
            Log::warning("Refused a {$kind} directory name that is not a single path segment", [
                'candidate' => $candidate,
            ]);

            return null;
        }

        if (str_contains($candidate, '..')) {
            Log::warning("Refused a {$kind} directory name containing traversal", [
                'candidate' => $candidate,
            ]);

            return null;
        }

        return $candidate;
    }

    /**
     * The final segment of a separated name, or null when it is empty.
     */
    private static function lastSegment(string $value, string $separator): ?string
    {
        $parts = explode($separator, trim($value, $separator));
        $lastSegment = end($parts);

        return ($lastSegment === false || $lastSegment === '') ? null : $lastSegment;
    }
}
