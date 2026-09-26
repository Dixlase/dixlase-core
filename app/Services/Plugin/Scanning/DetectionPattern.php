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
 * Abstract base class for detection patterns
 *
 * Each pattern class corresponds to a specific permission category
 * and implements context-aware detection logic
 */
abstract class DetectionPattern
{
    /**
     * Permission key this pattern corresponds to (dot notation)
     */
    abstract public function permissionKey(): string;

    /**
     * Extension type this pattern corresponds to (plugin, theme, both)
     */
    public function applicableTo(): string
    {
        return 'both';
    }

    /**
     * File patterns to check for existence
     *
     * @return array<string>
     */
    public function filePatterns(): array
    {
        return [];
    }

    /**
     * Files this pattern finds by its own logic, relative to the extension root.
     *
     * For checks a fixed glob cannot express -- for example resolving the
     * assets an extension declares in its manifest. Default: none.
     *
     * @return array<string>
     */
    public function detectFiles(string $extensionDir): array
    {
        return [];
    }

    /**
     * Regular expression patterns for detection
     *
     * @return array<string>
     */
    public function regexPatterns(): array
    {
        return [];
    }

    /**
     * Validate match results considering context
     *
     * Context validation is performed after regex matching to reduce false positives
     * By default, all matches are considered valid
     *
     * @param  string  $match  Matched string
     * @param  string  $line  Full line containing the match
     * @param  string  $fileContent  Full file content
     * @param  string  $filePath  File path (relative to extension directory)
     */
    public function validateMatch(string $match, string $line, string $fileContent, string $filePath): bool
    {
        // Default: exclude comment lines
        $trimmedLine = ltrim($line);

        if (str_starts_with($trimmedLine, '//') || str_starts_with($trimmedLine, '*') || str_starts_with($trimmedLine, '/*')) {
            return false;
        }

        return true;
    }

    /**
     * Detect patterns in file and return validated results
     *
     * @return array<array{type: string, file: string, line: int, match: string}>
     */
    public function scan(string $fileContent, string $filePath): array
    {
        $results = [];

        foreach ($this->regexPatterns() as $pattern) {
            if (preg_match_all($pattern, $fileContent, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as [$matchText, $offset]) {
                    $lineNumber = substr_count(substr($fileContent, 0, $offset), "\n") + 1;
                    $line = $this->getLineAtOffset($fileContent, $offset);

                    if ($this->validateMatch($matchText, $line, $fileContent, $filePath)) {
                        $results[] = [
                            'type' => 'pattern_match',
                            'file' => $filePath,
                            'line' => $lineNumber,
                            'match' => trim($matchText),
                        ];
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Get full line at specified offset position
     */
    protected function getLineAtOffset(string $content, int $offset): string
    {
        $lineStart = strrpos($content, "\n", $offset - strlen($content));
        $lineStart = $lineStart === false ? 0 : $lineStart + 1;

        $lineEnd = strpos($content, "\n", $offset);
        $lineEnd = $lineEnd === false ? strlen($content) : $lineEnd;

        return substr($content, $lineStart, $lineEnd - $lineStart);
    }
}
