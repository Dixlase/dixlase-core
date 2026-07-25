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
 * @internal Core use only. Do not reference from plugins/themes
 *
 * Mail-related detection patterns
 *
 * Detects mail.send and mail.bulk_send
 * Excludes cases with only use statement imports or only Mailable class definitions
 */
class MailDetectionPattern extends DetectionPattern
{
    public function __construct(
        protected string $subKey = 'send',
    ) {}

    public function permissionKey(): string
    {
        return "mail.{$this->subKey}";
    }

    public function regexPatterns(): array
    {
        return match ($this->subKey) {
            'send' => [
                '/Mail::(send|to|queue|later)/i',
                '/Notification::(send|route)/i',
                '/->notify\s*\(/i',
                '/extends\s+Mailable\b/i',
            ],
            'bulk_send' => [
                '/Mail::queue\s*\(/i',
                '/Mail::later\s*\(/i',
                // Mail usage in closure within each (same line to within a few lines)
                '/->each\s*\(\s*function[^}]{0,200}Mail::/i',
            ],
            default => [],
        };
    }

    /**
     * Excludes only use statement imports, Mailable within class definitions is valid
     */
    public function validateMatch(string $match, string $line, string $fileContent, string $filePath): bool
    {
        if (! parent::validateMatch($match, $line, $fileContent, $filePath)) {
            return false;
        }

        $trimmedLine = ltrim($line);

        // Excludes only use statement imports
        if (str_starts_with($trimmedLine, 'use ') && ! str_contains($trimmedLine, '(')) {
            return false;
        }

        return true;
    }
}
