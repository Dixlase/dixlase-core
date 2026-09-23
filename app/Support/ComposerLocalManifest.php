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
            $result[] = $relPrefix.ltrim($entry, '/');
        }

        return $result;
    }
}
