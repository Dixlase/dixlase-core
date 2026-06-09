<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes (reserved hook; default implementation always returns low risk)
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

namespace App\Contracts\Security;

use App\DTO\Security\LoginContext;
use App\DTO\Security\RiskScore;

/**
 * Conditional-access risk scoring hook.
 *
 * Reserved Phase 1 extension point for the Zero Trust roadmap. The default
 * implementation returns {@see RiskScore::low()} for every input — no
 * conditional-access policy is enforced out of the box. Operators who want
 * step-up authentication on suspicious logins, downgrade-on-anomaly, or
 * deny-on-critical-risk install a plugin (or rebind the contract) that
 * implements actual scoring.
 *
 * Compatibility: the method signature is part of the Plugin API stability
 * pledge (see PLUGIN-API.md). Implementations must stay compatible with
 * future fields added to {@see LoginContext} (only ADDITIONS are allowed
 * within a major version).
 *
 * Implementers must:
 *  - Be deterministic for the same input (no hidden state).
 *  - Be safe to call repeatedly during a single request.
 *  - Not throw on missing context fields; missing fields are signals, not
 *    errors. Return a higher risk level when context is suspiciously
 *    sparse if that fits the policy.
 *  - Treat the method as read-only: no DB writes, no audit log entries
 *    (the caller is responsible for those).
 *
 * @see \App\Services\Security\LowRiskEvaluator Default implementation
 */
interface RiskEvaluatorInterface
{
    /**
     * Score the risk of the request described by `$context`.
     */
    public function evaluate(LoginContext $context): RiskScore;
}
