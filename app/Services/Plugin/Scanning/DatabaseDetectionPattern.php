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
 * データベース関連の検出パターン
 *
 * database.own_tables と database.core_tables を検出します。
 * use文のインポートのみの場合は除外します。
 */
class DatabaseDetectionPattern extends DetectionPattern
{
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
            'core_tables' => [
                '/\\\\App\\\\Models\\\\(User|Member|Plugin|Media|Setting|BaseSetting|SecuritySetting)/i',
                '/DB::table\s*\(\s*[\'"](users|members|plugins|media|settings|base_settings|security_settings)[\'"]\)/i',
            ],
            default => [],
        };
    }

    /**
     * use文のインポートのみの場合は除外
     */
    public function validateMatch(string $match, string $line, string $fileContent, string $filePath): bool
    {
        if (! parent::validateMatch($match, $line, $fileContent, $filePath)) {
            return false;
        }

        // use文のインポートのみは除外しない（core_tablesの場合、use + 実際の使用が必要）
        if ($this->subKey === 'core_tables') {
            $trimmedLine = ltrim($line);

            // use文のインポートのみは除外
            if (str_starts_with($trimmedLine, 'use ')) {
                return false;
            }
        }

        return true;
    }
}
