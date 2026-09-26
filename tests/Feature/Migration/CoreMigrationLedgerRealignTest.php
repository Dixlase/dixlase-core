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

namespace Tests\Feature\Migration;

use App\Services\CoreMigrator;
use App\Services\Migration\MigrationLedgerReconciler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Core's baseline migrations are renumbered in place during the beta
 * series, and `migrations` keys on filename — so after a renumber an
 * already-applied file looks new and its CREATE runs again over the
 * existing table (SQLSTATE 42S01).
 *
 * `dls:migration:resync` has always been able to repair that, and
 * upgrading.md tells operators to run it, but nothing ran it for them:
 * `dls:core:update` reaches its migrate only after the backup,
 * `artisan down` and the source swap, so the failure left the site in
 * maintenance mode with new source and an un-migrated database.
 */
class CoreMigrationLedgerRealignTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_renamed_file_is_matched_to_its_ledger_row(): void
    {
        $dir = $this->migrationDir(['0001_01_01_000023_create_ledger_fixture_table.php']);
        // The ledger still carries the number the file had before the re-sort.
        // A fixture name, so the assertions cannot collide with the real
        // migrations RefreshDatabase has already recorded.
        $this->recordRan('0001_01_01_000048_create_ledger_fixture_table');

        $renamed = MigrationLedgerReconciler::reconcileCore(DB::connection(), 'migrations', $dir);

        $this->assertSame(1, $renamed);
        $this->assertTrue($this->isRecorded('0001_01_01_000023_create_ledger_fixture_table'));
        $this->assertFalse($this->isRecorded('0001_01_01_000048_create_ledger_fixture_table'));
    }

    public function test_rows_that_already_match_are_left_alone(): void
    {
        $dir = $this->migrationDir(['0001_01_01_000023_create_ledger_fixture_table.php']);
        $this->recordRan('0001_01_01_000023_create_ledger_fixture_table');

        $this->assertSame(0, MigrationLedgerReconciler::reconcileCore(DB::connection(), 'migrations', $dir));
    }

    public function test_a_row_with_no_matching_file_is_left_alone(): void
    {
        // An orphan is `dls:migration:resync --prune`'s business.
        $dir = $this->migrationDir(['0001_01_01_000023_create_ledger_fixture_table.php']);
        $this->recordRan('0001_01_01_000099_create_something_removed_table');

        $this->assertSame(0, MigrationLedgerReconciler::reconcileCore(DB::connection(), 'migrations', $dir));
        $this->assertTrue($this->isRecorded('0001_01_01_000099_create_something_removed_table'));
    }

    public function test_a_duplicate_is_dropped_rather_than_created(): void
    {
        $dir = $this->migrationDir(['0001_01_01_000023_create_ledger_fixture_table.php']);
        $this->recordRan('0001_01_01_000023_create_ledger_fixture_table');
        $this->recordRan('0001_01_01_000048_create_ledger_fixture_table');

        $renamed = MigrationLedgerReconciler::reconcileCore(DB::connection(), 'migrations', $dir);

        $this->assertSame(1, $renamed);
        $this->assertSame(
            1,
            DB::table('migrations')->where('migration', 'like', '%create_ledger_fixture_table')->count(),
            'The stale row must be removed, not renamed onto the existing one.'
        );
    }

    public function test_an_ambiguous_suffix_is_skipped(): void
    {
        // Two files ending the same way: nothing can say which row is which.
        $dir = $this->migrationDir([
            '0001_01_01_000023_create_ledger_fixture_table.php',
            '0001_01_01_000031_create_ledger_fixture_table.php',
        ]);
        $this->recordRan('0001_01_01_000048_create_ledger_fixture_table');

        $this->assertSame(0, MigrationLedgerReconciler::reconcileCore(DB::connection(), 'migrations', $dir));
    }

    public function test_the_core_migrator_realigns_before_it_runs(): void
    {
        // The real path: every core migrate goes through this migrator —
        // dls:core:update, the installer's `migrate --force`, and a plain
        // `php artisan migrate`.
        $stale = '0001_01_01_999999_add_foreign_key_constraints_stale_copy';
        DB::table('migrations')->insert(['migration' => $stale, 'batch' => 1]);

        $migrator = app('migrator');
        $this->assertInstanceOf(CoreMigrator::class, $migrator);

        $migrator->run([], ['pretend' => true]);

        // Nothing on disk ends with that suffix, so the row stands; the
        // point is that run() reached the realignment without throwing.
        $this->assertTrue($this->isRecorded($stale));
    }

    /**
     * @param  array<int, string>  $fileNames
     */
    private function migrationDir(array $fileNames): string
    {
        $dir = storage_path('framework/testing/migrations-'.uniqid());
        mkdir($dir, 0775, true);

        foreach ($fileNames as $name) {
            file_put_contents($dir.'/'.$name, "<?php\n");
        }

        $this->beforeApplicationDestroyed(function () use ($dir) {
            foreach (glob($dir.'/*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($dir);
        });

        return $dir;
    }

    private function recordRan(string $migration): void
    {
        DB::table('migrations')->insert(['migration' => $migration, 'batch' => 1]);
    }

    private function isRecorded(string $migration): bool
    {
        return DB::table('migrations')->where('migration', $migration)->exists();
    }
}
