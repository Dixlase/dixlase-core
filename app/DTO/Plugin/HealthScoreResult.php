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

namespace App\DTO\Plugin;

use App\Enums\PluginHealthStatus;
use JsonSerializable;

/**
 * @internal Core only. Do not reference from plugins/themes
 *
 * Health score calculation result DTO
 */
final readonly class HealthScoreResult implements JsonSerializable
{
    /**
     * @param  int  $score  Health score (0-100)
     * @param  PluginHealthStatus  $status  Health status
     * @param  array<HealthIssue>  $issues  List of detected issues
     * @param  bool  $hasCriticalIssue  Whether there are critical issues
     */
    public function __construct(
        public int $score,
        public PluginHealthStatus $status,
        public array $issues = [],
        public bool $hasCriticalIssue = false,
    ) {}

    /**
     * Whether healthy
     */
    public function isHealthy(): bool
    {
        return $this->status === PluginHealthStatus::Healthy;
    }

    /**
     * Whether unconfirmed (audit not executed or permission undefined)
     */
    public function isNotVerified(): bool
    {
        return $this->status === PluginHealthStatus::NotVerified;
    }

    /**
     * Simple check for whether activation is possible
     */
    public function needsAttention(): bool
    {
        return $this->status === PluginHealthStatus::NeedsAttention;
    }

    /**
     * Get number of issues
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
