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

namespace App\Services\Extension;

use App\DTO\Extension\CompatibilityResult;
use App\Enums\ExtensionCompatibilityStatus;
use App\Extension\ExtensionApi;
use Composer\Semver\Semver;
use Throwable;

/**
 * Validate an extension's declared Plugin API version against the
 * core's supported range.
 *
 * Dependency-free so it can be instantiated with `new` during the
 * ServiceProvider::register() phase (before the container is fully
 * wired). Manifest-shape agnostic — same logic for plugin.json and
 * theme.json because both share the requires.dixlase_api path.
 */
final class ExtensionCompatibilityChecker
{
    /**
     * @param  array<string, mixed>  $manifest  Decoded plugin.json or theme.json contents.
     */
    public function check(array $manifest): CompatibilityResult
    {
        $declared = $this->extractDeclaration($manifest);
        $coreVersion = ExtensionApi::CURRENT_VERSION;

        if ($declared === null) {
            return new CompatibilityResult(
                status: ExtensionCompatibilityStatus::MissingDeclaration,
                declared: null,
                coreVersion: $coreVersion,
                message: 'Extension does not declare requires.dixlase_api',
            );
        }

        try {
            $satisfies = Semver::satisfies($coreVersion, $declared);
        } catch (Throwable) {
            return new CompatibilityResult(
                status: ExtensionCompatibilityStatus::MalformedConstraint,
                declared: $declared,
                coreVersion: $coreVersion,
                message: 'requires.dixlase_api is not a valid semver constraint',
            );
        }

        if (! $satisfies) {
            return new CompatibilityResult(
                status: ExtensionCompatibilityStatus::Incompatible,
                declared: $declared,
                coreVersion: $coreVersion,
                message: "Extension declares API {$declared} but core supports {$coreVersion}",
            );
        }

        return new CompatibilityResult(
            status: ExtensionCompatibilityStatus::Compatible,
            declared: $declared,
            coreVersion: $coreVersion,
            message: 'Compatible',
        );
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    private function extractDeclaration(array $manifest): ?string
    {
        $requires = $manifest['requires'] ?? null;

        if (! is_array($requires)) {
            return null;
        }

        $value = $requires[ExtensionApi::MANIFEST_KEY] ?? null;

        if (! is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }
}
