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

namespace Tests\Unit\Services\Migration;

use App\Services\Migration\MigrationLedgerReconciler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * The reconciler renames a per-extension ledger row when the release
 * re-sorted that migration's timestamp prefix, so the extension
 * migrator recognises it as already applied instead of re-running its
 * CREATE over the existing table.
 */
class MigrationLedgerReconcilerTest extends TestCase
{
    use RefreshDatabase;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir().'/reconcile-test-'.getmypid();
        File::ensureDirectoryExists($this->dir);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        parent::tearDown();
    }

    private function file(string $name): void
    {
        File::put($this->dir."/{$name}.php", "<?php\n");
    }

    private function ledgerInsert(string $migration, string $theme): void
    {
        DB::table('theme_migrations')->insert([
            'migration' => $migration,
            'theme' => $theme,
            'batch' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_renames_a_resorted_migration_by_stable_suffix(): void
    {
        $this->file('0001_01_01_000001_create_foo_table');
        $this->ledgerInsert('0001_01_01_000100_create_foo_table', 'demo');

        $updated = MigrationLedgerReconciler::reconcile(DB::connection(), 'theme_migrations', 'theme', 'demo', $this->dir);

        $this->assertSame(1, $updated);
        $this->assertDatabaseHas('theme_migrations', [
            'theme' => 'demo',
            'migration' => '0001_01_01_000001_create_foo_table',
        ]);
        $this->assertDatabaseMissing('theme_migrations', [
            'migration' => '0001_01_01_000100_create_foo_table',
        ]);
    }

    public function test_is_a_noop_when_names_already_match(): void
    {
        $this->file('0001_01_01_000001_create_foo_table');
        $this->ledgerInsert('0001_01_01_000001_create_foo_table', 'demo');

        $this->assertSame(0, MigrationLedgerReconciler::reconcile(DB::connection(), 'theme_migrations', 'theme', 'demo', $this->dir));
    }

    public function test_does_not_touch_other_extensions(): void
    {
        $this->file('0001_01_01_000001_create_foo_table');
        $this->ledgerInsert('0001_01_01_000100_create_foo_table', 'other');

        $updated = MigrationLedgerReconciler::reconcile(DB::connection(), 'theme_migrations', 'theme', 'demo', $this->dir);

        $this->assertSame(0, $updated);
        $this->assertDatabaseHas('theme_migrations', [
            'theme' => 'other',
            'migration' => '0001_01_01_000100_create_foo_table',
        ]);
    }

    public function test_drops_the_stale_row_when_the_target_name_already_exists(): void
    {
        $this->file('0001_01_01_000001_create_foo_table');
        $this->ledgerInsert('0001_01_01_000001_create_foo_table', 'demo'); // new name already recorded
        $this->ledgerInsert('0001_01_01_000100_create_foo_table', 'demo'); // stale duplicate

        $updated = MigrationLedgerReconciler::reconcile(DB::connection(), 'theme_migrations', 'theme', 'demo', $this->dir);

        $this->assertSame(1, $updated);
        $this->assertSame(1, DB::table('theme_migrations')->where('theme', 'demo')->count());
        $this->assertDatabaseHas('theme_migrations', [
            'theme' => 'demo',
            'migration' => '0001_01_01_000001_create_foo_table',
        ]);
    }

    public function test_ambiguous_suffix_collision_is_left_untouched(): void
    {
        // Two files share a suffix -> cannot tell which row maps where.
        $this->file('0001_01_01_000001_create_foo_table');
        $this->file('2026_01_01_000001_create_foo_table');
        $this->ledgerInsert('0001_01_01_000100_create_foo_table', 'demo');

        $this->assertSame(0, MigrationLedgerReconciler::reconcile(DB::connection(), 'theme_migrations', 'theme', 'demo', $this->dir));
    }
}
