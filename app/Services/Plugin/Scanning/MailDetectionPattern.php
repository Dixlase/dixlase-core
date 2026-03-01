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
 * メール関連の検出パターン
 *
 * mail.send と mail.bulk_send を検出します。
 * use文のインポートのみ、Mailable クラス定義のみの場合は除外します。
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
                // each内のクロージャでMail使用（同一行〜数行以内）
                '/->each\s*\(\s*function[^}]{0,200}Mail::/i',
            ],
            default => [],
        };
    }

    /**
     * use文のインポートのみは除外、クラス定義内のMailableは有効
     */
    public function validateMatch(string $match, string $line, string $fileContent, string $filePath): bool
    {
        if (! parent::validateMatch($match, $line, $fileContent, $filePath)) {
            return false;
        }

        $trimmedLine = ltrim($line);

        // use文のインポートのみは除外
        if (str_starts_with($trimmedLine, 'use ') && ! str_contains($trimmedLine, '(')) {
            return false;
        }

        return true;
    }
}
