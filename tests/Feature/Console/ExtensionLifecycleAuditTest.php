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

use App\Enums\MemberRole;
use App\Enums\PluginEnableAction;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\Plugin;
use App\Models\Theme;
use App\Repositories\SecuritySettingRepository;
use App\Services\Extension\ExtensionActivationGate;
use App\Services\Extension\ExtensionRescanService;
use App\Services\Tailwind\PluginSourceAggregator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Mockery;
use Tests\TestCase;

/**
 * The extension lifecycle commands write the audit row themselves
 * (dixlase-core#630), so a command-line run is recorded like an admin one.
 *
 * Regression: install, uninstall, enable, disable, delete and theme switch
 * were recorded only by the admin controllers after they called the
 * command, so the same operation run from the command line left nothing.
 *
 * The commands run against a throwaway base path under
 * storage/framework/testing/, never the repository's plugins/ or themes/.
 */
class ExtensionLifecycleAuditTest extends TestCase
{
    use RefreshDatabase;

    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = base_path('storage/framework/testing/lifecycle-audit-'.uniqid('', true));
        File::ensureDirectoryExists($this->root.'/plugins');
        File::ensureDirectoryExists($this->root.'/themes');
        File::ensureDirectoryExists($this->root.'/public');

        // The Tailwind source aggregator writes a tracked CSS file.
        $aggregator = Mockery::mock(PluginSourceAggregator::class);
        $aggregator->shouldReceive('regenerate')->andReturn([]);
        $this->app->instance(PluginSourceAggregator::class, $aggregator);

        // The install path scans the new code for the row's health; the
        // scan itself is not under test.
        $rescan = Mockery::mock(ExtensionRescanService::class);
        $rescan->shouldReceive('rescanPlugin')->andReturn([]);
        $rescan->shouldReceive('rescanTheme')->andReturn([]);
        $this->app->instance(ExtensionRescanService::class, $rescan);

        app(SecuritySettingRepository::class)->set('extension_security_preset', 'development');

        $langPath = $this->app->langPath();
        $this->app->setBasePath($this->root);
        $this->app->useLangPath($langPath);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->root);

        parent::tearDown();
    }

    public function test_installing_a_plugin_from_the_cli_is_recorded(): void
    {
        $this->placePluginOnDisk();

        $this->artisan('dls:plugin:install', ['pluginName' => 'AuditDemo', '--skip-build' => true])
            ->assertExitCode(0);

        $row = $this->onlyRow(AuditLog::ACTION_PLUGIN_INSTALLED);
        $this->assertSame(AuditLog::ACTOR_SOURCE_CLI, $row->actor_source);
        $this->assertNull($row->actor_id);
        $this->assertSame(Plugin::class, $row->target_type);
        $this->assertSame('audit-demo', $row->context['extension_slug']);
        $this->assertArrayNotHasKey('forced', $row->context);
    }

    public function test_a_forced_install_past_the_license_guard_records_the_refusal(): void
    {
        // A manifest with no licence is refused unless --force is passed.
        $this->placePluginOnDisk(license: null);

        $this->artisan('dls:plugin:install', ['pluginName' => 'AuditDemo', '--skip-build' => true])
            ->assertExitCode(1);
        $this->assertSame(0, AuditLog::where('action', AuditLog::ACTION_PLUGIN_INSTALLED)->count());

        $this->artisan('dls:plugin:install', ['pluginName' => 'AuditDemo', '--skip-build' => true, '--force' => true])
            ->assertExitCode(0);

        $row = $this->onlyRow(AuditLog::ACTION_PLUGIN_INSTALLED);
        $this->assertArrayHasKey('license', $row->context['forced']);
        $this->assertNotSame('', $row->context['forced']['license']);
    }

    public function test_enabling_and_disabling_a_plugin_from_the_cli_is_recorded(): void
    {
        $this->makePlugin();

        $this->artisan('dls:plugin:enable', ['pluginName' => 'AuditDemo'])->assertExitCode(0);
        $this->artisan('dls:plugin:disable', ['pluginName' => 'AuditDemo'])->assertExitCode(0);

        foreach ([AuditLog::ACTION_PLUGIN_ENABLED, AuditLog::ACTION_PLUGIN_DISABLED] as $action) {
            $row = $this->onlyRow($action);
            $this->assertSame(AuditLog::ACTOR_SOURCE_CLI, $row->actor_source);
            $this->assertSame(AuditLog::CATEGORY_EXTENSION, $row->category);
        }
    }

    public function test_a_forced_enable_past_the_activation_gate_records_the_outcome(): void
    {
        $this->makePlugin();

        $gate = Mockery::mock(ExtensionActivationGate::class);
        $gate->shouldReceive('isEnforced')->andReturn(true);
        $gate->shouldReceive('resolve')->andReturn(PluginEnableAction::WarningRequired);
        $this->app->instance(ExtensionActivationGate::class, $gate);

        $this->artisan('dls:plugin:enable', ['pluginName' => 'AuditDemo', '--force' => true])->assertExitCode(0);

        $row = $this->onlyRow(AuditLog::ACTION_PLUGIN_ENABLED);
        $this->assertSame(PluginEnableAction::WarningRequired->value, $row->context['forced']['activation_gate']);
    }

    public function test_applied_by_names_the_admin_member(): void
    {
        $member = Member::factory()->create(['role' => MemberRole::SUPER_ADMIN]);
        $this->makePlugin();

        $this->artisan('dls:plugin:enable', ['pluginName' => 'AuditDemo', '--applied-by' => (string) $member->id])
            ->assertExitCode(0);

        $row = $this->onlyRow(AuditLog::ACTION_PLUGIN_ENABLED);
        $this->assertSame(Member::class, $row->actor_type);
        $this->assertSame($member->id, (int) $row->actor_id);
    }

    public function test_uninstalling_and_deleting_a_plugin_from_the_cli_is_recorded(): void
    {
        $this->placePluginOnDisk();
        $this->makePlugin();

        $this->artisan('dls:plugin:uninstall', ['pluginName' => 'AuditDemo', '--no-interaction' => true])
            ->assertExitCode(0);
        $this->artisan('dls:plugin:delete', ['pluginDirectory' => 'AuditDemo', '--force' => true])
            ->assertExitCode(0);

        $this->assertSame('audit-demo', $this->onlyRow(AuditLog::ACTION_PLUGIN_UNINSTALLED)->context['extension_slug']);

        $deleted = $this->onlyRow(AuditLog::ACTION_PLUGIN_DELETED);
        $this->assertSame('audit-demo', $deleted->context['extension_slug']);
        $this->assertSame(AuditLog::ACTOR_SOURCE_CLI, $deleted->actor_source);
        $this->assertDirectoryDoesNotExist($this->root.'/plugins/AuditDemo');
    }

    public function test_switching_the_theme_from_the_cli_is_recorded(): void
    {
        $theme = $this->makeTheme();

        $this->artisan('dls:theme:switch', ['themeName' => 'audit-theme'])->assertExitCode(0);

        $row = $this->onlyRow(AuditLog::ACTION_THEME_ENABLED);
        $this->assertSame(AuditLog::ACTOR_SOURCE_CLI, $row->actor_source);
        $this->assertSame(Theme::class, $row->target_type);
        $this->assertSame($theme->id, (int) $row->target_id);
    }

    public function test_a_switch_to_the_theme_already_in_use_writes_nothing(): void
    {
        $theme = $this->makeTheme();
        DB::table('theme_settings')->where('key', 'enabled_theme_id')->update(['value' => (string) $theme->id]);

        $this->artisan('dls:theme:switch', ['themeName' => 'audit-theme'])->assertExitCode(0);

        $this->assertSame(0, AuditLog::where('action', AuditLog::ACTION_THEME_ENABLED)->count());
    }

    public function test_the_extension_log_setting_still_turns_recording_off(): void
    {
        app(SecuritySettingRepository::class)->set('extension_log_operations', false);
        $this->makePlugin();

        $this->artisan('dls:plugin:enable', ['pluginName' => 'AuditDemo'])->assertExitCode(0);

        $this->assertSame(0, AuditLog::where('action', AuditLog::ACTION_PLUGIN_ENABLED)->count());
    }

    private function onlyRow(string $action): AuditLog
    {
        $rows = AuditLog::where('action', $action)->get();
        $this->assertCount(1, $rows, "expected one {$action} row");

        return $rows->first();
    }

    private function placePluginOnDisk(?string $license = 'MIT'): void
    {
        File::ensureDirectoryExists($this->root.'/plugins/AuditDemo');
        File::put($this->root.'/plugins/AuditDemo/plugin.json', (string) json_encode(array_filter([
            'name' => 'AuditDemo',
            'slug' => 'audit-demo',
            'version' => '1.0.0',
            'license' => $license,
        ])));
    }

    private function makePlugin(): Plugin
    {
        return Plugin::forceCreate([
            'name' => 'AuditDemo',
            'namespace' => 'Plugins\\AuditDemo',
            'directory' => 'AuditDemo',
            'slug' => 'audit-demo',
            'version' => '1.0.0',
            'installed_at' => now(),
        ]);
    }

    private function makeTheme(): Theme
    {
        DB::table('theme_settings')->insert([
            'site_id' => 1,
            'key' => 'enabled_theme_id',
            'value' => '0',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Theme::forceCreate([
            'name' => 'AuditTheme',
            'directory' => 'AuditTheme',
            'slug' => 'audit-theme',
            'version' => '1.0.0',
            'installed_at' => now(),
        ]);
    }
}
