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

namespace Tests\Feature\Admin;

use App\Enums\MemberRole;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\FrontPage;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * Admin forms still save with their optional fields left empty (#642)
 *
 * Restoring ConvertEmptyStringsToNull turns an empty input into null, which
 * a NOT NULL column would reject. These forms write such fields, so they are
 * posted here with every optional field empty, through the real global
 * middleware stack.
 */
class EmptyOptionalFieldsSaveTest extends TestCase
{
    use RefreshDatabase;

    private Member $admin;

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

        $this->admin = Member::factory()->create([
            'id' => 1,
            'account_name' => 'owner',
            'email' => 'owner@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        unset($_ENV['INSTALLED']);

        parent::tearDown();
    }

    public function test_the_profile_saves_with_empty_optional_fields(): void
    {
        $this->actingAs($this->admin, 'member')
            ->post(route('admin.profile.basic.update'), [
                'account_name' => 'owner',
                'email' => 'owner@example.com',
                'display_name' => '',
                'description' => '',
                'locale' => '',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $member = $this->admin->fresh();
        $this->assertNull($member->display_name);
        $this->assertSame('', $member->description);
        $this->assertNull($member->locale);
    }

    public function test_the_front_page_saves_with_empty_parts(): void
    {
        $this->actingAs($this->admin, 'member')
            ->post(route('admin.front.store'), [
                'lang' => 'en',
                'editor_type' => 'html',
                'storage_type' => 'database',
                'content' => '<h1>Front</h1>',
                'custom_css' => '',
                'custom_js' => '',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.front.edit'));

        $this->assertNotNull(FrontPage::findByType('main_content'));
    }
}
