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

namespace Tests\Unit\Services\Deploy;

use App\DTO\PluginIntegration\DeployProtectionSource;
use App\Services\Deploy\DeployProtectionRegistry;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\TestCase;

/**
 * Feature coverage for the plugin.json / theme.json driven aggregation
 * of runtime-data protection declarations. Every test points the
 * registry at a fixture tree built under a temp dir — the registry
 * never touches the real installation.
 *
 * The tests exercise both the happy path (a plugin with well-formed
 * declarations flows through to the flat lists) and every documented
 * failure mode (missing manifest, malformed JSON, `deploy` block
 * present but wrong shape). A hard failure on any of those would let
 * a single bad plugin disable protection for the rest of the fleet —
 * exactly the outcome the registry's best-effort design is meant to
 * prevent.
 */
class DeployProtectionRegistryTest extends TestCase
{
    protected string $fixtureBasePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtureBasePath = sys_get_temp_dir().'/dls-deploy-protection-'.uniqid('', true);
        @mkdir($this->fixtureBasePath.'/plugins', 0777, true);
        @mkdir($this->fixtureBasePath.'/themes', 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->fixtureBasePath)) {
            $this->deleteTree($this->fixtureBasePath);
        }

        parent::tearDown();
    }

    // -----------------------------------------------------------------
    // Fixture helpers
    // -----------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $manifest
     */
    protected function writePluginManifest(string $pluginDirName, array $manifest): void
    {
        $dir = $this->fixtureBasePath.'/plugins/'.$pluginDirName;
        @mkdir($dir, 0777, true);
        file_put_contents($dir.'/plugin.json', json_encode($manifest));
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    protected function writeThemeManifest(string $themeDirName, array $manifest): void
    {
        $dir = $this->fixtureBasePath.'/themes/'.$themeDirName;
        @mkdir($dir, 0777, true);
        file_put_contents($dir.'/theme.json', json_encode($manifest));
    }

    protected function writeRawManifest(string $type, string $extensionDirName, string $manifestName, string $rawContent): void
    {
        $dir = $this->fixtureBasePath.'/'.$type.'/'.$extensionDirName;
        @mkdir($dir, 0777, true);
        file_put_contents($dir.'/'.$manifestName, $rawContent);
    }

    protected function makeRegistry(): DeployProtectionRegistry
    {
        return new DeployProtectionRegistry($this->fixtureBasePath);
    }

    private function deleteTree(string $dir): void
    {
        $items = @scandir($dir) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir.'/'.$item;
            is_dir($path) && ! is_link($path) ? $this->deleteTree($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    // -----------------------------------------------------------------
    // Empty / no-op cases
    // -----------------------------------------------------------------

    public function test_registry_returns_empty_when_no_extensions_installed(): void
    {
        $registry = $this->makeRegistry();

        $this->assertSame([], $registry->protectedTables());
        $this->assertSame([], $registry->protectedStoragePaths());
        $this->assertSame([], $registry->sources());
    }

    public function test_registry_ignores_extension_without_deploy_section(): void
    {
        // A plugin whose manifest simply does not opt in — the common
        // case for extensions that carry no runtime data. Must not be
        // treated as an error nor pollute the source list.
        $this->writePluginManifest('DixlaseNoDeploy', [
            'name'    => 'DixlaseNoDeploy',
            'version' => '1.0.0',
        ]);

        $registry = $this->makeRegistry();

        $this->assertSame([], $registry->sources());
    }

    // -----------------------------------------------------------------
    // Happy path
    // -----------------------------------------------------------------

    public function test_registry_collects_tables_and_paths_from_plugin_manifest(): void
    {
        $this->writePluginManifest('DixlaseLegal', [
            'name'   => 'DixlaseLegal',
            'deploy' => [
                'protected_tables' => ['dls_plg_dixlase_legal_cookie_consents'],
            ],
        ]);

        $registry = $this->makeRegistry();

        $this->assertSame(['dls_plg_dixlase_legal_cookie_consents'], $registry->protectedTables());
        $this->assertSame([], $registry->protectedStoragePaths());

        $sources = $registry->sources();
        $this->assertCount(1, $sources);
        $this->assertInstanceOf(DeployProtectionSource::class, $sources[0]);
        $this->assertSame('DixlaseLegal', $sources[0]->extensionName);
        $this->assertSame('plugin', $sources[0]->extensionType);
        $this->assertSame(['dls_plg_dixlase_legal_cookie_consents'], $sources[0]->tables);
        $this->assertSame([], $sources[0]->storagePaths);
    }

    public function test_registry_collects_storage_paths(): void
    {
        $this->writePluginManifest('DixlaseInquiry', [
            'name'   => 'DixlaseInquiry',
            'deploy' => [
                'protected_tables'        => ['dls_plg_dixlase_inquiries'],
                'protected_storage_paths' => ['inquiries/attachments/'],
            ],
        ]);

        $registry = $this->makeRegistry();

        $this->assertSame(['dls_plg_dixlase_inquiries'], $registry->protectedTables());
        $this->assertSame(['inquiries/attachments/'], $registry->protectedStoragePaths());
    }

    public function test_registry_reads_theme_manifests_too(): void
    {
        // Themes should have the same declarative surface — a theme
        // that owns runtime tables must not have to bind a service
        // provider just to declare protection.
        $this->writeThemeManifest('DixlaseOnePage', [
            'name'   => 'DixlaseOnePage',
            'deploy' => [
                'protected_tables' => ['thm_dixlase_onepage_analytics'],
            ],
        ]);

        $registry = $this->makeRegistry();

        $this->assertSame(['thm_dixlase_onepage_analytics'], $registry->protectedTables());

        $sources = $registry->sources();
        $this->assertCount(1, $sources);
        $this->assertSame('theme', $sources[0]->extensionType);
    }

    public function test_registry_dedupes_across_extensions(): void
    {
        // Two plugins declare the same protected table (unusual but
        // possible: e.g. a shared audit-log table). The flat list must
        // report it exactly once so downstream `--ignore-table`
        // arguments are not duplicated.
        $this->writePluginManifest('DixlaseA', [
            'name'   => 'DixlaseA',
            'deploy' => ['protected_tables' => ['dls_shared_audit', 'dls_plg_a']],
        ]);
        $this->writePluginManifest('DixlaseB', [
            'name'   => 'DixlaseB',
            'deploy' => ['protected_tables' => ['dls_shared_audit', 'dls_plg_b']],
        ]);

        $registry = $this->makeRegistry();

        $tables = $registry->protectedTables();
        $this->assertContains('dls_shared_audit', $tables);
        $this->assertContains('dls_plg_a', $tables);
        $this->assertContains('dls_plg_b', $tables);
        $this->assertCount(3, $tables, 'shared table must appear exactly once');
    }

    public function test_registry_caches_the_walk_within_instance(): void
    {
        // A tree walk on every deploy call is wasted work; the
        // singleton binding is only useful if the registry itself
        // memoizes. Verify by mutating the fixture between calls and
        // asserting the second call returns the CACHED snapshot.
        $this->writePluginManifest('DixlaseInitial', [
            'name'   => 'DixlaseInitial',
            'deploy' => ['protected_tables' => ['dls_initial']],
        ]);

        $registry = $this->makeRegistry();
        $first = $registry->protectedTables();

        // Add a new plugin AFTER the first read.
        $this->writePluginManifest('DixlaseLate', [
            'name'   => 'DixlaseLate',
            'deploy' => ['protected_tables' => ['dls_late']],
        ]);

        $second = $registry->protectedTables();

        $this->assertSame($first, $second, 'second read must use the cached source list');
        $this->assertNotContains('dls_late', $second, 'late plugin must not be visible until a fresh registry');
    }

    // -----------------------------------------------------------------
    // Malformed / degenerate manifests — best-effort behaviour
    // -----------------------------------------------------------------

    public function test_registry_skips_extension_with_unreadable_manifest(): void
    {
        $this->writeRawManifest('plugins', 'DixlaseBadJson', 'plugin.json', '{ not valid json');

        $registry = $this->makeRegistry();

        $this->assertSame([], $registry->sources());
    }

    public function test_registry_skips_extension_with_non_array_deploy_section(): void
    {
        $this->writePluginManifest('DixlaseWrongShape', [
            'name'   => 'DixlaseWrongShape',
            'deploy' => 'oops-should-be-an-object',
        ]);

        $registry = $this->makeRegistry();

        $this->assertSame([], $registry->sources());
    }

    public function test_registry_normalises_wrong_type_entries(): void
    {
        // A plugin author accidentally mixes strings with non-string
        // entries. The non-strings are dropped; the strings survive.
        $this->writePluginManifest('DixlaseMixed', [
            'name'   => 'DixlaseMixed',
            'deploy' => [
                'protected_tables' => [
                    'dls_good_table',
                    null,
                    ['nested-array-not-allowed'],
                    42,
                    'dls_good_table',
                    '',
                ],
            ],
        ]);

        $registry = $this->makeRegistry();

        $this->assertSame(
            ['dls_good_table', '42'],
            $registry->sources()[0]->tables,
        );
    }

    public function test_registry_skips_extension_with_only_empty_deploy_lists(): void
    {
        // A plugin that opts in but contributes nothing must not show
        // up as a "source" — otherwise UI callers would render an
        // empty-count line for every plugin whose author just left the
        // skeleton behind.
        $this->writePluginManifest('DixlaseEmpty', [
            'name'   => 'DixlaseEmpty',
            'deploy' => [
                'protected_tables'        => [],
                'protected_storage_paths' => [],
            ],
        ]);

        $registry = $this->makeRegistry();

        $this->assertSame([], $registry->sources());
    }
}
