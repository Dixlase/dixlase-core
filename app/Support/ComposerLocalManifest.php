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

declare(strict_types=1);

namespace App\Support;

/**
 * Builds the contents of `composer.local.json`, the generated file that maps
 * installed plugins and themes into the root autoloader through
 * wikimedia/composer-merge-plugin.
 *
 * Two callers write that file, and they must agree exactly:
 *
 *   - `scripts/sync-local-autoload.php`, run from Composer's post-install-cmd
 *     and by operators after a manual clone. It runs before the framework
 *     autoloader exists, so it requires this class by path.
 *   - {@see \App\Helpers\ComposerLocalHelper::syncAutoload()}, run on every
 *     plugin/theme install and delete, and by the installer, core update,
 *     core rollback and backup restore.
 *
 * They did not agree before 2026-09-24: each had its own directory detection,
 * and the helper emitted `psr-4` only. Because both write the same file,
 * whichever ran last won, so a plugin install through the admin panel silently
 * dropped every `autoload.files` entry the script had hoisted — and a missing
 * entry there is the mirror of a stale one: the helper function is simply
 * undefined wherever it is called. Keeping the generation in one place is the
 * point of this class; callers only decide where to write the result.
 */
final class ComposerLocalManifest
{
    /**
     * Marker left in an extension directory that has been uploaded or
     * downloaded but not installed yet.
     *
     * Composer requires every hoisted `autoload.files` entry unconditionally on
     * every request, so hoisting them for an extension that has only been
     * extracted would run its code before the pre-install scan and before the
     * operator confirmed the install. While the marker is present the
     * extension's `autoload.files` are withheld; the install command clears it.
     * PSR-4 mappings are still emitted -- they load nothing until a class is
     * referenced.
     */
    public const PENDING_INSTALL_MARKER = '.dixlase-pending-install';

    /**
     * Flag a freshly extracted extension as not installed yet.
     */
    public static function markPendingInstall(string $extensionDir): void
    {
        @file_put_contents(
            rtrim($extensionDir, '/').'/'.self::PENDING_INSTALL_MARKER,
            'Extracted '.date('c').'; withheld from autoload.files until installed.'.PHP_EOL
        );
    }

    /**
     * Clear the pending-install flag once the extension is being installed.
     */
    public static function clearPendingInstall(string $extensionDir): void
    {
        $marker = rtrim($extensionDir, '/').'/'.self::PENDING_INSTALL_MARKER;
        if (is_file($marker)) {
            @unlink($marker);
        }
    }

    public static function isPendingInstall(string $extensionDir): bool
    {
        return is_file(rtrim($extensionDir, '/').'/'.self::PENDING_INSTALL_MARKER);
    }

    /**
     * The whole `composer.local.json` document for the tree at `$baseDir`.
     *
     * @return array<string, mixed>
     */
    public static function build(string $baseDir): array
    {
        $baseDir = rtrim($baseDir, '/');

        $psr4 = [];
        $files = [];

        // Custom\Plugins\{P}\App\ and Custom\Themes\{T}\App\ are reserved for
        // the deferred plugin/theme logic-override loader -- see
        // .backlog/custom-overrides-plugin-theme.md for the design. The mapping
        // is emitted unconditionally so that once the loader lands, code already
        // written under those namespaces autoloads without a second migration
        // of this file. Composer tolerates missing target directories.
        foreach (self::detect($baseDir.'/plugins') as $name) {
            $psr4["Plugins\\{$name}\\App\\"] = "plugins/{$name}/app";
            $psr4["Plugins\\{$name}\\Database\\Factories\\"] = "plugins/{$name}/database/factories";
            $psr4["Plugins\\{$name}\\Database\\Seeders\\"] = "plugins/{$name}/database/seeders";
            $psr4["Plugins\\{$name}\\Tests\\"] = "plugins/{$name}/tests";
            $psr4["Custom\\Plugins\\{$name}\\App\\"] = "custom/plugins/{$name}/app";

            foreach (self::autoloadFiles($baseDir."/plugins/{$name}", "plugins/{$name}/") as $path) {
                $files[] = $path;
            }
        }

        foreach (self::detect($baseDir.'/themes') as $name) {
            $psr4["Themes\\{$name}\\App\\"] = "themes/{$name}/app";
            $psr4["Themes\\{$name}\\Database\\Factories\\"] = "themes/{$name}/database/factories";
            $psr4["Themes\\{$name}\\Database\\Seeders\\"] = "themes/{$name}/database/seeders";
            $psr4["Custom\\Themes\\{$name}\\App\\"] = "custom/themes/{$name}/app";

            foreach (self::autoloadFiles($baseDir."/themes/{$name}", "themes/{$name}/") as $path) {
                $files[] = $path;
            }
        }

        $autoload = ['psr-4' => $psr4];

        // Preserve insertion order but drop duplicates so two plugins listing
        // the same shared bootstrap file do not double-require it.
        $files = array_values(array_unique($files));
        if ($files !== []) {
            $autoload['files'] = $files;
        }

        return [
            '_comment' => 'This file is auto-generated. Do not edit manually.',
            '_generated_at' => date('Y-m-d H:i:s'),
            'autoload' => $autoload,
        ];
    }

    /**
     * The document as it is written to disk, trailing newline included, so
     * both callers produce a byte-identical file.
     *
     * @param  array<string, mixed>  $manifest
     */
    public static function encode(array $manifest): string
    {
        return json_encode(
            $manifest,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        )."\n";
    }

    /**
     * PSR-4 prefixes composer.local.json declares that the dumped autoloader
     * does not resolve.
     *
     * Composer's merge-plugin reads composer.local.json when it initialises,
     * which is before core's pre-autoload-dump hook has had the chance to
     * write it. On a tree where the file does not exist yet -- a release ZIP,
     * which ships without it because it is generated and gitignored -- the
     * very first `composer install` therefore dumps an autoloader with no
     * extension roots at all, and never dumps again. The bundled theme's
     * ServiceProvider is then unresolvable and the front page is a 500.
     *
     * A non-empty result means the tree needs one more dump.
     *
     * @return list<string>
     */
    public static function missingPsr4Roots(string $baseDir): array
    {
        $declared = self::declaredPsr4Roots($baseDir);

        if ($declared === []) {
            return [];
        }

        $dumpedPath = $baseDir.'/vendor/composer/autoload_psr4.php';

        if (! is_file($dumpedPath)) {
            return [];
        }

        $dumped = @include $dumpedPath;

        if (! is_array($dumped)) {
            return [];
        }

        return array_values(array_filter(
            $declared,
            static fn (string $prefix): bool => ! array_key_exists($prefix, $dumped)
        ));
    }

    /**
     * PSR-4 prefixes composer.local.json declares, or none when it is absent.
     *
     * @return list<string>
     */
    public static function declaredPsr4Roots(string $baseDir): array
    {
        $path = $baseDir.'/composer.local.json';

        if (! is_file($path)) {
            return [];
        }

        $document = json_decode((string) file_get_contents($path), true);

        if (! is_array($document)) {
            return [];
        }

        $psr4 = $document['autoload']['psr-4'] ?? [];

        if (! is_array($psr4)) {
            return [];
        }

        return array_values(array_filter(array_keys($psr4), 'is_string'));
    }

    /**
     * Installed extension directory names directly under `$parentDir`, sorted.
     *
     * Registering a move-aside copy here is not a cosmetic problem: its
     * `autoload.files` entries are hoisted into the flat list below, and
     * Composer requires those unconditionally at bootstrap, so deleting a copy
     * that was registered takes down every request and every artisan command.
     * Brand hit exactly this on 2026-09-23. Hence two independent guards --
     * the name rule, and a manifest requirement.
     *
     * @return list<string>
     */
    public static function detect(string $parentDir): array
    {
        if (! is_dir($parentDir)) {
            return [];
        }

        $names = [];

        foreach (scandir($parentDir) ?: [] as $entry) {
            if (! ExtensionDirectories::isInstalledName($entry)) {
                continue;
            }

            $dir = $parentDir.'/'.$entry;
            if (! is_dir($dir) || ! is_dir($dir.'/app')) {
                continue;
            }

            // An installed extension always declares itself. This also skips a
            // bare directory that merely happens to contain an `app/`.
            if (! is_file($dir.'/plugin.json') && ! is_file($dir.'/theme.json')) {
                continue;
            }

            $names[] = $entry;
        }

        sort($names);

        return $names;
    }

    /**
     * An extension's own `autoload.files`, rebased to paths relative to the
     * Core root so they can be merged into the flat list.
     *
     * PSR-4 cannot autoload non-class symbols, so an extension that ships
     * `function dls_*()` helpers lists the file here and we hoist it, making
     * the function available before the extension's ServiceProvider boots.
     *
     * Returns an empty array when the file is missing, unreadable or has no
     * `autoload.files` section -- only well-formed extensions contribute.
     *
     * @return list<string>
     */
    private static function autoloadFiles(string $extensionDir, string $relPrefix): array
    {
        // Not installed yet: its files would run on the next request.
        if (self::isPendingInstall($extensionDir)) {
            return [];
        }

        $composerJson = $extensionDir.'/composer.json';
        if (! is_file($composerJson) || ! is_readable($composerJson)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($composerJson), true);
        if (! is_array($decoded)) {
            return [];
        }

        $files = $decoded['autoload']['files'] ?? [];
        if (! is_array($files)) {
            return [];
        }

        $result = [];
        foreach ($files as $entry) {
            if (! is_string($entry) || $entry === '') {
                continue;
            }
            // An entry must stay inside the extension's own directory. A path
            // with `..`, a backslash or a NUL could otherwise point Composer at
            // any PHP file under the application root.
            $relative = ltrim($entry, '/');
            if (str_contains($relative, "\0") || str_contains($relative, '\\')
                || in_array('..', explode('/', $relative), true)) {
                continue;
            }

            $result[] = $relPrefix.$relative;
        }

        return $result;
    }
}
