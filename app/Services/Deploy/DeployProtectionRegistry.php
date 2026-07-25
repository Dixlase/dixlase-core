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

namespace App\Services\Deploy;

use App\Contracts\PluginIntegration\DeployProtectionRegistryInterface;
use App\DTO\PluginIntegration\DeployProtectionSource;

/**
 * Concrete registry that walks the `plugins/` and `themes/` directories
 * once per request and extracts the `deploy` block from every
 * extension's manifest. See the interface docblock for the design
 * rationale — this class is intentionally thin because the contract
 * carries the specification.
 *
 * Discovery is filesystem-based, matching the plugin loader's own
 * `plugin.json` / `theme.json` scan. The result is memoized on the
 * instance so a subsequent call is O(1). The container binds this as
 * a singleton (see `AppServiceProvider::register()`), so the walk
 * happens once per request across every deploy tool that queries it.
 *
 * Discovery is best-effort — a missing or malformed manifest drops
 * only that extension's contribution. A hard failure here would let
 * one broken plugin disable protection for the rest of the fleet,
 * which is exactly the opposite of what runtime-data protection is
 * meant to guarantee.
 */
class DeployProtectionRegistry implements DeployProtectionRegistryInterface
{
    /** @var list<DeployProtectionSource>|null */
    private ?array $cachedSources = null;

    /**
     * The scan roots correspond to the directory layouts used by the
     * Core plugin/theme loader. Kept as a constructor parameter so
     * tests can point the registry at a fixture tree without touching
     * the real installation.
     *
     * @param  string|null  $basePath  Override for base_path(); tests only.
     */
    public function __construct(private readonly ?string $basePath = null) {}

    public function protectedTables(): array
    {
        return $this->flattenUnique('tables');
    }

    public function protectedStoragePaths(): array
    {
        return $this->flattenUnique('storagePaths');
    }

    public function sources(): array
    {
        if ($this->cachedSources !== null) {
            return $this->cachedSources;
        }

        $sources = [];
        $sources = array_merge($sources, $this->discoverIn('plugins', 'plugin.json', 'plugin'));
        $sources = array_merge($sources, $this->discoverIn('themes', 'theme.json', 'theme'));

        return $this->cachedSources = $sources;
    }

    /**
     * Union every source's list for the given property, de-duplicating.
     *
     * @param  'tables'|'storagePaths'  $property
     * @return list<string>
     */
    private function flattenUnique(string $property): array
    {
        $union = [];
        foreach ($this->sources() as $source) {
            foreach ($source->{$property} as $entry) {
                $union[] = $entry;
            }
        }

        return array_values(array_unique($union));
    }

    /**
     * Walk one extension root (plugins/ or themes/) and return a source
     * per extension whose manifest carries a valid `deploy` block. An
     * extension without the block, or with a malformed one, is silently
     * skipped — see the class-level docblock for the rationale.
     *
     * @return list<DeployProtectionSource>
     */
    private function discoverIn(string $rootSubdir, string $manifestName, string $extensionType): array
    {
        $base = $this->resolveBasePath();
        $root = $base.'/'.$rootSubdir;

        if (! is_dir($root)) {
            return [];
        }

        $sources = [];
        $dirs = glob($root.'/*', GLOB_ONLYDIR) ?: [];

        foreach ($dirs as $dir) {
            $manifestPath = $dir.'/'.$manifestName;
            if (! is_file($manifestPath)) {
                continue;
            }

            $raw = @file_get_contents($manifestPath);
            if ($raw === false) {
                continue;
            }

            $manifest = json_decode($raw, true);
            if (! is_array($manifest)) {
                continue;
            }

            $deploySection = $manifest['deploy'] ?? null;
            if (! is_array($deploySection)) {
                continue;
            }

            $tables = $this->normalizeStringList($deploySection['protected_tables'] ?? []);
            $paths  = $this->normalizeStringList($deploySection['protected_storage_paths'] ?? []);

            if ($tables === [] && $paths === []) {
                continue;
            }

            $sources[] = new DeployProtectionSource(
                extensionName: basename($dir),
                extensionType: $extensionType,
                tables: $tables,
                storagePaths: $paths,
            );
        }

        return $sources;
    }

    /**
     * Normalize a manifest value into a de-duplicated `list<string>`,
     * silently dropping non-array inputs and empty entries.
     *
     * @return list<string>
     */
    private function normalizeStringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map(static fn ($v) => is_scalar($v) ? (string) $v : '', $value),
            static fn (string $s) => $s !== '',
        )));
    }

    private function resolveBasePath(): string
    {
        return $this->basePath ?? base_path();
    }
}
