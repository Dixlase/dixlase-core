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

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\DTO\Plugin\HealthScoreResult;
use App\Enums\PluginEnableAction;
use App\Enums\PluginHealthStatus;
use App\Models\Plugin;
use App\Models\Theme;
use App\Repositories\SecuritySettingRepository;
use App\Services\Extension\ExtensionRescanService;
use App\Services\Plugin\PluginHealthScorer;
use App\Services\Tailwind\PluginSourceAggregator;
use App\Services\Theme\ThemeHealthScorer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Mockery;
use Tests\TestCase;

/**
 * The CLI applies the admin panel's health / signature gate (dixlase-core#492).
 *
 * Regression: `dls:plugin:install --enable`, `dls:plugin:enable` and
 * `dls:theme:switch` enabled whatever was on disk regardless of the extension
 * security preset, while the admin panel refused the same extension.
 *
 * The scan and the score are mocked so each test pins the resolved action.
 * The commands run against a throwaway base path under
 * storage/framework/testing/, never the repository's plugins/ or themes/.
 */
class ExtensionActivationGateCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = base_path('storage/framework/testing/activation-gate-'.uniqid('', true));
        File::ensureDirectoryExists($this->root.'/plugins');
        File::ensureDirectoryExists($this->root.'/themes');
        File::ensureDirectoryExists($this->root.'/public');

        // The Tailwind source aggregator writes a tracked CSS file; keep the
        // enable path away from it.
        $aggregator = Mockery::mock(PluginSourceAggregator::class);
        $aggregator->shouldReceive('regenerate')->andReturn([]);
        $this->app->instance(PluginSourceAggregator::class, $aggregator);

        $this->setPreset('strict');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->root);

        parent::tearDown();
    }

    // -----------------------------------------------------------------
    // dls:plugin:enable
    // -----------------------------------------------------------------

    public function test_plugin_enable_refuses_a_blocked_plugin(): void
    {
        $plugin = $this->makePlugin();
        $this->mockPluginScan(PluginEnableAction::Blocked);

        $this->artisan('dls:plugin:enable', ['pluginName' => 'GateDemo'])
            ->assertExitCode(1);

        $this->assertNull($plugin->fresh()->enabled_at);
    }

    public function test_plugin_enable_refuses_a_blocked_plugin_even_with_force(): void
    {
        $plugin = $this->makePlugin();
        $this->mockPluginScan(PluginEnableAction::Blocked);

        $this->artisan('dls:plugin:enable', ['pluginName' => 'GateDemo', '--force' => true])
            ->assertExitCode(1);

        $this->assertNull($plugin->fresh()->enabled_at);
    }

    public function test_plugin_enable_needs_force_for_an_outcome_the_admin_panel_confirms(): void
    {
        $plugin = $this->makePlugin();
        $this->mockPluginScan(PluginEnableAction::AcknowledgementRequired);

        $this->artisan('dls:plugin:enable', ['pluginName' => 'GateDemo'])
            ->assertExitCode(1);
        $this->assertNull($plugin->fresh()->enabled_at);

        $exitCode = Artisan::call('dls:plugin:enable', ['pluginName' => 'GateDemo', '--force' => true]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('WARNING', Artisan::output());
        $this->assertNotNull($plugin->fresh()->enabled_at);
    }

    public function test_plugin_enable_allows_a_healthy_plugin_without_force(): void
    {
        $plugin = $this->makePlugin();
        $this->mockPluginScan(PluginEnableAction::Allowed);

        $this->artisan('dls:plugin:enable', ['pluginName' => 'GateDemo'])
            ->assertExitCode(0);

        $this->assertNotNull($plugin->fresh()->enabled_at);
    }

    public function test_plugin_enable_uses_the_current_scan_instead_of_rescanning(): void
    {
        $this->makePlugin();
        $this->mockPluginScan(PluginEnableAction::Allowed, needsRescan: false);

        $this->artisan('dls:plugin:enable', ['pluginName' => 'GateDemo'])
            ->assertExitCode(0);
    }

    public function test_plugin_enable_fails_closed_when_the_scan_crashes(): void
    {
        $plugin = $this->makePlugin();

        $scorer = Mockery::mock(PluginHealthScorer::class);
        $scorer->shouldReceive('needsRescan')->andReturn(true);
        $this->app->instance(PluginHealthScorer::class, $scorer);
        $rescan = Mockery::mock(ExtensionRescanService::class);
        $rescan->shouldReceive('rescanPlugin')->andThrow(new \RuntimeException('audit crashed'));
        $this->app->instance(ExtensionRescanService::class, $rescan);

        $this->artisan('dls:plugin:enable', ['pluginName' => 'GateDemo', '--force' => true])
            ->assertExitCode(1);

        $this->assertNull($plugin->fresh()->enabled_at);
    }

    public function test_the_development_preset_is_unrestricted(): void
    {
        $this->setPreset('development');
        $plugin = $this->makePlugin();

        $scorer = Mockery::mock(PluginHealthScorer::class);
        $scorer->shouldNotReceive('calculate');
        $this->app->instance(PluginHealthScorer::class, $scorer);
        $rescan = Mockery::mock(ExtensionRescanService::class);
        $rescan->shouldNotReceive('rescanPlugin');
        $this->app->instance(ExtensionRescanService::class, $rescan);

        $this->artisan('dls:plugin:enable', ['pluginName' => 'GateDemo'])
            ->assertExitCode(0);

        $this->assertNotNull($plugin->fresh()->enabled_at);
    }

    // -----------------------------------------------------------------
    // dls:plugin:install --enable
    // -----------------------------------------------------------------

    public function test_plugin_install_refuses_a_blocked_plugin_before_writing_anything(): void
    {
        $this->placePluginOnDisk();
        $this->mockPluginScan(PluginEnableAction::Blocked);

        $this->artisan('dls:plugin:install', ['pluginName' => 'GateDemo', '--enable' => true, '--force' => true])
            ->assertExitCode(1);

        $this->assertDatabaseMissing('plugins', ['name' => 'GateDemo']);
    }

    public function test_plugin_install_without_enable_still_refuses_a_blocked_plugin(): void
    {
        $this->placePluginOnDisk();
        $this->mockPluginScan(PluginEnableAction::Blocked);

        $this->artisan('dls:plugin:install', ['pluginName' => 'GateDemo'])
            ->assertExitCode(1);

        $this->assertDatabaseMissing('plugins', ['name' => 'GateDemo']);
    }

    public function test_plugin_install_enable_needs_force_for_an_outcome_the_admin_panel_confirms(): void
    {
        $this->placePluginOnDisk();
        $this->mockPluginScan(PluginEnableAction::WarningRequired);

        $exitCode = Artisan::call('dls:plugin:install', ['pluginName' => 'GateDemo', '--enable' => true]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('--force', Artisan::output());

        $this->assertDatabaseMissing('plugins', ['name' => 'GateDemo']);
    }

    // -----------------------------------------------------------------
    // dls:theme:switch
    // -----------------------------------------------------------------

    public function test_theme_switch_refuses_a_blocked_theme(): void
    {
        $theme = $this->makeTheme();
        $this->mockThemeScan(PluginEnableAction::Blocked);

        $this->artisan('dls:theme:switch', ['themeName' => 'gate-theme', '--force' => true])
            ->assertExitCode(1);

        $this->assertNotSame((string) $theme->id, (string) $this->enabledThemeId());
    }

    public function test_theme_switch_needs_force_for_an_outcome_the_admin_panel_confirms(): void
    {
        $theme = $this->makeTheme();
        $this->mockThemeScan(PluginEnableAction::WarningRequired);

        $this->artisan('dls:theme:switch', ['themeName' => 'gate-theme'])
            ->assertExitCode(1);
        $this->assertNotSame((string) $theme->id, (string) $this->enabledThemeId());

        $this->artisan('dls:theme:switch', ['themeName' => 'gate-theme', '--force' => true])
            ->assertExitCode(0);
        $this->assertSame((string) $theme->id, (string) $this->enabledThemeId());
    }

    public function test_theme_switch_under_development_skips_the_gate(): void
    {
        $this->setPreset('development');
        $theme = $this->makeTheme();

        $rescan = Mockery::mock(ExtensionRescanService::class);
        $rescan->shouldNotReceive('rescanTheme');
        $this->app->instance(ExtensionRescanService::class, $rescan);

        $this->artisan('dls:theme:switch', ['themeName' => 'gate-theme'])
            ->assertExitCode(0);

        $this->assertSame((string) $theme->id, (string) $this->enabledThemeId());
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    private function setPreset(string $preset): void
    {
        app(SecuritySettingRepository::class)->set('extension_security_preset', $preset);
    }

    /**
     * Point base_path() (plugins/, themes/, public/) at the throwaway tree,
     * keeping the real lang/ so the command messages stay translated.
     */
    private function useThrowawayBasePath(): void
    {
        $langPath = $this->app->langPath();
        $this->app->setBasePath($this->root);
        $this->app->useLangPath($langPath);
    }

    private function makePlugin(): Plugin
    {
        $this->useThrowawayBasePath();

        return Plugin::forceCreate([
            'name' => 'GateDemo',
            'namespace' => 'Plugins\\GateDemo',
            'directory' => 'GateDemo',
            'slug' => 'gate-demo',
            'version' => '1.0.0',
            'installed_at' => now(),
        ]);
    }

    private function placePluginOnDisk(): void
    {
        File::ensureDirectoryExists($this->root.'/plugins/GateDemo');
        File::put($this->root.'/plugins/GateDemo/plugin.json', (string) json_encode([
            'name' => 'GateDemo',
            'slug' => 'gate-demo',
            'version' => '1.0.0',
            'license' => 'MIT',
        ]));

        $this->useThrowawayBasePath();
    }

    private function makeTheme(): Theme
    {
        $this->useThrowawayBasePath();

        // A site always has this row (the installer writes it); the command
        // updates it in place.
        DB::table('theme_settings')->insert([
            'site_id' => 1,
            'key' => 'enabled_theme_id',
            'value' => '0',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Theme::forceCreate([
            'name' => 'GateTheme',
            'directory' => 'GateTheme',
            'slug' => 'gate-theme',
            'version' => '1.0.0',
            'installed_at' => now(),
        ]);
    }

    private function enabledThemeId(): mixed
    {
        return DB::table('theme_settings')->where('key', 'enabled_theme_id')->value('value');
    }

    private function mockPluginScan(PluginEnableAction $action, bool $needsRescan = true): void
    {
        $scorer = Mockery::mock(PluginHealthScorer::class);
        $scorer->shouldReceive('needsRescan')->with('gate-demo')->andReturn($needsRescan);
        $scorer->shouldReceive('calculate')->with('gate-demo')->andReturn(new HealthScoreResult(90, PluginHealthStatus::Healthy));
        $scorer->shouldReceive('determineEnableAction')->andReturn($action);
        $this->app->instance(PluginHealthScorer::class, $scorer);

        $rescan = Mockery::mock(ExtensionRescanService::class);
        $expectation = $rescan->shouldReceive('rescanPlugin')->with('gate-demo')->andReturn([]);
        $needsRescan ? $expectation->atLeast()->once() : $expectation->never();
        $this->app->instance(ExtensionRescanService::class, $rescan);
    }

    private function mockThemeScan(PluginEnableAction $action): void
    {
        $scorer = Mockery::mock(ThemeHealthScorer::class);
        $scorer->shouldReceive('calculate')->with('gate-theme')->andReturn(new HealthScoreResult(90, PluginHealthStatus::Healthy));
        $scorer->shouldReceive('determineEnableAction')->andReturn($action);
        $this->app->instance(ThemeHealthScorer::class, $scorer);

        // No audit is stored for the theme, so the gate must scan it first.
        $rescan = Mockery::mock(ExtensionRescanService::class);
        $rescan->shouldReceive('rescanTheme')->with('gate-theme')->atLeast()->once()->andReturn([]);
        $this->app->instance(ExtensionRescanService::class, $rescan);
    }
}
