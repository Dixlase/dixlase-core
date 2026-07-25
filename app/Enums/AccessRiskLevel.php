<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

declare(strict_types=1);

namespace App\Enums;

/**
 * Risk level of an access attempt (login, request, sensitive action).
 *
 * Used by {@see \App\Contracts\Security\RiskEvaluatorInterface} to express
 * the risk profile of an inbound request relative to learned baselines
 * (location, device, time-of-day, ASN, …). Distinct from
 * {@see OperationRiskLevel} which describes the *operation being performed*,
 * not the *context the request is coming from*.
 *
 * The four-level scale is intentionally coarse so that policy rules
 * ("medium and above requires step-up", "critical denies outright") stay
 * legible. Implementers attach their detailed signals to {@see \App\DTO\Security\RiskScore}.
 */
enum AccessRiskLevel: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    /**
     * Numeric weight for ordering / threshold comparison.
     */
    public function weight(): int
    {
        return match ($this) {
            self::Low => 0,
            self::Medium => 1,
            self::High => 2,
            self::Critical => 3,
        };
    }

    /**
     * True when this level is at least as severe as the given threshold.
     */
    public function atLeast(self $threshold): bool
    {
        return $this->weight() >= $threshold->weight();
    }
}
