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
 * The manifest's "requires" block is guarded too, and that one is not
 * cosmetic: CorePreflightChecker reads `requires.php` from the release's
 * dixlase.json to decide whether this server may install it. When the
 * manifest lagged behind composer.json (`>=8.2` against `php ^8.3`), the
 * preflight happily passed a PHP 8.2 server that composer would then refuse.
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

    public function test_dixlase_json_php_requirement_matches_composer_json(): void
    {
        $manifest = $this->manifest();

        $this->assertArrayHasKey('requires', $manifest, 'dixlase.json must declare "requires".');
        $this->assertArrayHasKey('php', $manifest['requires'], 'dixlase.json "requires" must declare "php".');

        $this->assertSame(
            $this->floorOf($this->composerRequire('php'), 'composer.json require.php'),
            $this->floorOf($manifest['requires']['php'], 'dixlase.json requires.php'),
            'dixlase.json "requires.php" must declare the same PHP floor as composer.json. '
                .'CorePreflightChecker reads the manifest, so a stale floor lets an incompatible '
                .'server pass the update preflight and break afterwards.'
        );
    }

    public function test_dixlase_json_laravel_requirement_matches_composer_json(): void
    {
        $manifest = $this->manifest();

        $this->assertArrayHasKey('requires', $manifest, 'dixlase.json must declare "requires".');
        $this->assertArrayHasKey('laravel', $manifest['requires'], 'dixlase.json "requires" must declare "laravel".');

        $this->assertSame(
            $this->majorOf($this->composerRequire('laravel/framework'), 'composer.json require.laravel/framework'),
            $this->majorOf($manifest['requires']['laravel'], 'dixlase.json requires.laravel'),
            'dixlase.json "requires.laravel" must name the same Laravel major as composer.json.'
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

    private function composerRequire(string $package): string
    {
        $raw = file_get_contents(self::REPO_ROOT.'/composer.json');
        $this->assertNotFalse($raw, 'composer.json is missing or unreadable.');

        $decoded = json_decode((string) $raw, true);
        $this->assertIsArray($decoded, 'composer.json must be valid JSON.');
        $this->assertArrayHasKey($package, $decoded['require'] ?? [], sprintf('composer.json must require "%s".', $package));

        return (string) $decoded['require'][$package];
    }

    /**
     * The leading "major.minor" of a constraint, e.g. "^8.3" and ">=8.3" both give "8.3".
     */
    private function floorOf(string $constraint, string $label): string
    {
        $this->assertSame(
            1,
            preg_match('/(\d+\.\d+)/', $constraint, $matches),
            sprintf('%s ("%s") must carry a major.minor version.', $label, $constraint)
        );

        return $matches[1];
    }

    /**
     * The leading major of a constraint, e.g. "^13.0" gives "13".
     */
    private function majorOf(string $constraint, string $label): string
    {
        $this->assertSame(
            1,
            preg_match('/(\d+)/', $constraint, $matches),
            sprintf('%s ("%s") must carry a major version.', $label, $constraint)
        );

        return $matches[1];
    }
}
