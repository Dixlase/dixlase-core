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
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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
 * Database-related detection patterns
 *
 * Detects database.own_tables, database.core_tables_read, database.core_tables_write
 * core_tables_read: Detects references (reads) to Core tables
 * core_tables_write: Detects write operations to Core tables
 * Excludes cases where there are only use statement imports
 */
class DatabaseDetectionPattern extends DetectionPattern
{
    /**
     * Regex pattern to detect write operations
     */
    protected const WRITE_PATTERNS = [
        '/->save\s*\(/i',
        '/->create\s*\(/i',
        '/->update\s*\(/i',
        '/->delete\s*\(/i',
        '/->forceDelete\s*\(/i',
        '/->insert\s*\(/i',
        '/->upsert\s*\(/i',
        '/DB::table\s*\([\'"][^"\']+[\'"]\)\s*->\s*(insert|update|delete|upsert)\s*\(/i',
    ];

    public function __construct(
        protected string $subKey = 'own_tables',
    ) {}

    public function permissionKey(): string
    {
        return "database.{$this->subKey}";
    }

    public function filePatterns(): array
    {
        if ($this->subKey === 'own_tables') {
            return ['database/migrations/*.php'];
        }

        return [];
    }

    public function regexPatterns(): array
    {
        return match ($this->subKey) {
            'own_tables' => [
                '/Schema::(create|table)\s*\(\s*[\'"](\w+)[\'"]/i',
            ],
            'core_tables_read', 'core_tables_write' => [
                '/\\\\App\\\\Models\\\\(User|Member|Plugin|Media|Setting|SiteSetting|SecuritySetting)/i',
                '/DB::table\s*\(\s*[\'"](users|members|plugins|media|settings|site_settings|security_settings)[\'"]\)/i',
            ],
            default => [],
        };
    }

    /**
     * Exclude if only use statement imports
     * For core_tables_write, also verify that write operations exist in the file
     */
    public function validateMatch(string $match, string $line, string $fileContent, string $filePath): bool
    {
        if (! parent::validateMatch($match, $line, $fileContent, $filePath)) {
            return false;
        }

        // Common to core_tables_read / core_tables_write: exclude use statement imports only
        if (in_array($this->subKey, ['core_tables_read', 'core_tables_write'], true)) {
            $trimmedLine = ltrim($line);

            if (str_starts_with($trimmedLine, 'use ')) {
                return false;
            }
        }

        // core_tables_write: detect only when write operations exist in the file
        if ($this->subKey === 'core_tables_write') {
            return $this->hasWriteOperations($fileContent);
        }

        return true;
    }

    /**
     * Determine if write operations to Core tables exist in the file
     */
    protected function hasWriteOperations(string $fileContent): bool
    {
        foreach (self::WRITE_PATTERNS as $pattern) {
            if (preg_match($pattern, $fileContent)) {
                return true;
            }
        }

        return false;
    }
}
