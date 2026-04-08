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

namespace Tests\Feature\Admin\Settings\Security;

use App\Contracts\Repositories\SecuritySettingRepositoryInterface;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\CheckMenuAccess;
use App\Http\Middleware\CheckMenuEdit;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\BaseSetting;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * 拡張機能ソース設定 フィーチャーテスト
 */
class ExtensionSourceSettingsTest extends TestCase
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

        BaseSetting::setValue('site_name', 'Test Site');

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

    public function test_extension_settings_page_displays_source_section(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->get(route('admin.settings.security.extensions'));

        $response->assertOk();
        $response->assertSeeText(__('admin/settings/security/extensions.source.title'));
        $response->assertSeeText(__('admin/settings/security/extensions.source.source_type'));
    }

    public function test_source_settings_can_be_saved(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->from(route('admin.settings.security.extensions'))
            ->post(route('admin.settings.security.extensions.update'), [
                'extension_security_preset' => 'balanced',
                'extension_plugin_max_health_level' => 1,
                'extension_theme_max_health_level' => 2,
                'extension_permission_mismatch_action' => 'warn',
                'extension_source_type' => 'github',
                'extension_update_check_interval' => 43200,
            ]);

        $response->assertRedirect(route('admin.settings.security.extensions'));
        $response->assertSessionHas('success');

        $repo = app(SecuritySettingRepositoryInterface::class);
        $this->assertEquals('github', $repo->get('extension_source_type'));
        $this->assertEquals(43200, (int) $repo->get('extension_update_check_interval'));
    }

    public function test_source_type_validation_rejects_unknown_type(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->from(route('admin.settings.security.extensions'))
            ->post(route('admin.settings.security.extensions.update'), [
                'extension_security_preset' => 'balanced',
                'extension_plugin_max_health_level' => 1,
                'extension_theme_max_health_level' => 2,
                'extension_permission_mismatch_action' => 'warn',
                'extension_source_type' => 'unknown_source',
                'extension_update_check_interval' => 86400,
            ]);

        $response->assertSessionHasErrors('extension_source_type');
    }

    public function test_check_interval_validation_rejects_invalid_value(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->from(route('admin.settings.security.extensions'))
            ->post(route('admin.settings.security.extensions.update'), [
                'extension_security_preset' => 'balanced',
                'extension_plugin_max_health_level' => 1,
                'extension_theme_max_health_level' => 2,
                'extension_permission_mismatch_action' => 'warn',
                'extension_source_type' => 'github',
                'extension_update_check_interval' => 9999,
            ]);

        $response->assertSessionHasErrors('extension_update_check_interval');
    }

    public function test_test_source_api_returns_success_on_valid_connection(): void
    {
        $owner = config('extension-sources.github.default_owner', 'Dixlase');

        Http::fake([
            "api.github.com/users/{$owner}" => Http::response([
                'login' => $owner,
            ], 200, [
                'X-RateLimit-Remaining' => '59',
            ]),
        ]);

        $response = $this->actingAs($this->admin, 'member')
            ->postJson(route('admin.settings.security.extensions.test-source'), [
                'type' => 'github',
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'is_official' => true,
        ]);
        $response->assertJsonStructure(['success', 'message', 'details', 'is_official']);
    }

    public function test_test_source_api_returns_failure_on_connection_error(): void
    {
        config()->set('extension-sources.github.default_token', null);
        $owner = config('extension-sources.github.default_owner', 'Dixlase');

        Http::fake([
            "api.github.com/users/{$owner}" => Http::response(['message' => 'Not Found'], 404),
        ]);

        $response = $this->actingAs($this->admin, 'member')
            ->postJson(route('admin.settings.security.extensions.test-source'), [
                'type' => 'github',
            ]);

        $response->assertOk();
        $response->assertJson(['success' => false]);
    }

    public function test_test_source_api_validates_input(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->postJson(route('admin.settings.security.extensions.test-source'), [
                // type is missing
                'owner' => 'TestOrg',
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('type');
    }

    public function test_test_source_uses_config_values(): void
    {
        Http::fake([
            'api.github.com/user' => Http::response([
                'login' => 'config-user',
            ], 200),
        ]);

        $response = $this->actingAs($this->admin, 'member')
            ->postJson(route('admin.settings.security.extensions.test-source'), [
                'type' => 'github',
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }
}
