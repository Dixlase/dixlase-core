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

namespace Tests\Feature\Admin\Settings\Plugins;

use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\CheckMenuAccess;
use App\Http\Middleware\CheckMenuEdit;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\ExtensionSource;
use App\Models\Member;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * プラグインソースインストール フィーチャーテスト
 */
class PluginSourceInstallTest extends TestCase
{
    use RefreshDatabase;

    private Member $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckInstallationReady::class,
            CheckMenuAccess::class,
            CheckMenuEdit::class,
            EnsureEmailIsVerified::class,
        ]);

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';

        $adminTheme = config('themes.admin_theme', 'admin');
        $customFilesDir = base_path(config('custom.custom_files_dir', 'custom'));
        View::addNamespace('admin', [
            base_path("{$customFilesDir}/resources/views/{$adminTheme}"),
            resource_path("views/{$adminTheme}"),
        ]);

        SiteSetting::setValue('site_name', 'Test Site');

        $this->admin = Member::create([
            'account_name' => 'testadmin',
            'display_name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => MemberStatus::Active,
        ]);
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        parent::tearDown();
    }

    public function test_add_page_displays_tab_navigation(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->get(route('admin.settings.plugins.add'));

        $response->assertOk();
        $response->assertSeeText(__('admin/settings/plugins/add.tab_zip'));
        $response->assertSeeText(__('admin/settings/plugins/add.tab_online'));
    }

    public function test_available_from_source_returns_plugin_list(): void
    {
        $owner = config('extension-sources.github.default_owner', 'Dixlase');

        ExtensionSource::query()->create([
            'name' => 'Test Source',
            'type' => 'github',
            'base_url' => 'https://api.github.com',
            'owner' => $owner,
            'is_enabled' => true,
            'priority' => 0,
        ]);

        Http::fake([
            "api.github.com/orgs/{$owner}/repos*" => Http::sequence()
                ->push([
                    ['name' => 'plugin-dixlase-sample-plugin', 'description' => 'A sample plugin'],
                ])
                ->push([]),
        ]);

        $response = $this->actingAs($this->admin, 'member')
            ->getJson(route('admin.settings.plugins.available-from-source'));

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure(['success', 'plugins']);
    }

    public function test_available_from_source_returns_empty_when_the_default_source_offers_nothing(): void
    {
        // No ExtensionSource row exists here, but the endpoint does not
        // answer "no sources": getEnabledSources() seeds the config
        // presets first, so the official GitHub source is created and
        // then queried. That request used to leave the runner, and the
        // assertion held only while GitHub's anonymous rate limit --
        // counted per IP, and shared with every other GitHub-hosted
        // runner -- still had budget (dixlase-core#680).
        //
        // preventStrayRequests() only takes effect while faking is
        // active, so the fake below is what keeps the request in, and
        // the guard is what reports any other URL this path starts
        // asking for.
        Http::preventStrayRequests();
        Http::fake([
            'api.github.com/*' => Http::response([], 200),
        ]);

        $response = $this->actingAs($this->admin, 'member')
            ->getJson(route('admin.settings.plugins.available-from-source'));

        $response->assertOk();
        $response->assertJson(['success' => true]);
        // assertJson treats an expected [] as a subset, which every list
        // satisfies, so the emptiness is asserted by count instead.
        $response->assertJsonCount(0, 'plugins');
        $this->assertSame(1, ExtensionSource::query()->ofType('github')->count());
        Http::assertSentCount(1);
    }

    public function test_download_from_source_validates_slug(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->post(route('admin.settings.plugins.download-from-source'), [
                // slug is missing
            ]);

        $response->assertSessionHasErrors('slug');
    }

    public function test_download_from_source_handles_failure_gracefully(): void
    {
        Http::fake([
            '*' => Http::response([], 404),
        ]);

        $response = $this->actingAs($this->admin, 'member')
            ->from(route('admin.settings.plugins.add'))
            ->post(route('admin.settings.plugins.download-from-source'), [
                'slug' => 'nonexistent-plugin',
            ]);

        $response->assertRedirect(route('admin.settings.plugins.add'));
        $response->assertSessionHas('error');
    }
}
