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
 * Detection patterns for dangerous API calls
 *
 * Detects direct use of exec, shell_exec, eval, system, passthru, and env()
 * Excludes matches within comment lines and string literals
 */
class DangerousApiPattern extends DetectionPattern
{
    public function __construct(
        protected string $subKey = 'exec',
    ) {}

    public function permissionKey(): string
    {
        return "dangerous_api.{$this->subKey}";
    }

    public function regexPatterns(): array
    {
        return match ($this->subKey) {
            'exec' => [
                '/\b(exec|shell_exec|system|passthru|popen|proc_open)\s*\(/i',
                '/\beval\s*\(/i',
            ],
            'env_access' => [
                '/\benv\s*\(\s*[\'"][A-Z_]+[\'"]/i',
            ],
            default => [],
        };
    }

    /**
     * Exclude matches within comment lines and string literals
     */
    public function validateMatch(string $match, string $line, string $fileContent, string $filePath): bool
    {
        if (! parent::validateMatch($match, $line, $fileContent, $filePath)) {
            return false;
        }

        $trimmedLine = ltrim($line);

        // Exclude matches within PHPDoc
        if (str_starts_with($trimmedLine, '* ') || str_starts_with($trimmedLine, '/**')) {
            return false;
        }

        // For env(), usage within config files is allowed (Laravel convention)
        if ($this->subKey === 'env_access') {
            if (str_contains($filePath, 'config/') || str_contains($filePath, 'config\\')) {
                return false;
            }
        }

        return true;
    }
}
