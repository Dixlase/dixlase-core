<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @internal Core use only (consumed by the first-party DixlaseSigner via the
 *           CoreManifestBuilderInterface contract). Do not reference from
 *           third-party plugins/themes.
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

use App\Contracts\Core\CoreManifestBuilderInterface;

/**
 * Builds the Core integrity manifest from the curated first-party file set
 * declared in config/core-integrity.php.
 */
class CoreManifestBuilder implements CoreManifestBuilderInterface
{
    public function fileHashes(?string $basePath = null): array
    {
        $basePath = $this->resolveBasePath($basePath);
        $hashAlgo = (string) config('core-integrity.hash_algorithm', 'sha256');
        $excludePatterns = (array) config('core-integrity.exclude_patterns', []);
        $include = (array) config('core-integrity.include', []);

        $files = [];

        foreach ($include as $entry) {
            $absolute = $basePath.DIRECTORY_SEPARATOR.$entry;

            if (is_dir($absolute)) {
                foreach ($this->walkDirectory($absolute) as $absoluteFile) {
                    $relPath = $this->relativePath($basePath, $absoluteFile);
                    if ($this->shouldExclude($relPath, $excludePatterns)) {
                        continue;
                    }
                    $files[$relPath] = $hashAlgo.':'.hash_file($hashAlgo, $absoluteFile);
                }

                continue;
            }

            if (is_file($absolute)) {
                $relPath = $this->relativePath($basePath, $absolute);
                if ($this->shouldExclude($relPath, $excludePatterns)) {
                    continue;
                }
                $files[$relPath] = $hashAlgo.':'.hash_file($hashAlgo, $absolute);
            }
            // Missing optional files (e.g. public/.htaccess) are simply skipped.
        }

        ksort($files);

        return $files;
    }

    public function build(?string $basePath = null): array
    {
        return [
            'version' => (string) config('app.version', '0.0.0'),
            'files' => $this->fileHashes($basePath),
        ];
    }

    public function canonicalize(array $manifest): string
    {
        unset($manifest['signing']);
        ksort($manifest);
        if (isset($manifest['files']) && is_array($manifest['files'])) {
            ksort($manifest['files']);
        }

        return json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Recursively yield every file path under a directory.
     *
     * @return iterable<string>
     */
    protected function walkDirectory(string $directory): iterable
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY,
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                continue;
            }
            yield $file->getPathname();
        }
    }

    /**
     * Forward-slashed path relative to the core root.
     */
    protected function relativePath(string $basePath, string $absolute): string
    {
        return str_replace('\\', '/', str_replace($basePath.DIRECTORY_SEPARATOR, '', $absolute));
    }

    /**
     * Substring/glob exclusion, matching PluginSigner::shouldExclude semantics.
     *
     * @param  array<int, string>  $patterns
     */
    protected function shouldExclude(string $path, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (strpos($pattern, '*') !== false) {
                $regex = '/^'.str_replace(['*', '/'], ['.*', '\/'], $pattern).'$/';
                if (preg_match($regex, $path)) {
                    return true;
                }
            } elseif (strpos($path, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    protected function resolveBasePath(?string $basePath): string
    {
        return rtrim($basePath ?? base_path(), DIRECTORY_SEPARATOR);
    }
}
