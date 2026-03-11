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
 * 設定読み取り関連の検出パターン
 *
 * settings.read_core, settings.write_own を検出します。
 */
class SettingsDetectionPattern extends DetectionPattern
{
    public function __construct(
        protected string $subKey = 'read_core',
    ) {}

    public function permissionKey(): string
    {
        return "settings.{$this->subKey}";
    }

    public function regexPatterns(): array
    {
        return match ($this->subKey) {
            'read_core' => [
                '/BaseSetting::(get|getValue|find|first|all)/i',
                '/SecuritySetting::(get|getValue|find|first|all)/i',
                '/config\s*\(\s*[\'"]app\./i',
                '/config\s*\(\s*[\'"]mail\./i',
                '/config\s*\(\s*[\'"]database\./i',
            ],
            'write_own' => [
                '/BaseSetting::(set|setValue|update|create)/i',
                '/SecuritySetting::(set|setValue|update|create)/i',
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
