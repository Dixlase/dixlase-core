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

namespace App\DTO\Plugin;

use App\Enums\PluginHealthStatus;
use JsonSerializable;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * 健全性スコア計算結果DTO
 */
final readonly class HealthScoreResult implements JsonSerializable
{
    /**
     * @param  int  $score  健全性スコア（0-100）
     * @param  PluginHealthStatus  $status  健全性ステータス
     * @param  array<HealthIssue>  $issues  検出された問題のリスト
     * @param  bool  $hasCriticalIssue  致命的な問題があるか
     */
    public function __construct(
        public int $score,
        public PluginHealthStatus $status,
        public array $issues = [],
        public bool $hasCriticalIssue = false,
    ) {}

    /**
     * 健全かどうか
     */
    public function isHealthy(): bool
    {
        return $this->status === PluginHealthStatus::Healthy;
    }

    /**
     * 有効化可能かどうかの簡易チェック
     */
    public function needsAttention(): bool
    {
        return $this->status === PluginHealthStatus::NeedsAttention;
    }

    /**
     * 問題数を取得
     */
    public function issueCount(): int
    {
        return count($this->issues);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'score' => $this->score,
            'status' => $this->status->value,
            'issues' => array_map(fn (HealthIssue $i) => $i->toArray(), $this->issues),
            'has_critical_issue' => $this->hasCriticalIssue,
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
