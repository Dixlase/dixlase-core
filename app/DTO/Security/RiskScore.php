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

namespace App\DTO\Security;

use App\Enums\AccessRiskLevel;

/**
 * Outcome of a {@see \App\Contracts\Security\RiskEvaluatorInterface} call.
 *
 * Carries a categorical {@see AccessRiskLevel} and an optional list of
 * `signals` describing the contributing factors (e.g.
 * `["new_country", "asn_change", "off_hours"]`). Phase 1 default
 * evaluator returns `RiskScore::low()` with an empty signal list.
 *
 * The static factories cover the common cases. Phase 3 risk engines can
 * still construct via the standard constructor when finer control over
 * the signal payload is needed.
 */
final readonly class RiskScore
{
    /**
     * @param  AccessRiskLevel  $level  Categorical risk level
     * @param  array<int,string>  $signals  Symbolic names of contributing factors
     * @param  array<string,mixed>  $details  Optional structured details for the consumer (kept opaque to listeners that don't recognise it)
     */
    public function __construct(
        public AccessRiskLevel $level,
        public array $signals = [],
        public array $details = [],
    ) {}

    public static function low(array $signals = [], array $details = []): self
    {
        return new self(AccessRiskLevel::Low, $signals, $details);
    }

    public static function medium(array $signals = [], array $details = []): self
    {
        return new self(AccessRiskLevel::Medium, $signals, $details);
    }

    public static function high(array $signals = [], array $details = []): self
    {
        return new self(AccessRiskLevel::High, $signals, $details);
    }

    public static function critical(array $signals = [], array $details = []): self
    {
        return new self(AccessRiskLevel::Critical, $signals, $details);
    }

    /**
     * Convenience: true when the level is at least the given threshold.
     */
    public function atLeast(AccessRiskLevel $threshold): bool
    {
        return $this->level->atLeast($threshold);
    }
}
