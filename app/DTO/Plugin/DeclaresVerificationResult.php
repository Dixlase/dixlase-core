<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

namespace App\DTO\Plugin;

use JsonSerializable;

/**
 * declares セクション検証結果DTO
 *
 * plugin.json の declares セクションと実際のファイル構成を
 * 照合した結果を表現します。
 */
final readonly class DeclaresVerificationResult implements JsonSerializable
{
    /**
     * @param  array<array{key: string, type: string, description: string}>  $issues  検出された問題
     * @param  int  $declaredCount  宣言されたアイテム数
     * @param  int  $actualCount  実際に存在するアイテム数
     */
    public function __construct(
        public array $issues = [],
        public int $declaredCount = 0,
        public int $actualCount = 0,
    ) {}

    /**
     * 問題がないかどうか
     */
    public function isClean(): bool
    {
        return empty($this->issues);
    }

    /**
     * 問題数を取得
     */
    public function issueCount(): int
    {
        return count($this->issues);
    }

    /**
     * 健全性減点の合計を取得
     *
     * - 宣言あり + ファイルなし → -5
     * - ファイルあり + 宣言なし → -2
     */
    public function totalDeduction(): int
    {
        $deduction = 0;
        foreach ($this->issues as $issue) {
            $deduction += match ($issue['type']) {
                'declared_but_missing' => -5,
                'exists_but_undeclared' => -2,
                default => 0,
            };
        }

        return $deduction;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'is_clean' => $this->isClean(),
            'issues' => $this->issues,
            'issue_count' => $this->issueCount(),
            'declared_count' => $this->declaredCount,
            'actual_count' => $this->actualCount,
            'total_deduction' => $this->totalDeduction(),
        ];
    }
}
