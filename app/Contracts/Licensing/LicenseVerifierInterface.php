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

namespace App\Contracts\Licensing;

use App\DTO\Licensing\LicenseVerificationResult;

/**
 * License verification contract (reserved for marketplace Phase 2)
 *
 * Provides a license verification abstraction on the Core side, mirroring
 * the pattern used by SignatureVerifierInterface. The contract is reserved
 * in v0.1.0 so that paid plugins and themes can target a stable surface;
 * the actual verification logic is expected to ship as a future official
 * "DixlaseLicensing" plugin once the marketplace begins selling paid
 * extensions.
 *
 * Until that plugin is installed, the Core binds this contract to a no-op
 * stub whose isAvailable() returns false and whose verify() returns a
 * LicenseVerificationResult with status STATUS_UNAVAILABLE — that is the
 * signal that the host install does not enforce licensing and that paid
 * extensions should not refuse to start solely because of a missing
 * verifier.
 *
 * Implementations that wish to enforce licenses (a marketplace-aware
 * verifier plugin) are expected to:
 *
 *  - resolve the configured license key for the given $extensionSlug
 *    against the local license store,
 *  - call out to the configured license authority over HTTPS,
 *  - cache the result for the duration declared by the authority,
 *  - return a populated LicenseVerificationResult.
 *
 * The contract intentionally does not expose raw license keys; key material
 * is held by the licensing plugin and never returned through this surface.
 */
interface LicenseVerifierInterface
{
    /**
     * Verify the license registered for the given plugin or theme.
     *
     * @param  string  $extensionSlug  Plugin or theme slug (kebab-case)
     * @return LicenseVerificationResult Verification result; status STATUS_UNAVAILABLE
     *                                   when no licensing plugin is active
     */
    public function verify(string $extensionSlug): LicenseVerificationResult;

    /**
     * Whether license verification is available on this install.
     *
     * Returns false when no licensing plugin is installed; callers must
     * treat that case as "do not block functionality on licensing".
     */
    public function isAvailable(): bool;
}
