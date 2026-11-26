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

namespace Tests\Feature\Admin\Settings\Base;

use App\Enums\MemberRole;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\Member;
use App\Models\RolePermissionOverride;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * The mail settings are super admin only, whatever the menu permission
 * says (#929).
 *
 * The menu permission for settings.base.mail can be lowered on the role
 * permission screen. Every test below lowers it to ADMIN first, so an
 * ADMIN passes the menu gates and only the super admin check stands in
 * the way.
 */
class MailSettingsSuperAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            CheckInstallationReady::class,
            ContentSecurityPolicy::class,
            EnsureEmailIsVerified::class,
        ]);

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';

        $adminTheme = config('themes.admin_theme', 'admin');
        View::addNamespace('admin', [resource_path("views/{$adminTheme}")]);

        foreach (['settings.base', 'settings.base.mail'] as $menuKey) {
            RolePermissionOverride::setCoreOverride($menuKey, MemberRole::ADMIN->value, MemberRole::ADMIN->value);
        }

        SiteSetting::setValue('mail_host', 'mail.example.org');
        SiteSetting::setValue('mail_connection_tested', 1);
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        unset($_ENV['INSTALLED']);

        parent::tearDown();
    }

    private function member(MemberRole $role): Member
    {
        return Member::factory()->create([
            'account_name' => strtolower($role->name).'x',
            'email' => strtolower($role->name).'@example.com',
            'email_verified_at' => now(),
            'role' => $role,
            'status' => 1,
        ]);
    }

    public function test_an_admin_cannot_open_the_mail_settings(): void
    {
        $this->actingAs($this->member(MemberRole::ADMIN), 'member')
            ->get(route('admin.settings.base.mail'))
            ->assertForbidden();
    }

    public function test_an_admin_cannot_save_the_mail_settings(): void
    {
        $this->actingAs($this->member(MemberRole::ADMIN), 'member')
            ->post(route('admin.settings.base.mail.update'), [
                'mail_mailer' => 'smtp',
                'mail_host' => 'attacker.example.net',
                'mail_port' => 25,
            ])
            ->assertForbidden();

        $this->assertSame('mail.example.org', SiteSetting::getValue('mail_host'));
    }

    public function test_an_admin_cannot_reset_the_mail_tests(): void
    {
        $this->actingAs($this->member(MemberRole::ADMIN), 'member')
            ->post(route('admin.settings.base.mail.clear-test-session'))
            ->assertForbidden();

        $this->assertSame(1, (int) SiteSetting::getValue('mail_connection_tested'));
    }

    public function test_an_admin_cannot_run_the_mail_tests(): void
    {
        $admin = $this->member(MemberRole::ADMIN);

        $this->actingAs($admin, 'member')
            ->post(route('admin.settings.base.mail.test-connection'))
            ->assertForbidden();
        $this->actingAs($admin, 'member')
            ->post(route('admin.settings.base.mail.test-mail'))
            ->assertForbidden();
        $this->actingAs($admin, 'member')
            ->get(route('admin.settings.base.mail.check-test-session'))
            ->assertForbidden();
    }

    public function test_a_super_admin_can_open_the_mail_settings(): void
    {
        $this->actingAs($this->member(MemberRole::SUPER_ADMIN), 'member')
            ->get(route('admin.settings.base.mail'))
            ->assertOk();
    }
}
