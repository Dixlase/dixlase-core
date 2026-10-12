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

namespace Tests\Feature\Extensions;

use App\Models\AuditLog;
use App\Models\Plugin;
use App\Models\Theme;
use App\Services\Extension\ExtensionVersionReconciler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * The recorded version is reconciled with the manifest on disk, so the admin
 * panel cannot offer an update to a version that is already installed
 * (dixlase-core#521).
 *
 * The manifests live in a throwaway tree under storage/framework/testing/ and
 * the plugin / theme roots are pointed at it in setUp(), so nothing here reads
 * or writes the real plugins/ or themes/.
 */
class ExtensionVersionReconcileTest extends TestCase
{
    use RefreshDatabase;

    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = 'storage/framework/testing/ext-versions-'.uniqid();

        File::ensureDirectoryExists(base_path($this->root.'/plugins'));
        File::ensureDirectoryExists(base_path($this->root.'/themes'));

        config([
            'plugins.plugins_directory' => $this->root.'/plugins',
            'themes.theme_directory' => $this->root.'/themes',
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(base_path($this->root));

        parent::tearDown();
    }

    public function test_a_plugin_whose_files_moved_is_reconciled_and_recorded(): void
    {
        $plugin = $this->plugin('0.1.2');
        $this->manifest('plugins', 'ReconcileMe', 'plugin.json', '0.1.4');

        $change = app(ExtensionVersionReconciler::class)->reconcile($plugin);

        $this->assertSame(['kind' => 'plugin', 'slug' => 'reconcile-me', 'from' => '0.1.2', 'to' => '0.1.4'], $change);
        $this->assertSame('0.1.4', $plugin->fresh()->version);

        $entry = AuditLog::where('action', AuditLog::ACTION_PLUGIN_VERSION_RECONCILED)->sole();
        $this->assertSame(AuditLog::SEVERITY_WARNING, $entry->severity);
        $this->assertSame('0.1.2', $entry->context['from']);
        $this->assertSame('0.1.4', $entry->context['to']);
    }

    public function test_a_theme_whose_files_moved_is_reconciled(): void
    {
        $theme = $this->theme('0.1.0');
        $this->manifest('themes', 'ReconcileTheme', 'theme.json', '0.3.0');

        $change = app(ExtensionVersionReconciler::class)->reconcile($theme);

        $this->assertSame('0.3.0', $change['to']);
        $this->assertSame('0.3.0', $theme->fresh()->version);
        $this->assertSame(1, AuditLog::where('action', AuditLog::ACTION_THEME_VERSION_RECONCILED)->count());
    }

    public function test_an_agreeing_version_is_left_alone(): void
    {
        $plugin = $this->plugin('0.1.4');
        $this->manifest('plugins', 'ReconcileMe', 'plugin.json', '0.1.4');

        $this->assertNull(app(ExtensionVersionReconciler::class)->reconcile($plugin));
        $this->assertSame(0, AuditLog::where('action', AuditLog::ACTION_PLUGIN_VERSION_RECONCILED)->count());
    }

    public function test_an_unreadable_manifest_leaves_the_recorded_version_alone(): void
    {
        $plugin = $this->plugin('0.1.2');
        // No manifest written at all: the directory is missing or broken, which
        // is not a reason to forget which version the site believes it has.
        $this->assertNull(app(ExtensionVersionReconciler::class)->reconcile($plugin));
        $this->assertSame('0.1.2', $plugin->fresh()->version);

        File::ensureDirectoryExists(base_path($this->root.'/plugins/ReconcileMe'));
        File::put(base_path($this->root.'/plugins/ReconcileMe/plugin.json'), '{ not json');

        $this->assertNull(app(ExtensionVersionReconciler::class)->reconcile($plugin));
        $this->assertSame('0.1.2', $plugin->fresh()->version);
    }

    public function test_a_manifest_with_no_usable_version_is_treated_as_unreadable(): void
    {
        $plugin = $this->plugin('0.1.2');
        $dir = base_path($this->root.'/plugins/ReconcileMe');
        File::ensureDirectoryExists($dir);

        foreach (['{}', '{"version": ""}', '{"version": "   "}', '{"version": 3}'] as $json) {
            File::put($dir.'/plugin.json', $json);

            $this->assertNull(app(ExtensionVersionReconciler::class)->reconcile($plugin), $json);
            $this->assertSame('0.1.2', $plugin->fresh()->version, $json);
        }
    }

    public function test_dry_run_reports_the_drift_without_writing(): void
    {
        $plugin = $this->plugin('0.1.2');
        $this->manifest('plugins', 'ReconcileMe', 'plugin.json', '0.1.4');

        $change = app(ExtensionVersionReconciler::class)->reconcile($plugin, true);

        $this->assertSame('0.1.4', $change['to']);
        $this->assertSame('0.1.2', $plugin->fresh()->version);
        $this->assertSame(0, AuditLog::where('action', AuditLog::ACTION_PLUGIN_VERSION_RECONCILED)->count());
    }

    public function test_the_command_reconciles_every_installed_extension(): void
    {
        $plugin = $this->plugin('0.1.2');
        $theme = $this->theme('0.1.0');
        $this->manifest('plugins', 'ReconcileMe', 'plugin.json', '0.1.4');
        $this->manifest('themes', 'ReconcileTheme', 'theme.json', '0.3.0');

        $this->artisan('dls:extensions:reconcile-versions')->assertExitCode(0);

        $this->assertSame('0.1.4', $plugin->fresh()->version);
        $this->assertSame('0.3.0', $theme->fresh()->version);
    }

    public function test_the_command_in_dry_run_exits_non_zero_when_it_finds_drift(): void
    {
        $plugin = $this->plugin('0.1.2');
        $this->manifest('plugins', 'ReconcileMe', 'plugin.json', '0.1.4');

        $this->artisan('dls:extensions:reconcile-versions', ['--dry-run' => true])->assertExitCode(1);
        $this->assertSame('0.1.2', $plugin->fresh()->version);

        $this->manifest('plugins', 'ReconcileMe', 'plugin.json', '0.1.2');
        $this->artisan('dls:extensions:reconcile-versions', ['--dry-run' => true])->assertExitCode(0);
    }

    public function test_the_command_says_so_when_nothing_is_installed(): void
    {
        // A site that was deployed but never installed has no rows at all.
        // "Every installed extension already matches its manifest" would read
        // in the deploy log as a check that passed.
        $this->artisan('dls:extensions:reconcile-versions')
            ->expectsOutputToContain('No installed extensions.')
            ->assertExitCode(0);
    }

    public function test_the_command_distinguishes_nothing_installed_from_all_matching(): void
    {
        $this->plugin('0.1.4');
        $this->manifest('plugins', 'ReconcileMe', 'plugin.json', '0.1.4');

        $this->artisan('dls:extensions:reconcile-versions')
            ->expectsOutputToContain('Every installed extension already matches its manifest.')
            ->assertExitCode(0);
    }

    public function test_a_stale_row_no_longer_offers_an_installed_version_as_an_update(): void
    {
        // The state found on a production site on 2026-10-11: recorded 0.1.2,
        // the source reporting 0.1.3 as available, 0.1.3 already on disk.
        $plugin = $this->plugin('0.1.2', ['available_version' => '0.1.3']);
        $this->manifest('plugins', 'ReconcileMe', 'plugin.json', '0.1.3');

        $this->assertFalse($plugin->hasUpdateAvailable());
    }

    public function test_an_update_is_still_offered_when_the_source_is_ahead_of_the_files(): void
    {
        $plugin = $this->plugin('0.1.2', ['available_version' => '0.2.0']);
        $this->manifest('plugins', 'ReconcileMe', 'plugin.json', '0.1.3');

        $this->assertTrue($plugin->hasUpdateAvailable());
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function plugin(string $version, array $overrides = []): Plugin
    {
        return Plugin::create(array_merge([
            'name' => 'Reconcile Me',
            'package_name' => 'dixlase/reconcile-me',
            'directory' => 'ReconcileMe',
            'namespace' => 'Plugins\\ReconcileMe',
            'slug' => 'reconcile-me',
            'version' => $version,
            'author' => 'Test Author',
            'installed_at' => now(),
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function theme(string $version, array $overrides = []): Theme
    {
        return Theme::create(array_merge([
            'name' => 'Reconcile Theme',
            'package_name' => 'dixlase/reconcile-theme',
            'directory' => 'ReconcileTheme',
            'namespace' => 'Themes\\ReconcileTheme',
            'slug' => 'reconcile-theme',
            'version' => $version,
            'author' => 'Test Author',
            'installed_at' => now(),
        ], $overrides));
    }

    private function manifest(string $kind, string $directory, string $file, string $version): void
    {
        $dir = base_path("{$this->root}/{$kind}/{$directory}");
        File::ensureDirectoryExists($dir);
        File::put("{$dir}/{$file}", json_encode(['version' => $version], JSON_PRETTY_PRINT));
    }
}
