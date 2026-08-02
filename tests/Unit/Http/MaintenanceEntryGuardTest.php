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

namespace Tests\Unit\Http;

use Tests\TestCase;

/**
 * Regression pin for the Round 5 residual fix. Sandbox verification
 * of the dryrun-11 → dryrun-12 update observed ~4-9 s of the
 * "maintenance window" during which some PHP-FPM workers still
 * returned 200 for regular requests: `artisan down` had written
 * `storage/framework/maintenance.php`, but those workers held a
 * stale negative stat of that path in their realpath / stat cache
 * and so `file_exists()` reported "no such file". The resulting
 * requests reached composer's file-autoload layer in `public/index.php`
 * BEFORE Laravel booted, and produced fatals like
 * `require(GlobalHelper.php): Failed to open stream` when the source
 * tree was mid-swap.
 *
 * The fix is a single line in `public/index.php`:
 * `clearstatcache(true, $maintenance)` immediately before the
 * `file_exists()` check. This test source-inspects that line rather
 * than trying to reproduce the FPM stat-cache behaviour in PHPUnit
 * (which runs one process, so the cache dynamics don't apply).
 * Behavioural verification is left to the next sandbox dryrun pair.
 */
class MaintenanceEntryGuardTest extends TestCase
{
    public function test_public_index_bypasses_stat_cache_before_maintenance_check(): void
    {
        $source = (string) file_get_contents(base_path('public/index.php'));

        $this->assertStringContainsString(
            'clearstatcache(true, $maintenance)',
            $source,
            'public/index.php must invalidate the stat cache for the '
            .'maintenance sentinel path immediately before the '
            .'file_exists() check. Without this, PHP-FPM workers can '
            .'hold a stale negative stat for several seconds after '
            .'`artisan down` writes the sentinel, and requests slip '
            .'through into a mid-swap source tree — producing the '
            .'composer-autoload-layer fatals the Round 5 sandbox '
            .'verification observed.'
        );
    }

    public function test_clearstatcache_precedes_file_exists_check(): void
    {
        // Order matters: the clear must come BEFORE the file_exists
        // check. If a future refactor moves the clear to after (or
        // drops the pairing entirely), the guard silently regresses.
        $source = (string) file_get_contents(base_path('public/index.php'));

        $clearPos = strpos($source, 'clearstatcache(true, $maintenance)');
        $checkPos = strpos($source, 'file_exists($maintenance)');

        $this->assertNotFalse($clearPos, 'clearstatcache call missing');
        $this->assertNotFalse($checkPos, 'file_exists($maintenance) check missing');
        $this->assertLessThan(
            $checkPos,
            $clearPos,
            'clearstatcache MUST appear before file_exists — otherwise '
            .'the first request through the workers still sees the '
            .'stale negative stat.'
        );
    }
}
