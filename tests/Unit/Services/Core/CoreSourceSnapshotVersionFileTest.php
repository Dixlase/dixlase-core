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

use App\Services\Core\CoreSourceSnapshot;
use Tests\TestCase;

/**
 * Regression pin for issue #171 Finding A: the VERSION file at the repo
 * root is a first-class part of the source tree — the downgrade guard
 * (Finding #1) reads it, VersionDriftService (Finding #5) compares it
 * against the ledger, and both mechanisms silently no-op when it is
 * absent. Before 2026-07-31 it was omitted from SOURCE_FILES, so
 * applyToLiveTree never copied it during an update: the release ZIP
 * shipped VERSION but the file never appeared on disk, permanently
 * breaking drift/guard on any post-update site. This test pins the
 * membership so a future refactor that touches the constant cannot
 * silently regress that behaviour.
 */
class CoreSourceSnapshotVersionFileTest extends TestCase
{
    public function test_version_file_is_listed_in_source_files(): void
    {
        $this->assertContains(
            'VERSION',
            CoreSourceSnapshot::SOURCE_FILES,
            'The VERSION file at the repo root must be listed in '
            .'CoreSourceSnapshot::SOURCE_FILES so applyToLiveTree() '
            .'copies it during a core update. Without this entry, the '
            .'file ships in the release ZIP but never lands on disk, '
            .'and Finding #5 drift-detection permanently reports '
            .'"unknown" post-update. See issue #171 Finding A.'
        );
    }

    public function test_version_file_is_not_in_protected_paths(): void
    {
        // Belt-and-braces: SOURCE_FILES entries would be overridden by
        // PROTECTED_PATHS if the same name showed up in both lists.
        // Pin the invariant so nobody accidentally adds it to
        // PROTECTED_PATHS while trying to "protect" it (which would
        // put us right back to the broken pre-fix behaviour).
        $this->assertNotContains(
            'VERSION',
            CoreSourceSnapshot::PROTECTED_PATHS,
            'VERSION must NOT appear in PROTECTED_PATHS — a core '
            .'update must be able to overwrite it so the new tag\'s '
            .'version reaches disk.'
        );
    }
}
