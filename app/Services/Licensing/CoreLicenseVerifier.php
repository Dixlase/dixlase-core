<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

namespace App\Services\Licensing;

use App\Contracts\Licensing\LicenseVerifierInterface;
use App\DTO\Licensing\LicenseVerificationResult;

/**
 * @internal Core use only. Do not reference from plugins/themes
 *
 * No-op license verifier shipped with the core.
 *
 * The marketplace surface for paid extensions is reserved in v0.1.0 but the
 * actual verification logic is expected to be supplied by a future official
 * "DixlaseLicensing" plugin (Phase 2). Until that plugin is installed, this
 * verifier reports STATUS_UNAVAILABLE for every request, which callers must
 * treat as "do not enforce licensing on this install".
 *
 * A licensing plugin overrides the binding in
 * App\Providers\AppServiceProvider so that real verification logic replaces
 * this stub at runtime without any change to the Core API surface.
 */
final class CoreLicenseVerifier implements LicenseVerifierInterface
{
    public function verify(string $extensionSlug): LicenseVerificationResult
    {
        return new LicenseVerificationResult(
            status: LicenseVerificationResult::STATUS_UNAVAILABLE,
            message: 'License verification is not available on this install.',
        );
    }

    public function isAvailable(): bool
    {
        return false;
    }
}
