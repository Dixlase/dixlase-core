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

namespace Tests\Feature\Admin\Members;

use App\Enums\MemberRole;
use App\Http\Middleware\ApplySessionConfig;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\AuditLog;
use App\Models\Member;
use App\Services\Member\MemberSessionService;
use App\Session\GuardAwareDatabaseSessionHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * Core #674: administrators can list members' active admin sessions and end
 * one at a time.
 */
class MemberSessionListTest extends TestCase
{
    use RefreshDatabase;

    private const CHROME_MAC = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0 Safari/537.36';

    private const SAFARI_IPHONE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';

    private Member $owner;

    private Member $staff;

    private MemberSessionService $sessions;

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

        config(['session.driver' => 'guard-aware-database']);
        GuardAwareDatabaseSessionHandler::forgetStartedAtColumn();

        $this->owner = Member::factory()->create([
            'id' => 1,
            'account_name' => 'owner',
            'email' => 'owner@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::SUPER_ADMIN,
            'status' => 1,
        ]);

        $this->staff = Member::factory()->create([
            'account_name' => 'staff',
            'display_name' => 'Staff Member',
            'email' => 'staff@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::ADMIN,
            'status' => 1,
            'remember_token' => 'remember-me',
        ]);

        $this->sessions = app(MemberSessionService::class);
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        unset($_ENV['INSTALLED']);
        GuardAwareDatabaseSessionHandler::forgetStartedAtColumn();

        parent::tearDown();
    }

    public function test_a_member_s_active_sessions_are_listed_newest_first(): void
    {
        $this->memberSession($this->staff, 'staff-old', self::CHROME_MAC, time() - 600, time() - 3600);
        $this->memberSession($this->staff, 'staff-new', self::SAFARI_IPHONE, time() - 60, null);
        $this->memberSession($this->staff, 'staff-expired', self::CHROME_MAC, time() - (config('session.lifetime') + 1) * 60);
        $this->memberSession($this->owner, 'owner-a', self::CHROME_MAC);

        $rows = $this->sessions->forMember($this->staff);

        $this->assertCount(2, $rows);
        $this->assertSame($this->device('Safari', 'iOS'), $rows[0]['device']);
        $this->assertNull($rows[0]['started_at']);
        $this->assertSame($this->device('Chrome', 'macOS'), $rows[1]['device']);
        $this->assertNotNull($rows[1]['started_at']);
        $this->assertSame('203.0.113.7', $rows[1]['ip_address']);
    }

    public function test_a_session_is_never_identified_by_its_id(): void
    {
        $this->memberSession($this->staff, 'the-session-cookie-value', self::CHROME_MAC);

        $row = $this->sessions->forMember($this->staff)[0];

        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $row['handle']);
        $this->assertStringNotContainsString('the-session-cookie-value', json_encode($row));

        $page = $this->actingAs($this->owner, 'member')->get(route('admin.members.sessions'));
        $page->assertOk();
        $page->assertDontSee('the-session-cookie-value');
        $page->assertSee($row['handle']);
    }

    public function test_ending_one_session_leaves_the_others_and_clears_remember_me(): void
    {
        $this->memberSession($this->staff, 'staff-a', self::CHROME_MAC);
        $this->memberSession($this->staff, 'staff-b', self::SAFARI_IPHONE);

        $handle = $this->sessions->handleFor('staff-a');

        $this->actingAs($this->owner, 'member')
            ->from(route('admin.members.edit', $this->staff))
            ->post(route('admin.members.sessions.revoke', ['member' => $this->staff, 'session' => $handle]))
            ->assertRedirect(route('admin.members.edit', $this->staff))
            ->assertSessionHas('success');

        $this->assertFalse(DB::table('members_sessions')->where('id', 'staff-a')->exists());
        $this->assertTrue(DB::table('members_sessions')->where('id', 'staff-b')->exists());
        $this->assertNull($this->staff->fresh()->remember_token);

        $row = AuditLog::where('action', AuditLog::ACTION_SESSION_DESTROYED)->sole();
        $this->assertSame($this->owner->id, $row->actor_id);
        $this->assertSame($this->staff->id, $row->target_id);
        $this->assertSame(AuditLog::SEVERITY_WARNING, $row->severity);
        $this->assertSame($handle, $row->context['session']);
        $this->assertSame($this->device('Chrome', 'macOS'), $row->context['device']);
        $this->assertStringNotContainsString('staff-a', json_encode($row->context));
    }

    public function test_a_handle_of_another_member_s_session_ends_nothing(): void
    {
        $this->memberSession($this->owner, 'owner-a', self::CHROME_MAC);

        $this->actingAs($this->owner, 'member')
            ->from(route('admin.members.sessions'))
            ->post(route('admin.members.sessions.revoke', ['member' => $this->staff, 'session' => $this->sessions->handleFor('owner-a')]))
            ->assertSessionHas('error');

        $this->assertTrue(DB::table('members_sessions')->where('id', 'owner-a')->exists());
        $this->assertSame('remember-me', $this->staff->fresh()->remember_token);
        $this->assertSame(0, AuditLog::where('action', AuditLog::ACTION_SESSION_DESTROYED)->count());
    }

    public function test_a_lower_ranked_member_cannot_end_a_higher_ranked_member_s_session(): void
    {
        $this->memberSession($this->owner, 'owner-a', self::CHROME_MAC);

        $this->actingAs($this->staff, 'member')
            ->post(route('admin.members.sessions.revoke', ['member' => $this->owner, 'session' => $this->sessions->handleFor('owner-a')]))
            ->assertForbidden();

        $this->assertTrue(DB::table('members_sessions')->where('id', 'owner-a')->exists());
    }

    public function test_the_site_wide_list_shows_only_members_the_viewer_may_manage(): void
    {
        $this->memberSession($this->owner, 'owner-a', self::CHROME_MAC);
        $this->memberSession($this->staff, 'staff-a', self::SAFARI_IPHONE);

        $asOwner = $this->sessions->forManageableMembers($this->owner);
        $this->assertSame(2, $asOwner->total());

        $asStaff = $this->sessions->forManageableMembers($this->staff);
        $this->assertSame(1, $asStaff->total());
        $this->assertSame('Staff Member', $asStaff->items()[0]['display_name']);
    }

    public function test_the_edit_page_lists_the_member_s_sessions(): void
    {
        $this->memberSession($this->staff, 'staff-a', self::CHROME_MAC);

        $this->actingAs($this->owner, 'member')
            ->get(route('admin.members.edit', $this->staff))
            ->assertOk()
            ->assertSee(__('admin/members/sessions.section_title'))
            ->assertSee($this->device('Chrome', 'macOS'))
            ->assertSee($this->sessions->handleFor('staff-a'));
    }

    public function test_without_the_database_driver_the_pages_say_sessions_cannot_be_listed(): void
    {
        // An installed site always runs on the database handler, which
        // ApplySessionConfig sets per request; without it the configured
        // driver stays.
        $this->withoutMiddleware(ApplySessionConfig::class);
        config(['session.driver' => 'array']);

        $this->assertFalse($this->sessions->isAvailable());

        $this->actingAs($this->owner, 'member')
            ->get(route('admin.members.sessions'))
            ->assertOk()
            ->assertSee(__('admin/members/sessions.unavailable'));
    }

    public function test_the_handler_records_when_a_session_started_and_keeps_it(): void
    {
        $handler = app('session.guard-aware');
        $handler->setContainer($this->app);
        (new \ReflectionProperty($handler, 'currentGuard'))->setValue($handler, 'member');
        $insert = new \ReflectionMethod($handler, 'performInsert');
        $update = new \ReflectionMethod($handler, 'performUpdate');

        $insert->invoke($handler, 'fresh-session', ['payload' => '', 'last_activity' => time(), 'member_id' => $this->staff->id]);
        $started = DB::table('members_sessions')->where('id', 'fresh-session')->value('started_at');
        $this->assertNotNull($started);

        DB::table('members_sessions')->where('id', 'fresh-session')->update(['started_at' => 1000]);
        $update->invoke($handler, 'fresh-session', ['payload' => 'x', 'last_activity' => time()]);
        $this->assertSame(1000, (int) DB::table('members_sessions')->where('id', 'fresh-session')->value('started_at'));
    }

    private function device(string $browser, string $os): string
    {
        return __('admin/members/sessions.device', ['browser' => $browser, 'os' => $os]);
    }

    private function memberSession(Member $member, string $id, string $userAgent, ?int $lastActivity = null, ?int $startedAt = 0): void
    {
        DB::table('members_sessions')->insert([
            'id' => $id,
            'member_id' => $member->id,
            'ip_address' => '203.0.113.7',
            'user_agent' => $userAgent,
            'payload' => '',
            'last_activity' => $lastActivity ?? time(),
            'started_at' => $startedAt === 0 ? time() - 7200 : $startedAt,
        ]);
    }
}
