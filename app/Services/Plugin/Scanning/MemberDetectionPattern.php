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
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * メンバー関連の検出パターン
 *
 * members.read, members.write, members.create, members.delete を検出します。
 * use文のインポートのみは除外します。
 */
class MemberDetectionPattern extends DetectionPattern
{
    public function __construct(
        protected string $subKey = 'read',
    ) {}

    public function permissionKey(): string
    {
        return "members.{$this->subKey}";
    }

    public function regexPatterns(): array
    {
        return match ($this->subKey) {
            'read' => [
                '/\\\\App\\\\Models\\\\Member::(get|find|first|all|where|query)/i',
                '/Member::(get|find|first|all|where|query)/i',
            ],
            'write' => [
                '/\\\\App\\\\Models\\\\Member[^;]*->(save|update)\s*\(/i',
                '/Member::(update|create)\s*\(/i',
            ],
            'create' => [
                '/Member::create\s*\(/i',
                '/new\s+Member\s*\(/i',
                '/Member::(firstOrCreate|updateOrCreate)/i',
            ],
            'delete' => [
                '/Member::(delete|destroy)\s*\(/i',
                '/->delete\s*\(\s*\).*Member/i',
            ],
            default => [],
        };
    }

    /**
     * use文のインポートのみは除外
     */
    public function validateMatch(string $match, string $line, string $fileContent, string $filePath): bool
    {
        if (! parent::validateMatch($match, $line, $fileContent, $filePath)) {
            return false;
        }

        $trimmedLine = ltrim($line);

        // use文のインポートのみは除外
        if (str_starts_with($trimmedLine, 'use ')) {
            return false;
        }

        return true;
    }
}
