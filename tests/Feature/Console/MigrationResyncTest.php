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

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Feature coverage for `dls:migration:resync`.
 *
 * Each test points the command at a fixture base path containing fake
 * migration files (just an empty `<?php` shell — the command only reads
 * filenames) and seeds the `migrations` ledger with the "old" filenames
 * a real site would have. The ledger is wiped first so our assertions
 * are not polluted by the rows RefreshDatabase inserts from running the
 * real suite-under-test migrations; DELETE is used (not TRUNCATE) so
 * everything stays within the per-test transaction.
 */
class MigrationResyncTest extends TestCase
{
    use RefreshDatabase;

    protected string $fixtureBasePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtureBasePath = sys_get_temp_dir().'/dls-migration-resync-'.uniqid('', true);
        File::ensureDirectoryExists($this->fixtureBasePath.'/database/migrations');

        DB::table('migrations')->delete();
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->fixtureBasePath)) {
            File::deleteDirectory($this->fixtureBasePath);
        }

        parent::tearDown();
    }

    protected function writeMigration(string $relativePath): void
    {
        $absolute = $this->fixtureBasePath.'/'.ltrim($relativePath, '/');
        File::ensureDirectoryExists(dirname($absolute));
        File::put($absolute, "<?php\n");
    }

    protected function seedLedger(string $migrationName, int $batch = 1): int
    {
        return DB::table('migrations')->insertGetId([
            'migration' => $migrationName,
            'batch' => $batch,
        ]);
    }

    /**
     * @return list<string>
     */
    protected function ledgerNames(): array
    {
        return DB::table('migrations')->orderBy('id')->pluck('migration')->all();
    }

    protected function ledgerContains(string $name): bool
    {
        return DB::table('migrations')->where('migration', $name)->exists();
    }

    protected function runResync(bool $confirm = false, bool $json = false): int
    {
        $params = ['--base-path' => $this->fixtureBasePath];
        if ($confirm) {
            $params['--confirm'] = true;
        }
        if ($json) {
            $params['--json'] = true;
        }

        return Artisan::call('dls:migration:resync', $params);
    }

    public function test_realigns_core_migration_rename(): void
    {
        $this->seedLedger('0001_01_01_000050_create_signature_waivers_table');
        $this->writeMigration('database/migrations/0001_01_01_000039_create_signature_waivers_table.php');

        $this->runResync(confirm: true);

        $this->assertFalse($this->ledgerContains('0001_01_01_000050_create_signature_waivers_table'));
        $this->assertTrue($this->ledgerContains('0001_01_01_000039_create_signature_waivers_table'));
    }

    public function test_realigns_plugin_migration_rename(): void
    {
        $this->seedLedger('2026_05_19_000000_rename_thm_dixlase_one_page_settings');
        $this->writeMigration(
            'plugins/DixlaseExample/database/migrations/0001_01_01_000002_rename_thm_dixlase_one_page_settings.php',
        );

        $this->runResync(confirm: true);

        $this->assertFalse($this->ledgerContains('2026_05_19_000000_rename_thm_dixlase_one_page_settings'));
        $this->assertTrue($this->ledgerContains('0001_01_01_000002_rename_thm_dixlase_one_page_settings'));
    }

    public function test_realigns_theme_migration_rename(): void
    {
        $this->seedLedger('2026_05_30_000000_drop_thm_dixlase_onepage_settings_aggregate_table');
        $this->writeMigration(
            'themes/DixlaseOnePage/database/migrations/0001_01_01_000003_drop_thm_dixlase_onepage_settings_aggregate_table.php',
        );

        $this->runResync(confirm: true);

        $this->assertFalse($this->ledgerContains('2026_05_30_000000_drop_thm_dixlase_onepage_settings_aggregate_table'));
        $this->assertTrue($this->ledgerContains('0001_01_01_000003_drop_thm_dixlase_onepage_settings_aggregate_table'));
    }

    public function test_realigns_mixed_core_plugin_theme_in_one_pass(): void
    {
        $this->seedLedger('0001_01_01_000050_create_signature_waivers_table');
        $this->seedLedger('2026_05_19_000000_rename_thm_dixlase_one_page_settings');
        $this->seedLedger('2026_05_30_000000_drop_thm_dixlase_onepage_settings_aggregate_table');

        $this->writeMigration('database/migrations/0001_01_01_000039_create_signature_waivers_table.php');
        $this->writeMigration('plugins/DixlaseExample/database/migrations/0001_01_01_000002_rename_thm_dixlase_one_page_settings.php');
        $this->writeMigration('themes/DixlaseOnePage/database/migrations/0001_01_01_000003_drop_thm_dixlase_onepage_settings_aggregate_table.php');

        $this->runResync(confirm: true);

        $this->assertEqualsCanonicalizing(
            [
                '0001_01_01_000039_create_signature_waivers_table',
                '0001_01_01_000002_rename_thm_dixlase_one_page_settings',
                '0001_01_01_000003_drop_thm_dixlase_onepage_settings_aggregate_table',
            ],
            $this->ledgerNames(),
        );
    }

    public function test_dry_run_does_not_mutate_the_ledger(): void
    {
        $this->seedLedger('0001_01_01_000050_create_signature_waivers_table');
        $this->writeMigration('database/migrations/0001_01_01_000039_create_signature_waivers_table.php');

        $this->runResync(confirm: false);

        $this->assertTrue($this->ledgerContains('0001_01_01_000050_create_signature_waivers_table'));
        $this->assertFalse($this->ledgerContains('0001_01_01_000039_create_signature_waivers_table'));
    }

    public function test_orphan_ledger_row_is_skipped_not_dropped(): void
    {
        $this->seedLedger('0001_01_01_000099_create_uninstalled_plugin_table');

        $this->runResync(confirm: true);

        $this->assertTrue($this->ledgerContains('0001_01_01_000099_create_uninstalled_plugin_table'));
    }

    public function test_already_aligned_ledger_is_a_no_op(): void
    {
        $this->seedLedger('0001_01_01_000039_create_signature_waivers_table');
        $this->writeMigration('database/migrations/0001_01_01_000039_create_signature_waivers_table.php');

        $this->runResync(confirm: true);

        $this->assertTrue($this->ledgerContains('0001_01_01_000039_create_signature_waivers_table'));
    }

    public function test_suffix_collision_drops_both_files_from_realignment(): void
    {
        $this->seedLedger('2026_05_30_000000_create_settings_table');

        $this->writeMigration('plugins/PluginA/database/migrations/0001_01_01_000001_create_settings_table.php');
        $this->writeMigration('plugins/PluginB/database/migrations/0001_01_01_000002_create_settings_table.php');

        $this->runResync(confirm: true);

        // The ledger row is untouched because the colliding suffix is
        // dropped from the map.
        $this->assertTrue($this->ledgerContains('2026_05_30_000000_create_settings_table'));
        $this->assertFalse($this->ledgerContains('0001_01_01_000001_create_settings_table'));
        $this->assertFalse($this->ledgerContains('0001_01_01_000002_create_settings_table'));
    }

    public function test_json_output_includes_collisions(): void
    {
        $this->writeMigration('plugins/PluginA/database/migrations/0001_01_01_000001_create_settings_table.php');
        $this->writeMigration('plugins/PluginB/database/migrations/0001_01_01_000002_create_settings_table.php');

        $this->runResync(confirm: false, json: true);
        $decoded = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('collisions', $decoded);
        $this->assertArrayHasKey('create_settings_table', $decoded['collisions']);
        $this->assertCount(2, $decoded['collisions']['create_settings_table']);
    }

    public function test_pending_migration_is_listed_but_not_applied_by_resync(): void
    {
        $this->writeMigration('database/migrations/0001_01_01_000040_create_brand_new_table.php');

        $this->runResync(confirm: true, json: true);
        $decoded = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertFalse($this->ledgerContains('0001_01_01_000040_create_brand_new_table'));
        $this->assertContains(
            '0001_01_01_000040_create_brand_new_table',
            $decoded['pending'],
        );
    }
}
