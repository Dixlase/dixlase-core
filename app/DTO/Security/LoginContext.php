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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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

/**
 * Snapshot of the request context that a {@see \App\Contracts\Security\RiskEvaluatorInterface}
 * needs to score risk.
 *
 * The Phase 1 default evaluator does not consume any of these fields (it
 * always returns low risk). The DTO is shipped with a populated shape so
 * that Phase 3 risk engines can read the same fields without breaking
 * Phase 1 listeners — fields will only be ADDED in future minor versions.
 *
 * Field semantics mirror what {@see \App\Services\LoginBehaviorService}
 * already collects, plus a freeform `extra` bag for evaluator-specific
 * signals (CDN-supplied risk hints, third-party IDP claims, …).
 */
final readonly class LoginContext
{
    /**
     * @param  int|null  $member_id  Member ID being authenticated; null for pre-identification phase
     * @param  string|null  $ip_address  Source IP (post-TrustProxies resolution)
     * @param  string|null  $user_agent  HTTP User-Agent string
     * @param  string|null  $device_fingerprint  Hash of UA + Accept headers (see LoginBehaviorService)
     * @param  string|null  $country_code  ISO 3166-1 alpha-2 country code; null when unknown
     * @param  int|null  $login_hour  Hour of day in the application timezone (0–23); null when unknown
     * @param  int|null  $login_day_of_week  Day of week in the application timezone (0=Sunday … 6=Saturday); null when unknown
     * @param  string|null  $session_id  Existing session id, if the request is re-evaluating an active session
     * @param  array<string,mixed>  $extra  Free-form additional signals; never null
     */
    public function __construct(
        public ?int $member_id = null,
        public ?string $ip_address = null,
        public ?string $user_agent = null,
        public ?string $device_fingerprint = null,
        public ?string $country_code = null,
        public ?int $login_hour = null,
        public ?int $login_day_of_week = null,
        public ?string $session_id = null,
        public array $extra = [],
    ) {}
}
