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

namespace Tests\Feature\Admin\Settings\Base;

use App\Enums\MemberRole;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\CheckMenuAccess;
use App\Http\Middleware\CheckMenuEdit;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\BaseSetting;
use App\Models\Member;
use App\Services\Editor\EditorManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 管理画面設定ページのGUIエディター設定テスト
 */
class AdminBaseAdminEditorSettingTest extends TestCase
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

        BaseSetting::setValue('site_name', 'Test Site');

        $this->admin = Member::factory()->create([
            'role' => MemberRole::SUPER_ADMIN,
        ]);
    }

    // ========================================
    // 表示テスト
    // ========================================

    public function test_admin_settings_page_shows_content_editor_section(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->get(route('admin.settings.base.admin'));

        $response->assertStatus(200);
        $response->assertViewHas('guiEditors');
    }

    public function test_admin_settings_page_passes_gui_editors_as_array(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->get(route('admin.settings.base.admin'));

        $response->assertStatus(200);
        $guiEditors = $response->viewData('guiEditors');
        $this->assertIsArray($guiEditors);
    }

    public function test_admin_settings_page_includes_preferred_gui_editor_in_settings(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->get(route('admin.settings.base.admin'));

        $response->assertStatus(200);
        $settings = $response->viewData('settings');
        $this->assertArrayHasKey('preferred_gui_editor', $settings);
    }

    // ========================================
    // 保存テスト
    // ========================================

    public function test_preferred_gui_editor_is_saved(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->post(route('admin.settings.base.admin.update'), [
                'admin_url_prefix' => 'admin',
                'admin_url_suffix' => 'test1234',
                'preferred_gui_editor' => 'dixlase-gui-editor',
            ]);

        $response->assertRedirect();
        $this->assertEquals(
            'dixlase-gui-editor',
            BaseSetting::get(EditorManager::PREFERRED_GUI_EDITOR_KEY),
        );
    }

    public function test_preferred_gui_editor_can_be_empty(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->post(route('admin.settings.base.admin.update'), [
                'admin_url_prefix' => 'admin',
                'admin_url_suffix' => 'test1234',
                'preferred_gui_editor' => '',
            ]);

        $response->assertRedirect();
        $this->assertEquals(
            '',
            BaseSetting::get(EditorManager::PREFERRED_GUI_EDITOR_KEY),
        );
    }

    // ========================================
    // 認証テスト
    // ========================================

    public function test_guest_cannot_access_admin_settings(): void
    {
        $this->withMiddleware([
            CheckInstallationReady::class,
        ]);

        $response = $this->get(route('admin.settings.base.admin'));

        $response->assertRedirect();
    }
}
