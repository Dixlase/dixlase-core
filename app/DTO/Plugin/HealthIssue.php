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

namespace App\DTO\Plugin;

use JsonSerializable;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * 健全性チェックで検出された問題を表すDTO
 */
final readonly class HealthIssue implements JsonSerializable
{
    /**
     * @param  string  $type  問題の種別（getDeductionRules()のキーに対応）
     * @param  string  $severity  重要度（critical, warning, info）
     * @param  string  $description  問題の説明
     * @param  array<array{file?: string, line?: int, match?: string}>  $evidence  検出根拠
     * @param  int  $deduction  減点値（負の整数）
     */
    public function __construct(
        public string $type,
        public string $severity,
        public string $description,
        public array $evidence = [],
        public int $deduction = 0,
    ) {}

    /**
     * 致命的な問題かどうか
     */
    public function isCritical(): bool
    {
        return $this->severity === 'critical';
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'type' => $this->type,
            'severity' => $this->severity,
            'description' => $this->description,
            'evidence' => $this->evidence,
            'deduction' => $this->deduction,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->jsonSerialize();
    }
}
