<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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

namespace App\Services\Plugin;

use App\Services\Plugin\Scanning\PatternRegistry;
use Illuminate\Support\Facades\File;

/**
 * @internal Core use only. Do not reference from plugins/themes
 *
 * Auto-sync the permissions/declares sections of plugin.json to match the implementation code.
 *
 * - permissions: Fill true/false from PatternRegistry detection results
 * - declares: Fill true/false/arrays from actual file existence
 *
 * Preserve fields that are "written by human judgment" such as manually edited
 * `_optional`, `_notes`, `content.read_other_plugins`, etc.
 */
class PluginManifestSyncService
{
    /**
     * Target keys (dot notation) to write to the permissions tree in plugin.json.
     *
     * PatternRegistry also detects additional categories for scanning (dangerous_api / csp /
     * database.core_tables_read, etc.), but they are only for health scoring and
     * not included in the manifest's permissions section (different schema).
     */
    private const MANIFEST_PERMISSION_KEYS = [
        'database.own_tables',
        'storage.own_directory',
        'storage.public_uploads',
        'storage.temp_files',
        'settings.read_core',
        'settings.write_own',
        'members.read',
        'members.write',
        'members.create',
        'members.delete',
        'mail.send',
        'mail.bulk_send',
        'system.register_shortcodes',
        'system.register_middleware',
        'system.register_commands',
        'system.register_blade_directives',
        'system.modify_routes',
    ];

    public function __construct(
        protected PatternRegistry $patternRegistry,
    ) {}

    /**
     * Returns the sync result (plugin.json before/after changes and diff information).
     *
     * Does not write (diff calculation only). Actual file update is done by the caller.
     *
     * @return array{
     *     manifest_path: string,
     *     before: array<string, mixed>,
     *     after: array<string, mixed>,
     *     changed: bool,
     *     changes: array<int, array{path: string, before: mixed, after: mixed}>,
     *     evidence: array<string, array>,
     * }
     */
    public function diff(string $pluginDir, string $type = 'plugin'): array
    {
        $manifestFile = $type === 'theme' ? 'theme.json' : 'plugin.json';
        $manifestPath = "{$pluginDir}/{$manifestFile}";

        if (! File::exists($manifestPath)) {
            throw new \RuntimeException("Manifest file not found: {$manifestPath}");
        }

        $rawJson = File::get($manifestPath);
        $before = json_decode($rawJson, true);
        if (! is_array($before)) {
            throw new \RuntimeException("Manifest is not valid JSON: {$manifestPath}");
        }
        // Record keys that were `{}` in the original JSON (convert to object to prevent becoming `[]` on re-encoding)
        $emptyObjectKeys = $this->detectEmptyObjectKeys($rawJson);

        $scan = $this->patternRegistry->scan($pluginDir, $type);
        $detected = $scan['permissions'];

        $after = $before;
        $after['permissions'] = $this->mergePermissions($before['permissions'] ?? [], $detected);
        $after['declares'] = $this->resolveDeclares($pluginDir, $before['declares'] ?? [], $type);

        $changes = $this->computeChanges($before, $after);

        return [
            'manifest_path' => $manifestPath,
            'before' => $before,
            'after' => $after,
            'changed' => ! empty($changes),
            'changes' => $changes,
            'evidence' => $scan['evidence'],
            'empty_object_keys' => $emptyObjectKeys,
        ];
    }

    /**
     * Execute sync and write the manifest.
     *
     * @return array Return value of diff() above
     */
    public function sync(string $pluginDir, string $type = 'plugin'): array
    {
        $result = $this->diff($pluginDir, $type);

        if ($result['changed']) {
            // Convert keys that should remain as empty objects ({}) to stdClass (correction to restore
            // original form on re-encoding due to PHP json_decode converting `{}` to `[]`)
            $payload = $this->restoreEmptyObjectShape($result['after'], $result['empty_object_keys'] ?? []);
            $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            File::put($result['manifest_path'], $json."\n");
        }

        return $result;
    }

    /**
     * Extract key paths that were `{}` (empty objects) in the original JSON string.
     *
     * This is because if the key remains an empty array on re-encoding, it will be output as `[]`,
     * Used to cast to stdClass to preserve the original shape
     *
     * @return array<int, string> Dot notation paths (e.g., ["files", "permissions._notes"])
     */
    protected function detectEmptyObjectKeys(string $rawJson): array
    {
        $keys = [];

        // Detect top-level `"key": {}`
        if (preg_match_all('/^\s{4}"([^"]+)"\s*:\s*\{\s*\}/m', $rawJson, $matches)) {
            foreach ($matches[1] as $key) {
                $keys[] = $key;
            }
        }
        // Also handle 1-level nesting like `permissions._notes: {}`
        if (preg_match_all('/^\s{8}"([^"]+)"\s*:\s*\{\s*\}/m', $rawJson, $matches)) {
            // Record all 1-level nested keys (especially for _notes) since inferring the parent key is difficult
            foreach ($matches[1] as $key) {
                $keys[] = "*.{$key}";
            }
        }

        return $keys;
    }

    /**
     * Replace empty arrays with stdClass for paths detected by detectEmptyObjectKeys()
     *
     * @param  array<string, mixed>  $manifest
     * @param  array<int, string>  $emptyObjectKeys
     * @return array<string, mixed>
     */
    protected function restoreEmptyObjectShape(array $manifest, array $emptyObjectKeys): array
    {
        foreach ($emptyObjectKeys as $path) {
            // Wildcard nesting: e.g., `*.{key}` objectifies `{key}` in all top-level child arrays if empty
            if (str_starts_with($path, '*.')) {
                $childKey = substr($path, 2);
                foreach ($manifest as $topKey => $topVal) {
                    if (is_array($topVal) && isset($topVal[$childKey]) && is_array($topVal[$childKey]) && empty($topVal[$childKey])) {
                        $manifest[$topKey][$childKey] = (object) [];
                    }
                }

                continue;
            }

            // Top-level simple path
            if (isset($manifest[$path]) && is_array($manifest[$path]) && empty($manifest[$path])) {
                $manifest[$path] = (object) [];
            }
        }

        return $manifest;
    }

    /**
     * Merge detected permissions into the existing permissions tree
     *
     * - Expand dot notation detection results (e.g., `mail.send`) into nested structure
     * - Keep in tree even if not detected (false) to indicate unused
     * - Preserve manual inputs like `_optional` / `_notes` / `content.read_other_plugins`
     */
    protected function mergePermissions(array $existing, array $detected): array
    {
        $merged = $existing;

        // Only target keys that exist in the manifest schema
        foreach (self::MANIFEST_PERMISSION_KEYS as $key) {
            $value = $detected[$key] ?? false;
            $segments = explode('.', $key);
            $this->setNested($merged, $segments, (bool) $value);
        }

        // Ensure permissions section template (fill with false)
        $merged = $this->ensurePermissionShape($merged);

        return $merged;
    }

    /**
     * Ensure the permissions section has the expected structure (all categories enumerated)
     */
    protected function ensurePermissionShape(array $permissions): array
    {
        $defaults = [
            'database' => [
                'own_tables' => false,
                'core_tables' => $permissions['database']['core_tables'] ?? [],
            ],
            'storage' => [
                'own_directory' => false,
                'public_uploads' => false,
                'temp_files' => false,
            ],
            'settings' => [
                'read_core' => false,
                'write_own' => false,
            ],
            'members' => [
                'read' => false,
                'write' => false,
                'create' => false,
                'delete' => false,
            ],
            'mail' => [
                'send' => false,
                'bulk_send' => false,
            ],
            'content' => [
                'read_other_plugins' => $permissions['content']['read_other_plugins'] ?? [],
                'write_other_plugins' => $permissions['content']['write_other_plugins'] ?? [],
            ],
            'system' => [
                'register_shortcodes' => false,
                'register_middleware' => false,
                'register_commands' => false,
                'register_blade_directives' => false,
                'modify_routes' => false,
            ],
        ];

        // Overwrite defaults with existing permissions values (recursive merge per category)
        foreach ($defaults as $cat => $defaultValues) {
            $permissions[$cat] = array_merge($defaultValues, $permissions[$cat] ?? []);
        }

        // Manual input fields (preserved)
        $permissions['_optional'] = $permissions['_optional'] ?? [];
        $permissions['_notes'] = $permissions['_notes'] ?? ['ja' => '', 'en' => ''];

        return $permissions;
    }

    /**
     * Resolve the declares section from actual files
     */
    protected function resolveDeclares(string $pluginDir, array $existing, string $type): array
    {
        $declares = $existing;

        // configs
        $configs = $existing['configs'] ?? [];
        $configs['roles'] = File::exists("{$pluginDir}/config/admin/roles.php");
        $configs['database_cleanup'] = File::exists("{$pluginDir}/config/admin/database-cleanup.php");
        $configs['navigation'] = File::exists("{$pluginDir}/config/admin/navigation.php");
        $declares['configs'] = $configs;

        // contracts: existence of app/Contracts/PluginIntegration/*.php or app/Contracts/*.php
        $contractsDir = "{$pluginDir}/app/Contracts";
        $contracts = [];
        if (File::isDirectory($contractsDir)) {
            $contracts = $this->collectClassNames($contractsDir);
        }
        // Deduplicate including existing manual values
        $existingContracts = $existing['contracts'] ?? [];
        if (is_array($existingContracts)) {
            $contracts = array_values(array_unique(array_merge($contracts, $existingContracts)));
        }
        $declares['contracts'] = $contracts;

        // migrations: file exists
        $declares['migrations'] = File::isDirectory("{$pluginDir}/database/migrations")
            && count(File::files("{$pluginDir}/database/migrations")) > 0;

        // commands: file exists
        $commandsDir = "{$pluginDir}/app/Console/Commands";
        $declares['commands'] = File::isDirectory($commandsDir)
            && count(File::allFiles($commandsDir)) > 0;

        // middleware: file exists
        $middlewareDir = "{$pluginDir}/app/Http/Middleware";
        $declares['middleware'] = File::isDirectory($middlewareDir)
            && count(File::allFiles($middlewareDir)) > 0;

        // assets section (preserve existing manual entries)
        if (isset($existing['assets'])) {
            $declares['assets'] = $existing['assets'];
        }

        return $declares;
    }

    /**
     * Collect PHP class names under the directory
     *
     * @return array<int, string>
     */
    protected function collectClassNames(string $dir): array
    {
        $classes = [];
        if (! File::isDirectory($dir)) {
            return $classes;
        }
        foreach (File::allFiles($dir) as $file) {
            if ($file->getExtension() === 'php') {
                $classes[] = $file->getBasename('.php');
            }
        }
        sort($classes);

        return array_values(array_unique($classes));
    }

    /**
     * Set a value at the specified path in a nested array
     *
     * @param  array<int, string>  $segments
     */
    protected function setNested(array &$target, array $segments, mixed $value): void
    {
        $current = &$target;
        foreach ($segments as $i => $segment) {
            $isLast = $i === count($segments) - 1;
            if ($isLast) {
                $current[$segment] = $value;
            } else {
                if (! isset($current[$segment]) || ! is_array($current[$segment])) {
                    $current[$segment] = [];
                }
                $current = &$current[$segment];
            }
        }
    }

    /**
     * Flatten and return the diff between before/after (for human display)
     *
     * @return array<int, array{path: string, before: mixed, after: mixed}>
     */
    protected function computeChanges(array $before, array $after, string $prefix = ''): array
    {
        $changes = [];

        $allKeys = array_unique(array_merge(array_keys($before), array_keys($after)));
        foreach ($allKeys as $key) {
            $path = $prefix === '' ? $key : "{$prefix}.{$key}";
            $b = $before[$key] ?? null;
            $a = $after[$key] ?? null;

            if (is_array($b) && is_array($a) && ! $this->isList($b) && ! $this->isList($a)) {
                $changes = array_merge($changes, $this->computeChanges($b, $a, $path));

                continue;
            }

            if ($b !== $a) {
                $changes[] = ['path' => $path, 'before' => $b, 'after' => $a];
            }
        }

        return $changes;
    }

    /**
     * Check if array is a list (numeric keys)
     */
    protected function isList(array $arr): bool
    {
        if (empty($arr)) {
            return true;
        }

        return array_keys($arr) === range(0, count($arr) - 1);
    }
}
