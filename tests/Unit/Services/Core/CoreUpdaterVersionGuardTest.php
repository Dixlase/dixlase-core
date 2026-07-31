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

namespace Tests\Unit\Services\Core;

use App\Services\Core\CoreUpdater;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Pins the on-disk-version reader that feeds the downgrade guard added
 * in response to the 2026-07-27 sandbox core-update failure (Finding #1
 * in scratchpad/core-team-tasks.md).
 *
 * The reader itself is trivial file IO, but the semantics matter — a
 * subtle regression here (returning `''` instead of `null` for an empty
 * file, for example) would let a destructive downgrade through because
 * `''` collates lower than every real version, so the guard's
 * `version_compare` would not trigger.
 */
class CoreUpdaterVersionGuardTest extends TestCase
{
    private string $baseDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseDir = sys_get_temp_dir().'/core-updater-version-guard-'.uniqid();
        File::ensureDirectoryExists($this->baseDir);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->baseDir)) {
            File::deleteDirectory($this->baseDir);
        }
        parent::tearDown();
    }

    public function test_returns_the_versions_string_when_the_file_exists(): void
    {
        file_put_contents($this->baseDir.'/VERSION', "0.3.1\n");

        $this->assertSame('0.3.1', CoreUpdater::readVersionFromDisk($this->baseDir));
    }

    public function test_trims_surrounding_whitespace_and_trailing_newlines(): void
    {
        file_put_contents($this->baseDir.'/VERSION', "\n  0.3.1-dryrun-6  \r\n\n");

        $this->assertSame('0.3.1-dryrun-6', CoreUpdater::readVersionFromDisk($this->baseDir));
    }

    public function test_returns_null_when_the_file_is_absent(): void
    {
        // No VERSION file placed. Callers must treat null as "unknown; skip
        // any check that depended on it" for backward compatibility with
        // installs that predate the file.
        $this->assertNull(CoreUpdater::readVersionFromDisk($this->baseDir));
    }

    public function test_returns_null_when_the_file_is_present_but_empty(): void
    {
        // An empty file returning '' would collate lower than every real
        // version, so `version_compare('0.2.5', '', '<')` would be false
        // and the guard would silently pass. Explicitly nullable to make
        // "unknown" the only reachable state.
        file_put_contents($this->baseDir.'/VERSION', '');

        $this->assertNull(CoreUpdater::readVersionFromDisk($this->baseDir));
    }

    public function test_returns_null_when_the_file_contains_only_whitespace(): void
    {
        file_put_contents($this->baseDir.'/VERSION', "   \n\t\n");

        $this->assertNull(CoreUpdater::readVersionFromDisk($this->baseDir));
    }

    public function test_the_shipped_versio_n_file_is_readable_and_semver_shaped(): void
    {
        // Sanity check on the actual VERSION shipped at the repo root.
        // We do not pin the exact value (it moves every release), but it
        // must exist and look like a semver-ish string so the on-disk
        // guard has something meaningful to compare against.
        $shipped = CoreUpdater::readVersionFromDisk();

        $this->assertNotNull($shipped, 'The shipped VERSION file at the repo root should exist.');
        $this->assertMatchesRegularExpression(
            '/^\d+\.\d+\.\d+(-[a-zA-Z0-9.-]+)?$/',
            $shipped,
            "The shipped VERSION should be semver-shaped; got: {$shipped}"
        );
    }
}
