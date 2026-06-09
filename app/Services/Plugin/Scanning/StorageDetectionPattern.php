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
 * Storage-related detection pattern
 *
 * Detects storage.own_directory, storage.public_uploads, storage.temp_files
 */
class StorageDetectionPattern extends DetectionPattern
{
    public function __construct(
        protected string $subKey = 'own_directory',
    ) {}

    public function permissionKey(): string
    {
        return "storage.{$this->subKey}";
    }

    public function regexPatterns(): array
    {
        return match ($this->subKey) {
            'own_directory' => [
                '/Storage::(put|get|delete|exists|disk)/i',
                '/File::(put|get|delete|exists|copy|move)/i',
            ],
            'public_uploads' => [
                '/Storage::disk\s*\(\s*[\'"]public[\'"]\)/i',
                '/->store\s*\(\s*[\'"]uploads/i',
                '/public_path\s*\(\s*[\'"]uploads/i',
            ],
            'temp_files' => [
                '/tempnam\s*\(/i',
                '/sys_get_temp_dir\s*\(/i',
                '/Storage::disk\s*\(\s*[\'"]temp[\'"]\)/i',
            ],
            default => [],
        };
    }

    /**
     * Exclude use statement imports only
     */
    public function validateMatch(string $match, string $line, string $fileContent, string $filePath): bool
    {
        if (! parent::validateMatch($match, $line, $fileContent, $filePath)) {
            return false;
        }

        $trimmedLine = ltrim($line);

        // Exclude use statement imports only
        if (str_starts_with($trimmedLine, 'use ')) {
            return false;
        }

        return true;
    }
}
