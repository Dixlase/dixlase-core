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
 * Each test points the command at a fixture base path. The command
 * walks three independent scopes:
 *
 *   - **core**   — files in `database/migrations/`, ledger
 *                  `migrations`.
 *   - **plugin** — files in `plugins/<dir>/database/migrations/`
 *                  for every plugin directory, ledger
 *                  `plugin_migrations` filtered by the slug
 *                  declared in `plugins/<dir>/plugin.json` (or the
 *                  directory basename as a fallback).
 *   - **theme**  — same shape as plugin, but for
 *                  `themes/<dir>/database/migrations/` and the
 *                  `theme_migrations` ledger.
 *
 * Fixtures only need to write the migration files (empty `<?php`
 * shells — the command only reads filenames) plus a minimal
 * `plugin.json` / `theme.json` carrying the slug. All three ledgers
 * are wiped in setUp() so test assertions are not polluted by the
 * rows RefreshDatabase inserts from running the real suite-under-test
 * migrations; DELETE is used (not TRUNCATE) so everything stays
 * within the per-test transaction.
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
        DB::table('plugin_migrations')->delete();
        DB::table('theme_migrations')->delete();
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->fixtureBasePath)) {
            File::deleteDirectory($this->fixtureBasePath);
        }

        parent::tearDown();
    }

    // ------------------------------------------------------------------
    // Fixture helpers
    // ------------------------------------------------------------------

    protected function writeMigration(string $relativePath): void
    {
        $absolute = $this->fixtureBasePath.'/'.ltrim($relativePath, '/');
        File::ensureDirectoryExists(dirname($absolute));
        File::put($absolute, "<?php\n");
    }

    protected function writePluginManifest(string $pluginRelativeDir, string $slug): void
    {
        $absolute = $this->fixtureBasePath.'/'.ltrim($pluginRelativeDir, '/').'/plugin.json';
        File::ensureDirectoryExists(dirname($absolute));
        File::put($absolute, json_encode(['slug' => $slug]));
    }

    protected function writeThemeManifest(string $themeRelativeDir, string $slug): void
    {
        $absolute = $this->fixtureBasePath.'/'.ltrim($themeRelativeDir, '/').'/theme.json';
        File::ensureDirectoryExists(dirname($absolute));
        File::put($absolute, json_encode(['slug' => $slug]));
    }

    protected function seedCoreLedger(string $migrationName, int $batch = 1): int
    {
        return DB::table('migrations')->insertGetId([
            'migration' => $migrationName,
            'batch' => $batch,
        ]);
    }

    protected function seedPluginLedger(string $pluginSlug, string $migrationName, int $batch = 1): int
    {
        return DB::table('plugin_migrations')->insertGetId([
            'plugin' => $pluginSlug,
            'migration' => $migrationName,
            'batch' => $batch,
        ]);
    }

    protected function seedThemeLedger(string $themeSlug, string $migrationName, int $batch = 1): int
    {
        return DB::table('theme_migrations')->insertGetId([
            'theme' => $themeSlug,
            'migration' => $migrationName,
            'batch' => $batch,
        ]);
    }

    protected function coreContains(string $name): bool
    {
        return DB::table('migrations')->where('migration', $name)->exists();
    }

    protected function pluginContains(string $slug, string $name): bool
    {
        return DB::table('plugin_migrations')
            ->where('plugin', $slug)
            ->where('migration', $name)
            ->exists();
    }

    protected function themeContains(string $slug, string $name): bool
    {
        return DB::table('theme_migrations')
            ->where('theme', $slug)
            ->where('migration', $name)
            ->exists();
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

    /**
     * @return array<string, mixed>
     */
    protected function decodeJson(): array
    {
        return json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array<string, mixed>  $json
     * @return array<string, mixed>|null
     */
    protected function findScope(array $json, string $name): ?array
    {
        foreach ($json['scopes'] as $scope) {
            if ($scope['scope'] === $name) {
                return $scope;
            }
        }

        return null;
    }

    // ------------------------------------------------------------------
    // Core scope
    // ------------------------------------------------------------------

    public function test_realigns_core_migration_rename(): void
    {
        $this->seedCoreLedger('0001_01_01_000050_create_signature_waivers_table');
        $this->writeMigration('database/migrations/0001_01_01_000039_create_signature_waivers_table.php');

        $this->runResync(confirm: true);

        $this->assertFalse($this->coreContains('0001_01_01_000050_create_signature_waivers_table'));
        $this->assertTrue($this->coreContains('0001_01_01_000039_create_signature_waivers_table'));
    }

    public function test_dry_run_does_not_mutate_the_ledger(): void
    {
        $this->seedCoreLedger('0001_01_01_000050_create_signature_waivers_table');
        $this->writeMigration('database/migrations/0001_01_01_000039_create_signature_waivers_table.php');

        $this->runResync(confirm: false);

        $this->assertTrue($this->coreContains('0001_01_01_000050_create_signature_waivers_table'));
        $this->assertFalse($this->coreContains('0001_01_01_000039_create_signature_waivers_table'));
    }

    public function test_orphan_ledger_row_is_skipped_not_dropped(): void
    {
        $this->seedCoreLedger('0001_01_01_000099_create_uninstalled_plugin_table');

        $this->runResync(confirm: true);

        $this->assertTrue($this->coreContains('0001_01_01_000099_create_uninstalled_plugin_table'));
    }

    public function test_already_aligned_ledger_is_a_no_op(): void
    {
        $this->seedCoreLedger('0001_01_01_000039_create_signature_waivers_table');
        $this->writeMigration('database/migrations/0001_01_01_000039_create_signature_waivers_table.php');

        $this->runResync(confirm: true);

        $this->assertTrue($this->coreContains('0001_01_01_000039_create_signature_waivers_table'));
    }

    // ------------------------------------------------------------------
    // Plugin scope
    // ------------------------------------------------------------------

    public function test_realigns_plugin_migration_rename(): void
    {
        $this->writePluginManifest('plugins/DixlaseExample', 'dixlase-example');
        $this->seedPluginLedger('dixlase-example', '2026_05_19_000000_rename_thm_dixlase_one_page_settings');
        $this->writeMigration(
            'plugins/DixlaseExample/database/migrations/0001_01_01_000002_rename_thm_dixlase_one_page_settings.php',
        );

        $this->runResync(confirm: true);

        $this->assertFalse($this->pluginContains('dixlase-example', '2026_05_19_000000_rename_thm_dixlase_one_page_settings'));
        $this->assertTrue($this->pluginContains('dixlase-example', '0001_01_01_000002_rename_thm_dixlase_one_page_settings'));
    }

    public function test_plugin_scope_filters_by_slug_so_another_plugins_row_is_not_touched(): void
    {
        // Plugin A wants to realign one of its rows; Plugin B has a
        // ledger row with the same suffix. The resync must only
        // touch the row that belongs to the directory it is walking.
        $this->writePluginManifest('plugins/PluginA', 'plugin-a');
        $this->writePluginManifest('plugins/PluginB', 'plugin-b');

        $this->seedPluginLedger('plugin-a', '2026_01_01_000000_create_widget_table');
        $this->seedPluginLedger('plugin-b', '0001_01_01_000001_create_widget_table');

        // Only PluginA has the renamed file.
        $this->writeMigration('plugins/PluginA/database/migrations/0001_01_01_000005_create_widget_table.php');
        // PluginB has its file at the name already recorded (no drift).
        $this->writeMigration('plugins/PluginB/database/migrations/0001_01_01_000001_create_widget_table.php');

        $this->runResync(confirm: true);

        // PluginA's row was renamed.
        $this->assertFalse($this->pluginContains('plugin-a', '2026_01_01_000000_create_widget_table'));
        $this->assertTrue($this->pluginContains('plugin-a', '0001_01_01_000005_create_widget_table'));

        // PluginB's row is untouched — its filename was already in sync.
        $this->assertTrue($this->pluginContains('plugin-b', '0001_01_01_000001_create_widget_table'));
        // And critically: PluginA's resync did NOT pull PluginB's row over.
        $this->assertFalse($this->pluginContains('plugin-b', '0001_01_01_000005_create_widget_table'));
    }

    public function test_plugin_slug_falls_back_to_directory_basename_when_manifest_missing(): void
    {
        // No plugin.json — the resync uses the directory basename.
        $this->seedPluginLedger('PluginNoManifest', '2026_06_06_000000_create_foo_table');
        $this->writeMigration('plugins/PluginNoManifest/database/migrations/0001_01_01_000001_create_foo_table.php');

        $this->runResync(confirm: true);

        $this->assertFalse($this->pluginContains('PluginNoManifest', '2026_06_06_000000_create_foo_table'));
        $this->assertTrue($this->pluginContains('PluginNoManifest', '0001_01_01_000001_create_foo_table'));
    }

    // ------------------------------------------------------------------
    // Theme scope
    // ------------------------------------------------------------------

    public function test_realigns_theme_migration_rename(): void
    {
        $this->writeThemeManifest('themes/DixlaseOnePage', 'dixlase-one-page');
        $this->seedThemeLedger('dixlase-one-page', '2026_05_30_000000_drop_thm_dixlase_onepage_settings_aggregate_table');
        $this->writeMigration(
            'themes/DixlaseOnePage/database/migrations/0001_01_01_000003_drop_thm_dixlase_onepage_settings_aggregate_table.php',
        );

        $this->runResync(confirm: true);

        $this->assertFalse($this->themeContains('dixlase-one-page', '2026_05_30_000000_drop_thm_dixlase_onepage_settings_aggregate_table'));
        $this->assertTrue($this->themeContains('dixlase-one-page', '0001_01_01_000003_drop_thm_dixlase_onepage_settings_aggregate_table'));
    }

    // ------------------------------------------------------------------
    // Cross-scope independence (the regression PR #66 introduced)
    // ------------------------------------------------------------------

    public function test_core_and_plugin_share_a_suffix_without_colliding(): void
    {
        // Core and a plugin both ship a migration with the suffix
        // `create_widgets_table`. The OLD (PR #66) implementation
        // treated this as a cross-scope collision and refused to
        // realign either ledger row. The new implementation walks
        // each ledger independently, so neither row should be
        // skipped on collision grounds.
        $this->writePluginManifest('plugins/WidgetPlugin', 'widget-plugin');

        $this->seedCoreLedger('2026_01_01_000000_create_widgets_table');
        $this->seedPluginLedger('widget-plugin', '2026_02_02_000000_create_widgets_table');

        $this->writeMigration('database/migrations/0001_01_01_000050_create_widgets_table.php');
        $this->writeMigration('plugins/WidgetPlugin/database/migrations/0001_01_01_000001_create_widgets_table.php');

        $this->runResync(confirm: true);

        // Both rows realigned. Same suffix, different scope = different ledger = not a collision.
        $this->assertTrue($this->coreContains('0001_01_01_000050_create_widgets_table'));
        $this->assertFalse($this->coreContains('2026_01_01_000000_create_widgets_table'));

        $this->assertTrue($this->pluginContains('widget-plugin', '0001_01_01_000001_create_widgets_table'));
        $this->assertFalse($this->pluginContains('widget-plugin', '2026_02_02_000000_create_widgets_table'));
    }

    public function test_within_plugin_suffix_collision_drops_both_files_from_realignment(): void
    {
        // Two files inside the SAME plugin's migrations/ directory
        // with the same suffix is a real ambiguity — leave the
        // ledger row untouched and surface the collision.
        $this->writePluginManifest('plugins/PluginC', 'plugin-c');
        $this->seedPluginLedger('plugin-c', '2026_05_30_000000_create_settings_table');

        // Two PluginC files with the same suffix `create_settings_table`.
        $this->writeMigration('plugins/PluginC/database/migrations/0001_01_01_000001_create_settings_table.php');
        $this->writeMigration('plugins/PluginC/database/migrations/0001_01_01_000002_create_settings_table.php');

        $this->runResync(confirm: true);

        $this->assertTrue($this->pluginContains('plugin-c', '2026_05_30_000000_create_settings_table'));
        $this->assertFalse($this->pluginContains('plugin-c', '0001_01_01_000001_create_settings_table'));
        $this->assertFalse($this->pluginContains('plugin-c', '0001_01_01_000002_create_settings_table'));
    }

    // ------------------------------------------------------------------
    // Combined / report-shape coverage
    // ------------------------------------------------------------------

    public function test_realigns_mixed_core_plugin_theme_in_one_pass(): void
    {
        $this->writePluginManifest('plugins/DixlaseExample', 'dixlase-example');
        $this->writeThemeManifest('themes/DixlaseOnePage', 'dixlase-one-page');

        $this->seedCoreLedger('0001_01_01_000050_create_signature_waivers_table');
        $this->seedPluginLedger('dixlase-example', '2026_05_19_000000_rename_thm_dixlase_one_page_settings');
        $this->seedThemeLedger('dixlase-one-page', '2026_05_30_000000_drop_thm_dixlase_onepage_settings_aggregate_table');

        $this->writeMigration('database/migrations/0001_01_01_000039_create_signature_waivers_table.php');
        $this->writeMigration('plugins/DixlaseExample/database/migrations/0001_01_01_000002_rename_thm_dixlase_one_page_settings.php');
        $this->writeMigration('themes/DixlaseOnePage/database/migrations/0001_01_01_000003_drop_thm_dixlase_onepage_settings_aggregate_table.php');

        $this->runResync(confirm: true);

        $this->assertTrue($this->coreContains('0001_01_01_000039_create_signature_waivers_table'));
        $this->assertTrue($this->pluginContains('dixlase-example', '0001_01_01_000002_rename_thm_dixlase_one_page_settings'));
        $this->assertTrue($this->themeContains('dixlase-one-page', '0001_01_01_000003_drop_thm_dixlase_onepage_settings_aggregate_table'));
    }

    public function test_json_output_groups_collisions_by_scope(): void
    {
        $this->writePluginManifest('plugins/PluginC', 'plugin-c');
        $this->writeMigration('plugins/PluginC/database/migrations/0001_01_01_000001_create_settings_table.php');
        $this->writeMigration('plugins/PluginC/database/migrations/0001_01_01_000002_create_settings_table.php');

        $this->runResync(confirm: false, json: true);
        $json = $this->decodeJson();

        $scope = $this->findScope($json, 'plugin:plugin-c');
        $this->assertNotNull($scope, 'plugin scope should be present in the report');
        $this->assertArrayHasKey('create_settings_table', $scope['collisions']);
        $this->assertCount(2, $scope['collisions']['create_settings_table']);

        $coreScope = $this->findScope($json, 'core');
        $this->assertNotNull($coreScope);
        $this->assertSame([], $coreScope['collisions']);
    }

    public function test_pending_migrations_are_reported_per_scope_and_not_applied(): void
    {
        $this->writePluginManifest('plugins/PluginD', 'plugin-d');
        $this->writeMigration('database/migrations/0001_01_01_000040_create_brand_new_core_table.php');
        $this->writeMigration('plugins/PluginD/database/migrations/0001_01_01_000001_create_brand_new_plugin_table.php');

        $this->runResync(confirm: true, json: true);
        $json = $this->decodeJson();

        // Neither was inserted by resync — only `migrate` applies pending rows.
        $this->assertFalse($this->coreContains('0001_01_01_000040_create_brand_new_core_table'));
        $this->assertFalse($this->pluginContains('plugin-d', '0001_01_01_000001_create_brand_new_plugin_table'));

        // Each pending entry is reported under its own scope, not in a shared bucket.
        $coreScope = $this->findScope($json, 'core');
        $this->assertContains(
            '0001_01_01_000040_create_brand_new_core_table',
            $coreScope['pending'],
        );

        $pluginScope = $this->findScope($json, 'plugin:plugin-d');
        $this->assertContains(
            '0001_01_01_000001_create_brand_new_plugin_table',
            $pluginScope['pending'],
        );
    }
}
