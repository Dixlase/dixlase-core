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
use App\Support\CoreVersion;
use Composer\Semver\Semver;
use Composer\Semver\VersionParser;
use Throwable;

/**
 * Validate an extension's declared Plugin API version against the
 * core's supported range.
 *
 * Dependency-free so it can be instantiated with `new` during the
 * ServiceProvider::register() phase (before the container is fully
 * wired). Manifest-shape agnostic — same logic for plugin.json and
 * theme.json because both share the requires.dixlase_api path.
 *
 * checkRequirements() covers the other two keys of the same block,
 * requires.dixlase (the core release) and requires.php, which install
 * and update enforce (dixlase-core#473).
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
     * Check requires.dixlase against the running core and requires.php
     * against the running PHP.
     *
     * Returns only the requirements that are not met, or that cannot be
     * read: a missing key is not checked. A core or PHP version that is not
     * a valid semver string (a dry-run build, a distribution suffix) is
     * skipped rather than reported, so the extension is not blamed for a
     * version it cannot control.
     *
     * @param  array<string, mixed>  $manifest  Decoded plugin.json or theme.json contents.
     * @param  string|null  $coreVersion  Defaults to CoreVersion::current().
     * @param  string|null  $phpVersion  Defaults to the running PHP as major.minor.release.
     * @return list<array{requirement: 'dixlase'|'php', declared: string, current: string, status: ExtensionCompatibilityStatus}>
     */
    public function checkRequirements(array $manifest, ?string $coreVersion = null, ?string $phpVersion = null): array
    {
        $requires = is_array($manifest['requires'] ?? null) ? $manifest['requires'] : [];
        $current = [
            'dixlase' => $coreVersion ?? CoreVersion::current(),
            'php' => $phpVersion ?? sprintf('%d.%d.%d', PHP_MAJOR_VERSION, PHP_MINOR_VERSION, PHP_RELEASE_VERSION),
        ];

        $problems = [];
        foreach ($current as $requirement => $version) {
            $declared = $requires[$requirement] ?? null;
            if (! is_string($declared) || trim($declared) === '') {
                continue;
            }

            $status = $this->evaluate($version, $declared);
            if ($status === null || $status === ExtensionCompatibilityStatus::Compatible) {
                continue;
            }

            $problems[] = [
                'requirement' => $requirement,
                'declared' => $declared,
                'current' => $version,
                'status' => $status,
            ];
        }

        return $problems;
    }

    /**
     * Null when the running version itself cannot be parsed.
     */
    private function evaluate(string $version, string $constraint): ?ExtensionCompatibilityStatus
    {
        try {
            (new VersionParser())->normalize($version);
        } catch (Throwable) {
            return null;
        }

        try {
            return Semver::satisfies($version, $constraint)
                ? ExtensionCompatibilityStatus::Compatible
                : ExtensionCompatibilityStatus::Incompatible;
        } catch (Throwable) {
            return ExtensionCompatibilityStatus::MalformedConstraint;
        }
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
