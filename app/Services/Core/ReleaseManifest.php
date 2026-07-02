<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @internal Core only. Do not reference from plugins/themes.
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

namespace App\Services\Core;

use RuntimeException;

/**
 * Reads the optional release manifest a release ZIP can carry at its
 * payload root. Its presence tells the core updater that this release
 * ships more than just source + vendor — currently, that means a bundled
 * theme whose code the updater should apply in addition to the usual
 * source-only replace.
 *
 * A release without the manifest is the normal, backward-compatible
 * case: {@see readFromPayload()} returns null and the updater takes
 * exactly the pre-manifest code path.
 *
 * Manifest shape (as of v1):
 *
 * ```json
 * {
 *   "version": "0.2.8",
 *   "bundled_themes": [
 *     {
 *       "slug": "DixlaseOnePage",
 *       "version": "2.0.0",
 *       "path": "themes/DixlaseOnePage"
 *     }
 *   ]
 * }
 * ```
 *
 * The reader validates each declared `bundled_themes` entry — every
 * entry must be a JSON object with all three string fields, the `path`
 * must sit under `themes/` and its basename must equal the `slug` so a
 * hostile manifest cannot direct the updater at a file outside a
 * theme directory the operator would recognise.
 */
class ReleaseManifest
{
    public const FILENAME = '.dixlase-release.json';

    /**
     * @param  list<array{slug: string, version: string, path: string}>  $bundledThemes
     */
    private function __construct(
        public readonly ?string $version,
        public readonly array $bundledThemes,
    ) {}

    /**
     * Load the manifest that sits at the root of an extracted release
     * payload, or return null if none is present. A missing manifest
     * is not an error — most releases ship without one.
     */
    public static function readFromPayload(string $payloadRoot): ?self
    {
        $path = rtrim($payloadRoot, '/').'/'.self::FILENAME;
        if (! is_file($path)) {
            return null;
        }

        $raw = @file_get_contents($path);
        if ($raw === false) {
            throw new RuntimeException("Release manifest exists but could not be read: {$path}");
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            throw new RuntimeException("Release manifest is not valid JSON: {$path}");
        }

        return new self(
            version: isset($decoded['version']) && is_string($decoded['version']) ? $decoded['version'] : null,
            bundledThemes: self::normaliseBundledThemes($decoded['bundled_themes'] ?? []),
        );
    }

    /**
     * @return list<array{slug: string, version: string, path: string}>
     */
    private static function normaliseBundledThemes(mixed $raw): array
    {
        if ($raw === null || $raw === []) {
            return [];
        }

        if (! is_array($raw)) {
            throw new RuntimeException('Release manifest bundled_themes must be an array.');
        }

        $out = [];
        foreach ($raw as $index => $entry) {
            if (! is_array($entry)) {
                throw new RuntimeException("Release manifest bundled_themes[{$index}] must be an object.");
            }

            foreach (['slug', 'version', 'path'] as $required) {
                if (! isset($entry[$required]) || ! is_string($entry[$required]) || $entry[$required] === '') {
                    throw new RuntimeException("Release manifest bundled_themes[{$index}] is missing required string field '{$required}'.");
                }
            }

            $slug = $entry['slug'];
            $path = $entry['path'];

            // Refuse anything that isn't literally themes/<slug>. The updater
            // hands this path to a directory replace, so path traversal or a
            // slug/path mismatch would let a hostile release ZIP touch files
            // outside the theme directory the operator is expecting.
            if (! preg_match('#^themes/[A-Za-z0-9._-]+$#', $path)) {
                throw new RuntimeException("Release manifest bundled_themes[{$index}].path must match 'themes/<slug>' with no extra segments; got '{$path}'.");
            }
            if (basename($path) !== $slug) {
                throw new RuntimeException("Release manifest bundled_themes[{$index}].path basename must equal the slug; got path='{$path}' slug='{$slug}'.");
            }

            $out[] = [
                'slug' => $slug,
                'version' => $entry['version'],
                'path' => $path,
            ];
        }

        return $out;
    }

    public function hasBundledThemes(): bool
    {
        return $this->bundledThemes !== [];
    }
}
