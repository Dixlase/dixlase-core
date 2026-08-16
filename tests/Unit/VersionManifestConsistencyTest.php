<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Guards that the two places that record the core version never drift apart.
 *
 * The VERSION file is authoritative — the install baseline
 * (InstallConfirmController) and VersionDriftService read it — while
 * dixlase.json carries a descriptive "version". Nothing reads the core-root
 * dixlase.json for the running version, so a mismatch is only cosmetic, but
 * it is confusing and has drifted before. Bump both together with
 * `php scripts/bump-version.php <x.y.z>`.
 *
 * Pure file reads: no framework boot, no database.
 */
class VersionManifestConsistencyTest extends TestCase
{
    private const REPO_ROOT = __DIR__.'/../..';

    public function test_version_file_is_a_single_non_empty_semver_line(): void
    {
        $version = $this->versionFile();

        $this->assertNotSame('', $version, 'VERSION file must not be empty.');
        $this->assertMatchesRegularExpression(
            '/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/',
            $version,
            'VERSION file must contain a single semver-ish version (e.g. 0.3.27 or 0.3.27-dryrun-5).'
        );
    }

    public function test_dixlase_json_version_matches_the_version_file(): void
    {
        $manifest = $this->manifest();

        $this->assertArrayHasKey('version', $manifest, 'dixlase.json must declare a "version".');
        $this->assertSame(
            $this->versionFile(),
            $manifest['version'],
            'dixlase.json "version" must match the VERSION file. '
                .'Run `php scripts/bump-version.php <x.y.z>` to bump both together.'
        );
    }

    private function versionFile(): string
    {
        $raw = file_get_contents(self::REPO_ROOT.'/VERSION');
        $this->assertNotFalse($raw, 'VERSION file is missing or unreadable.');

        return trim($raw);
    }

    /**
     * @return array<string, mixed>
     */
    private function manifest(): array
    {
        $raw = file_get_contents(self::REPO_ROOT.'/dixlase.json');
        $this->assertNotFalse($raw, 'dixlase.json is missing or unreadable.');

        $decoded = json_decode($raw, true);
        $this->assertIsArray($decoded, 'dixlase.json must be valid JSON.');

        return $decoded;
    }
}
