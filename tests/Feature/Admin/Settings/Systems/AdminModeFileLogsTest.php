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

namespace Tests\Feature\Admin\Settings\Systems;

use App\Enums\AdminMode;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Helpers\AdminModeHelper;
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
 * かんたんモード時のファイルログアクセス制限テスト
 */
class AdminModeFileLogsTest extends TestCase
{
    use RefreshDatabase;

    private Member $superAdmin;

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

        $this->superAdmin = Member::create([
            'account_name' => 'superadmin',
            'display_name' => 'Super Admin',
            'email' => 'superadmin@example.com',
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

    // ========================================
    // かんたんモード: ファイルログへのアクセス制限
    // ========================================

    public function test_simple_mode_redirects_file_logs_to_audit_logs(): void
    {
        SiteSetting::setValue('admin_mode', (string) AdminMode::Simple->value);
        AdminModeHelper::clearCache();

        $response = $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.systems.logs.files'));

        $response->assertRedirect(route('admin.settings.systems.logs.index'));
    }

    public function test_simple_mode_redirects_file_logs_download_to_audit_logs(): void
    {
        SiteSetting::setValue('admin_mode', (string) AdminMode::Simple->value);
        AdminModeHelper::clearCache();

        $response = $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.systems.logs.download', ['type' => 'admin']));

        $response->assertRedirect(route('admin.settings.systems.logs.index'));
    }

    public function test_simple_mode_allows_audit_logs_access(): void
    {
        SiteSetting::setValue('admin_mode', (string) AdminMode::Simple->value);
        AdminModeHelper::clearCache();

        $response = $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.systems.logs.index'));

        $response->assertStatus(200);
    }

    // ========================================
    // 詳細モード: ファイルログへのアクセス許可
    // ========================================

    public function test_advanced_mode_allows_file_logs_access(): void
    {
        SiteSetting::setValue('admin_mode', (string) AdminMode::Advanced->value);
        AdminModeHelper::clearCache();

        $response = $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.settings.systems.logs.files'));

        $response->assertStatus(200);
    }
}
