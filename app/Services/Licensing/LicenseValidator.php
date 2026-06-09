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

namespace App\Services\Licensing;

/**
 * @api Stable API available for plugins/themes
 *
 * Validates the `license` field of plugin.json / theme.json against the
 * SPDX whitelist configured in config/licensing.php.
 *
 * The validator is intentionally lenient: it accepts simple SPDX
 * identifiers, SPDX expressions (e.g. "MIT OR Apache-2.0"), and
 * `LicenseRef-*` aliases. Semantic acceptance (is this license listed
 * in our `accepted` table?) is reported separately from syntactic
 * validity (does it look like SPDX?) so callers can distinguish
 * "malformed identifier" from "unknown identifier".
 */
final class LicenseValidator
{
    public const STATUS_MISSING = 'missing';

    public const STATUS_INVALID_SPDX = 'invalid_spdx';

    public const STATUS_REFUSED = 'refused';

    public const STATUS_UNKNOWN = 'unknown';

    public const STATUS_ACCEPTED = 'accepted';

    /**
     * Validate a raw license string from a manifest.
     *
     * @return array{
     *     status: string,
     *     spdx: string|null,
     *     reason: string|null,
     *     tier: string|null,
     *     compatibility: string|null,
     * }
     */
    public function validate(?string $license): array
    {
        if ($license === null || trim($license) === '') {
            return [
                'status' => self::STATUS_MISSING,
                'spdx' => null,
                'reason' => 'The manifest does not declare a `license` field.',
                'tier' => null,
                'compatibility' => null,
            ];
        }

        $normalized = trim($license);

        if (! $this->isSyntacticallyValidSpdx($normalized)) {
            return [
                'status' => self::STATUS_INVALID_SPDX,
                'spdx' => $normalized,
                'reason' => sprintf(
                    'License value %s does not match the SPDX identifier format. Use a value like "GPL-3.0-or-later", "MIT", or "LicenseRef-YourVendor-Commercial".',
                    json_encode($normalized, JSON_UNESCAPED_SLASHES),
                ),
                'tier' => null,
                'compatibility' => null,
            ];
        }

        $refused = config('licensing.refused', []);
        if (isset($refused[$normalized])) {
            return [
                'status' => self::STATUS_REFUSED,
                'spdx' => $normalized,
                'reason' => $refused[$normalized],
                'tier' => null,
                'compatibility' => null,
            ];
        }

        $accepted = config('licensing.accepted', []);
        if (isset($accepted[$normalized])) {
            $entry = $accepted[$normalized];

            return [
                'status' => self::STATUS_ACCEPTED,
                'spdx' => $normalized,
                'reason' => null,
                'tier' => $entry['tier'] ?? null,
                'compatibility' => $entry['compatibility'] ?? null,
            ];
        }

        return [
            'status' => self::STATUS_UNKNOWN,
            'spdx' => $normalized,
            'reason' => sprintf(
                'License %s is not in the accepted-licenses table. It looks like a valid SPDX identifier but the project has not yet vetted it; ask the maintainers before relying on it.',
                json_encode($normalized, JSON_UNESCAPED_SLASHES),
            ),
            'tier' => null,
            'compatibility' => null,
        ];
    }

    public function isSyntacticallyValidSpdx(string $value): bool
    {
        $pattern = config('licensing.spdx_pattern');
        if (! is_string($pattern) || $pattern === '') {
            return false;
        }

        return preg_match($pattern, $value) === 1;
    }
}
