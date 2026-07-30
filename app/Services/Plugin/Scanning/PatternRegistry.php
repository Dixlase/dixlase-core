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

use Illuminate\Support\Facades\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * @internal Core use only. Do not reference from plugins/themes
 *
 * Centralized registry for detection patterns
 *
 * Uses registered DetectionPattern instances to
 * scan plugin/theme code and detect permission usage
 */
class PatternRegistry
{
    /**
     * Registered patterns
     *
     * @var array<string, DetectionPattern>
     */
    protected array $patterns = [];

    /**
     * Create a registry with all default patterns registered
     */
    public static function createDefault(): static
    {
        $registry = new static();

        // Database
        $registry->register(new DatabaseDetectionPattern('own_tables'));
        $registry->register(new DatabaseDetectionPattern('core_tables_read'));
        $registry->register(new DatabaseDetectionPattern('core_tables_write'));

        // Storage
        $registry->register(new StorageDetectionPattern('own_directory'));
        $registry->register(new StorageDetectionPattern('public_uploads'));
        $registry->register(new StorageDetectionPattern('temp_files'));

        // Settings
        $registry->register(new SettingsDetectionPattern('read_core'));
        $registry->register(new SettingsDetectionPattern('write_own'));

        // Member
        $registry->register(new MemberDetectionPattern('read'));
        $registry->register(new MemberDetectionPattern('write'));
        $registry->register(new MemberDetectionPattern('create'));
        $registry->register(new MemberDetectionPattern('delete'));

        // Mail
        $registry->register(new MailDetectionPattern('send'));
        $registry->register(new MailDetectionPattern('bulk_send'));

        // System
        $registry->register(new SystemDetectionPattern('register_shortcodes'));
        $registry->register(new MiddlewareDetectionPattern());
        $registry->register(new SystemDetectionPattern('register_commands'));
        $registry->register(new SystemDetectionPattern('register_blade_directives'));
        $registry->register(new SystemDetectionPattern('modify_routes'));

        // Migration registration (never a legitimate declaration —
        // extension migrations belong to PluginMigrator / ThemeMigrator)
        $registry->register(new MigrationDetectionPattern('stock_migrator'));

        // Dangerous API
        $registry->register(new DangerousApiPattern('exec'));
        $registry->register(new DangerousApiPattern('env_access'));

        // External resources (common to plugins/themes)
        $registry->register(new ExternalResourceDetectionPattern());

        // Theme assets
        $registry->register(new ThemeAssetDetectionPattern('custom_css'));
        $registry->register(new ThemeAssetDetectionPattern('custom_js'));
        $registry->register(new ThemeAssetDetectionPattern('external_resources'));

        return $registry;
    }

    /**
     * Register a pattern
     */
    public function register(DetectionPattern $pattern): void
    {
        $this->patterns[$pattern->permissionKey()] = $pattern;
    }

    /**
     * Get list of patterns applicable to the specified type
     *
     * @param  string  $type  'plugin' or 'theme'
     * @return array<string, DetectionPattern>
     */
    public function getPatternsFor(string $type): array
    {
        return array_filter(
            $this->patterns,
            fn (DetectionPattern $p) => $p->applicableTo() === 'both' || $p->applicableTo() === $type
        );
    }

    /**
     * Get all patterns
     *
     * @return array<string, DetectionPattern>
     */
    public function all(): array
    {
        return $this->patterns;
    }

    /**
     * Scan the specified directory and detect permission usage
     *
     * @param  string  $extensionDir  Extension root directory
     * @param  string  $type  'plugin' or 'theme'
     * @return array{permissions: array<string, bool>, evidence: array<string, array>}
     */
    public function scan(string $extensionDir, string $type = 'plugin'): array
    {
        $patterns = $this->getPatternsFor($type);
        $detected = [];
        $evidence = [];

        foreach ($patterns as $permissionKey => $pattern) {
            $found = false;
            $foundEvidence = [];

            // Check if file pattern exists
            foreach ($pattern->filePatterns() as $filePattern) {
                $files = $this->globRecursive("{$extensionDir}/{$filePattern}");
                foreach ($files as $file) {
                    $found = true;
                    $foundEvidence[] = [
                        'type' => 'file_exists',
                        'file' => str_replace($extensionDir.'/', '', $file),
                    ];
                }
            }

            // Code scan
            if (! empty($pattern->regexPatterns())) {
                $codeFiles = $this->getCodeFiles($extensionDir, $type);
                foreach ($codeFiles as $file) {
                    $content = File::get($file);
                    $relativePath = str_replace($extensionDir.'/', '', $file);
                    $results = $pattern->scan($content, $relativePath);

                    if (! empty($results)) {
                        $found = true;
                        $foundEvidence = array_merge($foundEvidence, $results);
                    }
                }
            }

            $detected[$permissionKey] = $found;
            if (! empty($foundEvidence)) {
                $evidence[$permissionKey] = $foundEvidence;
            }
        }

        return [
            'permissions' => $detected,
            'evidence' => $evidence,
        ];
    }

    /**
     * Get code file list
     *
     * @return array<string>
     */
    protected function getCodeFiles(string $dir, string $type): array
    {
        $files = [];

        if (! File::isDirectory($dir)) {
            return $files;
        }

        // Directories to exclude from scan
        $excludeDirs = ['tests', 'vendor', 'node_modules'];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            // Skip files in excluded directories
            $relativePath = str_replace($dir.'/', '', $file->getPathname());
            $topDir = explode('/', $relativePath)[0] ?? '';
            if (in_array($topDir, $excludeDirs, true)) {
                continue;
            }

            $ext = $file->getExtension();
            $filename = $file->getFilename();

            // PHP files
            if ($ext === 'php') {
                $files[] = $file->getPathname();

                continue;
            }

            // For themes, JS and Blade files are also included
            if ($type === 'theme') {
                if ($ext === 'js' || str_ends_with($filename, '.blade.php')) {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }

    /**
     * Recursively expand glob pattern
     *
     * @return array<string>
     */
    protected function globRecursive(string $pattern): array
    {
        $files = glob($pattern);

        $dir = dirname($pattern);
        $filename = basename($pattern);

        if (File::isDirectory($dir)) {
            foreach (File::directories($dir) as $subdir) {
                $files = array_merge($files, $this->globRecursive("{$subdir}/{$filename}"));
            }
        }

        return $files ?: [];
    }
}
