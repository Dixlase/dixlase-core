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

use App\Contracts\RouteSlugProvider;
use App\DTO\RouteSlug\RegisteredSlug;
use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\CheckMenuAccess;
use App\Http\Middleware\CheckMenuEdit;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\BaseSetting;
use App\Models\Member;
use App\Services\RouteSlugRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Mockery;
use Tests\TestCase;

/**
 * ルートスラッグバリデーション フィーチャーテスト
 */
class RouteSlugValidationTest extends TestCase
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

    /**
     * 管理画面URLの更新で予約パスを拒否することを検証
     */
    public function test_admin_url_update_rejects_reserved_path(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->from(route('admin.settings.base.admin'))
            ->post(route('admin.settings.base.admin.update'), [
                'admin_url' => 'api',
            ]);

        $response->assertSessionHasErrors('admin_url');
    }

    /**
     * 管理画面URLの更新でプロバイダースラッグとの競合を拒否することを検証
     */
    public function test_admin_url_update_rejects_conflict_with_provider(): void
    {
        $provider = Mockery::mock(RouteSlugProvider::class);
        $provider->shouldReceive('getRouteSlugs')
            ->andReturn([
                new RegisteredSlug(
                    slug: 'pages',
                    owner: 'dixlase-pages:route_slug',
                    label: 'Pages Directory',
                ),
            ]);

        $registry = app(RouteSlugRegistry::class);
        $registry->registerProvider('dixlase-pages', $provider);

        $response = $this->actingAs($this->admin, 'member')
            ->from(route('admin.settings.base.admin'))
            ->post(route('admin.settings.base.admin.update'), [
                'admin_url' => 'pages',
            ]);

        $response->assertSessionHasErrors('admin_url');
    }

    /**
     * 管理画面URLの更新で現在値と同じ値は許可することを検証
     */
    public function test_admin_url_update_allows_current_value(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->from(route('admin.settings.base.admin'))
            ->post(route('admin.settings.base.admin.update'), [
                'admin_url' => 'admin',
            ]);

        $response->assertSessionDoesntHaveErrors('admin_url');
    }

    /**
     * 管理画面URLの更新で未使用のスラッグを許可することを検証
     */
    public function test_admin_url_update_allows_unique_slug(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->from(route('admin.settings.base.admin'))
            ->post(route('admin.settings.base.admin.update'), [
                'admin_url' => 'my-custom-admin',
            ]);

        $response->assertSessionDoesntHaveErrors('admin_url');
    }

    /**
     * 管理画面URLの更新で login を拒否することを検証
     */
    public function test_admin_url_update_rejects_login(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->from(route('admin.settings.base.admin'))
            ->post(route('admin.settings.base.admin.update'), [
                'admin_url' => 'login',
            ]);

        $response->assertSessionHasErrors('admin_url');
    }

    /**
     * 管理画面URLの更新で storage を拒否することを検証
     */
    public function test_admin_url_update_rejects_storage(): void
    {
        $response = $this->actingAs($this->admin, 'member')
            ->from(route('admin.settings.base.admin'))
            ->post(route('admin.settings.base.admin.update'), [
                'admin_url' => 'storage',
            ]);

        $response->assertSessionHasErrors('admin_url');
    }
}
