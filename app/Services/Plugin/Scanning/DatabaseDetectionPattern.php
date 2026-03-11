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
 * database.own_tables, database.core_tables_read, database.core_tables_write を検出します。
 * core_tables_read: コアテーブルへの参照（読み取り）を検出
 * core_tables_write: コアテーブルへの書き込み操作を検出
 * use文のインポートのみの場合は除外します。
 */
class DatabaseDetectionPattern extends DetectionPattern
{
    /**
     * 書き込み操作を検出する正規表現パターン
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
                '/\\\\App\\\\Models\\\\(User|Member|Plugin|Media|Setting|BaseSetting|SecuritySetting)/i',
                '/DB::table\s*\(\s*[\'"](users|members|plugins|media|settings|base_settings|security_settings)[\'"]\)/i',
            ],
            default => [],
        };
    }

    /**
     * use文のインポートのみの場合は除外
     * core_tables_write の場合はファイル内に書き込み操作が存在するかも検証
     */
    public function validateMatch(string $match, string $line, string $fileContent, string $filePath): bool
    {
        if (! parent::validateMatch($match, $line, $fileContent, $filePath)) {
            return false;
        }

        // core_tables_read / core_tables_write 共通: use文のインポートのみは除外
        if (in_array($this->subKey, ['core_tables_read', 'core_tables_write'], true)) {
            $trimmedLine = ltrim($line);

            if (str_starts_with($trimmedLine, 'use ')) {
                return false;
            }
        }

        // core_tables_write: ファイル内に書き込み操作が存在する場合のみ検出
        if ($this->subKey === 'core_tables_write') {
            return $this->hasWriteOperations($fileContent);
        }

        return true;
    }

    /**
     * ファイル内にコアテーブルへの書き込み操作が存在するか判定
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
