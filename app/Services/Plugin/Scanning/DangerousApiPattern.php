<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
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
 * 危険なAPI呼び出しの検出パターン
 *
 * exec, shell_exec, eval, system, passthru, env()直接使用を検出します。
 * コメント行、文字列リテラル内は除外します。
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
     * コメント行、文字列リテラル内のマッチを除外
     */
    public function validateMatch(string $match, string $line, string $fileContent, string $filePath): bool
    {
        if (! parent::validateMatch($match, $line, $fileContent, $filePath)) {
            return false;
        }

        $trimmedLine = ltrim($line);

        // PHPDoc内のマッチは除外
        if (str_starts_with($trimmedLine, '* ') || str_starts_with($trimmedLine, '/**')) {
            return false;
        }

        // env()の場合、config ファイル内での使用は許容（Laravelの慣例）
        if ($this->subKey === 'env_access') {
            if (str_contains($filePath, 'config/') || str_contains($filePath, 'config\\')) {
                return false;
            }
        }

        return true;
    }
}
