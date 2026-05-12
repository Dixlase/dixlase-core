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

namespace Tests\Feature\Admin\Profile;

use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\CheckMenuAccess;
use App\Http\Middleware\CheckMenuEdit;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\Member;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * Sidebar menu visibility preferences feature test
 */
class AdminProfileSidebarTest extends TestCase
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

    /**
     * Test saving hidden menus successfully
     */
    public function test_can_save_hidden_menus(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->postJson(route('admin.profile.sidebar.update'), [
                'hidden' => ['media', 'settings.security'],
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->admin->refresh();
        $this->assertEquals(['media', 'settings.security'], $this->admin->sidebar_preferences['hidden']);
    }

    /**
     * Test saving empty hidden array clears preferences
     */
    public function test_can_clear_hidden_menus(): void
    {
        $this->admin->update([
            'sidebar_preferences' => ['hidden' => ['media']],
        ]);

        $response = $this->actingAs($this->admin, 'member')
            ->postJson(route('admin.profile.sidebar.update'), [
                'hidden' => [],
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->admin->refresh();
        $this->assertEquals([], $this->admin->sidebar_preferences['hidden']);
    }

    /**
     * Test dashboard cannot be hidden (protected menu)
     */
    public function test_dashboard_cannot_be_hidden(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->postJson(route('admin.profile.sidebar.update'), [
                'hidden' => ['dashboard', 'media'],
            ]);

        $response->assertOk();

        $this->admin->refresh();
        $this->assertNotContains('dashboard', $this->admin->sidebar_preferences['hidden']);
        $this->assertContains('media', $this->admin->sidebar_preferences['hidden']);
    }

    /**
     * Test profile cannot be hidden (protected menu)
     */
    public function test_profile_cannot_be_hidden(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->postJson(route('admin.profile.sidebar.update'), [
                'hidden' => ['profile', 'profile.basic', 'media'],
            ]);

        $response->assertOk();

        $this->admin->refresh();
        $this->assertNotContains('profile', $this->admin->sidebar_preferences['hidden']);
        $this->assertNotContains('profile.basic', $this->admin->sidebar_preferences['hidden']);
        $this->assertContains('media', $this->admin->sidebar_preferences['hidden']);
    }

    /**
     * Test hidden field must be present
     */
    public function test_hidden_field_is_required(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->postJson(route('admin.profile.sidebar.update'), []);

        $response->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonStructure(['error' => ['details' => ['hidden']]]);
    }

    /**
     * Test hidden items must be strings
     */
    public function test_hidden_items_must_be_strings(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->postJson(route('admin.profile.sidebar.update'), [
                'hidden' => [123, null],
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonStructure(['error' => ['details' => ['hidden.0', 'hidden.1']]]);
    }

    /**
     * Test hidden items must match expected format
     */
    public function test_hidden_items_must_match_key_format(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->postJson(route('admin.profile.sidebar.update'), [
                'hidden' => ['valid_key', 'also.valid-key', '<script>alert(1)</script>'],
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonStructure(['error' => ['details' => ['hidden.2']]]);
    }

    /**
     * Test unauthenticated access is rejected
     */
    public function test_unauthenticated_access_is_rejected(): void
    {
        $response = $this->postJson(route('admin.profile.sidebar.update'), [
            'hidden' => ['media'],
        ]);

        $response->assertUnauthorized();
    }

    /**
     * Test preferences are stored per member
     */
    public function test_preferences_are_per_member(): void
    {
        $otherAdmin = Member::create([
            'account_name' => 'otheradmin',
            'display_name' => 'Other Admin',
            'email' => 'other@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => MemberStatus::Active,
        ]);

        // First admin hides media
        $this->actingAs($this->admin, 'member')
            ->postJson(route('admin.profile.sidebar.update'), [
                'hidden' => ['media'],
            ]);

        // Second admin hides settings
        $this->actingAs($otherAdmin, 'member')
            ->postJson(route('admin.profile.sidebar.update'), [
                'hidden' => ['settings'],
            ]);

        $this->admin->refresh();
        $otherAdmin->refresh();

        $this->assertEquals(['media'], $this->admin->sidebar_preferences['hidden']);
        $this->assertEquals(['settings'], $otherAdmin->sidebar_preferences['hidden']);
    }
}
