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

namespace Tests\Feature\Admin\Settings;

use App\DTO\Plugin\HealthScoreResult;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Enums\PluginEnableAction;
use App\Enums\PluginHealthStatus;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\Member;
use App\Models\Theme;
use App\Services\Theme\ThemeHealthScorer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Mockery;
use Tests\TestCase;

/**
 * Theme install and switch apply the same health gate as plugins.
 *
 * Regression (security review X2): under a preset that requires a scan,
 * themes were installed without any scan, and switch() ran the audit but
 * discarded the result, so a Blocked theme could become the live theme.
 *
 * The "allowed" cases let the install / switch command fail on purpose:
 * that proves the gate let the request through without running the
 * success path, which writes the repository's .gitignore.
 */
class ThemeInstallSecurityTest extends TestCase
{
    use RefreshDatabase;

    private Member $admin;

    /** @var array{0: string|false, 1: ?string, 2: ?string} */
    private array $savedInstalled;

    protected function setUp(): void
    {
        parent::setUp();

        $this->savedInstalled = [getenv('INSTALLED'), $_ENV['INSTALLED'] ?? null, $_SERVER['INSTALLED'] ?? null];
        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';
        $_SERVER['INSTALLED'] = 'true';

        $adminTheme = config('themes.admin_theme', 'admin');
        $customFilesDir = base_path(config('custom.custom_files_dir', 'custom'));
        View::addNamespace('admin', [
            base_path("{$customFilesDir}/resources/views/{$adminTheme}"),
            resource_path("views/{$adminTheme}"),
        ]);

        \App\Models\SiteSetting::setValue('site_name', 'Test Site');

        $this->admin = Member::create([
            'account_name' => 'testadmin',
            'display_name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => MemberStatus::Active,
        ]);

        $this->withoutMiddleware([
            CheckInstallationReady::class,
            EnsureEmailIsVerified::class,
        ]);
    }

    /**
     * Put INSTALLED back the way it was rather than forcing 'false': later
     * suites (Api\V1) set it through putenv / $_ENV only, and a leftover
     * $_SERVER value of 'false' takes precedence and sends them to /install.
     */
    protected function tearDown(): void
    {
        [$env, $envArray, $server] = $this->savedInstalled;
        putenv($env === false ? 'INSTALLED' : 'INSTALLED='.$env);
        if ($envArray === null) {
            unset($_ENV['INSTALLED']);
        } else {
            $_ENV['INSTALLED'] = $envArray;
        }
        if ($server === null) {
            unset($_SERVER['INSTALLED']);
        } else {
            $_SERVER['INSTALLED'] = $server;
        }

        parent::tearDown();
    }

    public function test_install_is_refused_when_the_scan_resolves_to_blocked(): void
    {
        Cache::put('security_settings:extension_security_preset', 'strict');
        $this->scorerReturns(PluginEnableAction::Blocked);
        $this->expectAudit('test-theme');
        Artisan::shouldReceive('call')->with('dls:theme:install', Mockery::any())->never();

        $response = $this->actingAs($this->admin, 'member')
            ->post(route('admin.settings.themes.install'), ['directory' => 'TestTheme']);

        $response->assertRedirect();
        $response->assertSessionHas('error', __('http/controllers/admin/settings/admin_themes_settings_controller.theme_install_blocked'));
    }

    public function test_install_is_refused_when_the_health_check_fails(): void
    {
        Cache::put('security_settings:extension_security_preset', 'balanced');
        $scorer = Mockery::mock(ThemeHealthScorer::class);
        $scorer->shouldReceive('calculate')->andThrow(new \RuntimeException('scan failed'));
        $this->app->instance(ThemeHealthScorer::class, $scorer);
        $this->expectAudit('test-theme');
        Artisan::shouldReceive('call')->with('dls:theme:install', Mockery::any())->never();

        $response = $this->actingAs($this->admin, 'member')
            ->post(route('admin.settings.themes.install'), ['directory' => 'TestTheme']);

        $response->assertSessionHas('error', __('http/controllers/admin/settings/admin_themes_settings_controller.theme_install_blocked'));
    }

    public function test_install_proceeds_when_the_scan_allows_it(): void
    {
        Cache::put('security_settings:extension_security_preset', 'balanced');
        $this->scorerReturns(PluginEnableAction::Allowed);
        $this->expectAudit('test-theme');
        Artisan::shouldReceive('call')->with('dls:theme:install', Mockery::any())->once()->andReturn(1);

        $response = $this->actingAs($this->admin, 'member')
            ->post(route('admin.settings.themes.install'), ['directory' => 'TestTheme']);

        $response->assertSessionHas('error', __('http/controllers/admin/settings/admin_themes_settings_controller.theme_installation_failed'));
    }

    public function test_install_is_not_scanned_in_development_mode(): void
    {
        Cache::put('security_settings:extension_security_preset', 'development');
        $this->scorerReturns(PluginEnableAction::Blocked);
        Artisan::shouldReceive('call')->with('dls:theme:audit', Mockery::any())->never();
        Artisan::shouldReceive('call')->with('dls:theme:install', Mockery::any())->once()->andReturn(1);
        Artisan::shouldReceive('output')->andReturn('');

        $response = $this->actingAs($this->admin, 'member')
            ->post(route('admin.settings.themes.install'), ['directory' => 'TestTheme']);

        $response->assertSessionHas('error', __('http/controllers/admin/settings/admin_themes_settings_controller.theme_installation_failed'));
    }

    public function test_switch_is_refused_when_the_scan_resolves_to_blocked(): void
    {
        $theme = $this->makeTheme();
        $this->scorerReturns(PluginEnableAction::Blocked);
        $this->expectAudit('test-theme');
        Artisan::shouldReceive('call')->with('dls:theme:switch', Mockery::any())->never();

        $response = $this->actingAs($this->admin, 'member')
            ->post(route('admin.settings.themes.switch', $theme->id));

        $response->assertRedirect();
        $response->assertSessionHas('error', __('http/controllers/admin/settings/admin_themes_settings_controller.theme_switch_blocked'));
    }

    public function test_switch_proceeds_when_the_scan_allows_it(): void
    {
        $theme = $this->makeTheme();
        $this->scorerReturns(PluginEnableAction::WarningRequired);
        $this->expectAudit('test-theme');
        Artisan::shouldReceive('call')->with('dls:theme:switch', Mockery::any())->once()->andReturn(1);

        $response = $this->actingAs($this->admin, 'member')
            ->post(route('admin.settings.themes.switch', $theme->id));

        $response->assertSessionHas('error', __('http/controllers/admin/settings/admin_themes_settings_controller.theme_switch_failed'));
    }

    private function makeTheme(): Theme
    {
        return Theme::create([
            'name' => 'Test Theme',
            'directory' => 'TestTheme',
            'slug' => 'test-theme',
        ]);
    }

    private function scorerReturns(PluginEnableAction $action): void
    {
        $scorer = Mockery::mock(ThemeHealthScorer::class);
        $scorer->shouldReceive('calculate')
            ->andReturn(new HealthScoreResult(90, PluginHealthStatus::Healthy));
        $scorer->shouldReceive('determineEnableAction')->andReturn($action);
        $this->app->instance(ThemeHealthScorer::class, $scorer);
    }

    /**
     * The gate scans the theme itself rather than trusting an earlier scan.
     */
    private function expectAudit(string $slug): void
    {
        Artisan::shouldReceive('call')
            ->with('dls:theme:audit', Mockery::on(fn ($args) => ($args['theme'] ?? null) === $slug))
            ->once()
            ->andReturn(0);
        Artisan::shouldReceive('output')->andReturn('{}');
    }
}
