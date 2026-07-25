<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

namespace App\Services\Licensing;

/**
 * @api Stable API available for plugins/themes
 *
 * Checks whether a plugin/theme `license` value is compatible with the
 * AGPL-3.0 core, taking the Dixlase Plugin and Theme Exception (see
 * LICENSE-EXCEPTIONS) into account.
 *
 * The intent is not to substitute for legal review; it answers a
 * narrower runtime question: "should the install command let this
 * package land, refuse it, or print a soft warning?". The verdict
 * combines the validator's status with the `compatibility` column from
 * config/licensing.php and the install_guard policy.
 */
final class LicenseCompatibilityChecker
{
    public const VERDICT_PASS = 'pass';

    public const VERDICT_WARN = 'warn';

    public const VERDICT_FAIL = 'fail';

    public function __construct(private LicenseValidator $validator) {}

    /**
     * Evaluate a license value against the install-guard policy.
     *
     * @return array{
     *     verdict: string,
     *     status: string,
     *     spdx: string|null,
     *     reason: string|null,
     *     tier: string|null,
     *     compatibility: string|null,
     * }
     */
    public function evaluateForInstall(?string $license): array
    {
        $result = $this->validator->validate($license);

        $policy = config('licensing.install_guard', [
            'on_missing' => 'fail',
            'on_refused' => 'fail',
            'on_unknown' => 'warn',
        ]);

        $verdict = match ($result['status']) {
            LicenseValidator::STATUS_MISSING => $this->policyVerdict($policy['on_missing'] ?? 'fail'),
            LicenseValidator::STATUS_INVALID_SPDX => self::VERDICT_FAIL,
            LicenseValidator::STATUS_REFUSED => $this->policyVerdict($policy['on_refused'] ?? 'fail'),
            LicenseValidator::STATUS_UNKNOWN => $this->policyVerdict($policy['on_unknown'] ?? 'warn'),
            LicenseValidator::STATUS_ACCEPTED => self::VERDICT_PASS,
            default => self::VERDICT_WARN,
        };

        return $result + ['verdict' => $verdict];
    }

    /**
     * Whether a license is recognised as compatible with the AGPL-3.0 core.
     *
     * Returns true for entries flagged `gpl_compatible` or `commercial_escape`
     * in config/licensing.php; false for everything else.
     */
    public function isCompatibleWithCore(?string $license): bool
    {
        $result = $this->validator->validate($license);

        if ($result['status'] !== LicenseValidator::STATUS_ACCEPTED) {
            return false;
        }

        return in_array(
            $result['compatibility'],
            ['gpl_compatible', 'compatible_via_exception', 'commercial_escape'],
            true,
        );
    }

    private function policyVerdict(string $policy): string
    {
        return match ($policy) {
            'fail' => self::VERDICT_FAIL,
            'pass' => self::VERDICT_PASS,
            default => self::VERDICT_WARN,
        };
    }
}
