<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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

namespace Tests\Feature\Admin\Privacy;

use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\CheckMenuAccess;
use App\Http\Middleware\CheckMenuEdit;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\Member;
use App\Models\Site;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class UserDataControllerTest extends TestCase
{
    use RefreshDatabase;

    private Member $superAdmin;

    private Member $editor;

    private Member $subject;

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

        Site::factory()->primary()->create(['slug' => 'primary']);
        SiteSetting::setValue('site_name', 'Test Site');

        $adminTheme = config('themes.admin_theme', 'admin');
        View::addNamespace('admin', [resource_path("views/{$adminTheme}")]);

        $this->superAdmin = Member::create([
            'account_name' => 'super',
            'display_name' => 'Super',
            'email' => 'super@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => MemberStatus::Active,
        ]);

        $this->editor = Member::create([
            'account_name' => 'editor',
            'display_name' => 'Editor',
            'email' => 'editor@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::EDITOR,
            'status' => MemberStatus::Active,
        ]);

        $this->subject = Member::create([
            'account_name' => 'subject',
            'display_name' => 'Subject',
            'email' => 'subject@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::EDITOR,
            'status' => MemberStatus::Active,
        ]);
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        parent::tearDown();
    }

    public function test_index_renders_for_super_admin(): void
    {
        $response = $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.privacy.users.index'));

        $response->assertOk();
        $response->assertSee('privacy', false);
    }

    public function test_index_is_forbidden_for_non_super_admin(): void
    {
        $response = $this->actingAs($this->editor, 'member')
            ->get(route('admin.privacy.users.index'));

        $response->assertForbidden();
    }

    public function test_index_finds_member_by_email_and_shows_actions(): void
    {
        $response = $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.privacy.users.index', ['search' => 'subject@example.com']));

        $response->assertOk();
        $response->assertSee('subject@example.com');
        $response->assertSee(route('admin.privacy.users.export', ['id' => $this->subject->id, 'scope' => 'site']), false);
    }

    public function test_export_streams_a_zip_attachment(): void
    {
        $response = $this->actingAs($this->superAdmin, 'member')
            ->get(route('admin.privacy.users.export', [
                'id' => $this->subject->id,
                'scope' => 'network',
            ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/zip');
        $disposition = $response->headers->get('content-disposition');
        $this->assertNotNull($disposition);
        $this->assertStringContainsString('privacy-export-member'.$this->subject->id.'-network', $disposition);
    }

    public function test_delete_anonymize_redirects_with_status_and_modifies_subject(): void
    {
        $response = $this->actingAs($this->superAdmin, 'member')
            ->post(route('admin.privacy.users.delete', ['id' => $this->subject->id]), [
                'mode' => 'anonymize',
                'scope' => 'network',
                'confirm' => '1',
            ]);

        $response->assertRedirect(route('admin.privacy.users.index'));
        $response->assertSessionHas('status');

        $row = DB::table('members')->where('id', $this->subject->id)->first();
        $this->assertNotSame('subject@example.com', $row->email);
    }

    public function test_delete_requires_confirm_checkbox(): void
    {
        $response = $this->actingAs($this->superAdmin, 'member')
            ->post(route('admin.privacy.users.delete', ['id' => $this->subject->id]), [
                'mode' => 'anonymize',
                'scope' => 'network',
                // no confirm
            ]);

        $response->assertSessionHasErrors('confirm');
    }
}
