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

use Tests\TestCase;

/**
 * Regression pins for two failures found updating a one-liner install
 * from v0.3.51 to v0.3.53 (a release that dropped livewire/livewire).
 *
 * 1. After vendor/ was swapped, view:cache ran in the updater's own
 *    process, which had booted Livewire's Blade extension from the old
 *    vendor/, and failed on a file the new vendor/ no longer had. The
 *    first update therefore always failed.
 * 2. The failed update's catch block restored the source and vendor/
 *    but left the migrations it had applied. The next update recorded
 *    that schema as its starting batch, so dls:core:rollback reversed
 *    nothing — old code on a new schema.
 *
 * Source inspection, like CoreMaintenanceBracketRegressionTest: driving
 * update() to these points needs a release ZIP, a vendor swap and a
 * migrate run.
 */
class CoreUpdaterPostSwapRegressionTest extends TestCase
{
    public function test_steps_after_a_vendor_swap_run_in_a_new_process(): void
    {
        $source = $this->updater();

        foreach (["'migrate'", "'config:clear'", "'cache:clear'"] as $command) {
            $this->assertMatchesRegularExpression(
                '/runArtisan\('.preg_quote($command, '/').',[^;]*\$vendorSwapped\)/s',
                $source,
                "{$command} must go through runArtisan(..., \$vendorSwapped)."
            );
        }
        $this->assertMatchesRegularExpression(
            '/if \(\$vendorSwapped\) \{\s*\$this->runArtisan\(\x27view:clear\x27, \[\], true\);\s*\$this->runArtisan\(\x27view:cache\x27, \[\], true\);/',
            $source,
            'After a vendor swap, view:clear / view:cache must run in a new process.'
        );
        $this->assertStringContainsString('app(ArtisanProcess::class)->run(', $source);
    }

    public function test_core_rollback_clears_caches_in_a_new_process_after_a_vendor_swap(): void
    {
        $source = (string) file_get_contents(app_path('Console/Commands/CoreRollback.php'));

        $this->assertMatchesRegularExpression(
            '/if \(\$vendorSwapped\) \{.*?app\(ArtisanProcess::class\).*?\x27view:cache\x27/s',
            $source
        );
    }

    public function test_a_failed_update_reverses_its_migrations_before_restoring_the_source(): void
    {
        $source = $this->updater();

        $catch = strpos($source, '} catch (\\Throwable $e) {', strpos($source, '\'backup_record_id\' => $backupRecordId,'));
        $this->assertNotFalse($catch, 'update() catch block not found');

        $rollback = strpos($source, '$this->rollBackMigrationsSince($preMigrateBatch', $catch);
        $restore = strpos($source, '$this->snapshotter->restore($snapshotPath)', $catch);

        $this->assertNotFalse($rollback, 'The catch block must reverse the migrations this run applied.');
        $this->assertNotFalse($restore);
        $this->assertLessThan(
            $restore,
            $rollback,
            'Migrations must be reversed before the source is restored: their down() methods live in the new files.'
        );
        $this->assertStringContainsString('$migrationsStarted = true;', $source);
    }

    private function updater(): string
    {
        return (string) file_get_contents(app_path('Services/Core/CoreUpdater.php'));
    }
}
