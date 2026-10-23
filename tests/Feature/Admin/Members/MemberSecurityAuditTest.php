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
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\MemberLoginAttempt;
use App\Models\MemberTwoFaAttempt;
use App\Models\Passkey;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * Taking a passkey, the recovery codes or the sessions away from a member --
 * including through a verification email, which ends every session -- writes
 * an audit row, on the member's own profile and on another member's
 * security page alike.
 */
class MemberSecurityAuditTest extends TestCase
{
    use RefreshDatabase;

    private Member $owner;

    private Member $staff;

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
            'email' => 'staff@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => MemberRole::ADMIN,
            'status' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        unset($_ENV['INSTALLED']);

        parent::tearDown();
    }

    public function test_force_logout_of_another_member_is_recorded(): void
    {
        $this->memberSession($this->staff, 'staff-a');
        $this->memberSession($this->staff, 'staff-b');

        $this->actingAs($this->owner, 'member')
            ->post(route('admin.members.force-logout', $this->staff))
            ->assertRedirect();

        $row = $this->onlyRow(AuditLog::ACTION_FORCED_LOGOUT);
        $this->assertRecordedBy($row, $this->owner, $this->staff);
        $this->assertSame(AuditLog::CATEGORY_SESSION, $row->category);
        $this->assertSame(AuditLog::SEVERITY_WARNING, $row->severity);
        $this->assertSame(2, $row->context['sessions']);
        $this->assertTrue($row->context['by_other_member']);
    }

    public function test_releasing_a_lockout_is_recorded(): void
    {
        MemberTwoFaAttempt::create([
            'member_id' => $this->staff->id,
            'attempt_type' => 'email',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'successful' => false,
            'created_at' => now(),
        ]);
        MemberLoginAttempt::create([
            'identifier' => $this->staff->email,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'successful' => false,
            'attempted_at' => now(),
        ]);

        $this->actingAs($this->owner, 'member')
            ->post(route('admin.members.unlock-lockout', $this->staff))
            ->assertRedirect();

        $row = $this->onlyRow(AuditLog::ACTION_LOCKOUT_RELEASED);
        $this->assertRecordedBy($row, $this->owner, $this->staff);
        $this->assertSame(1, $row->context['two_fa_attempts']);
        $this->assertSame(1, $row->context['login_attempts']);
    }

    public function test_revoking_another_members_passkeys_is_recorded(): void
    {
        $first = $this->passkey($this->staff, 'phone');
        $this->passkey($this->staff, 'laptop');
        $this->passkey($this->staff, 'key');

        $this->actingAs($this->owner, 'member')
            ->deleteJson(route('admin.members.passkey.revoke', ['member' => $this->staff->id, 'credentialId' => $first->id]))
            ->assertOk();

        $row = $this->onlyRow(AuditLog::ACTION_PASSKEY_REVOKED);
        $this->assertRecordedBy($row, $this->owner, $this->staff);
        $this->assertSame(AuditLog::SEVERITY_WARNING, $row->severity);
        $this->assertSame((string) $first->id, (string) $row->context['passkey_id']);

        $this->actingAs($this->owner, 'member')
            ->deleteJson(route('admin.members.passkey.revoke-all', ['member' => $this->staff->id]))
            ->assertOk();

        $rows = AuditLog::where('action', AuditLog::ACTION_PASSKEY_REVOKED)->orderBy('id')->get();
        $this->assertCount(2, $rows);
        $this->assertSame('all', $rows[1]->context['scope']);
        $this->assertSame(2, $rows[1]->context['count']);
    }

    public function test_revoking_another_members_recovery_codes_is_recorded(): void
    {
        $this->staff->twoFaRecoveryCodes()->create(['code' => Hash::make('1'), 'disabled' => false]);
        $this->staff->twoFaRecoveryCodes()->create(['code' => Hash::make('2'), 'disabled' => false]);

        $this->actingAs($this->owner, 'member')
            ->deleteJson(route('admin.members.recovery-codes.revoke', $this->staff))
            ->assertOk();

        $row = $this->onlyRow(AuditLog::ACTION_RECOVERY_CODES_REVOKED);
        $this->assertRecordedBy($row, $this->owner, $this->staff);
        $this->assertSame(AuditLog::CATEGORY_ACCOUNT, $row->category);
        $this->assertSame(2, $row->context['count']);
    }

    public function test_force_logout_of_everyone_is_one_row(): void
    {
        $this->memberSession($this->staff, 'staff-a');
        $this->memberSession($this->owner, 'owner-a');

        $this->actingAs($this->owner, 'member')
            ->post(route('admin.members.force-logout-all'))
            ->assertRedirect();

        $row = $this->onlyRow(AuditLog::ACTION_FORCED_LOGOUT);
        $this->assertSame(Member::class, $row->actor_type);
        $this->assertSame($this->owner->id, $row->actor_id);
        $this->assertNull($row->target_id);
        $this->assertSame('all', $row->context['scope']);
        $this->assertSame(1, $row->context['sessions']);
        $this->assertSame(1, $row->context['members']);
    }

    public function test_sending_another_member_a_verification_email_is_recorded(): void
    {
        $this->markMailServerTested();
        $this->memberSession($this->staff, 'staff-a');
        $this->memberSession($this->staff, 'staff-b');

        $this->actingAs($this->owner, 'member')
            ->postJson(route('admin.members.send-verification-email', $this->staff))
            ->assertOk()
            ->assertJson(['success' => true]);

        $row = $this->onlyRow(AuditLog::ACTION_EMAIL_VERIFICATION_RESET);
        $this->assertRecordedBy($row, $this->owner, $this->staff);
        $this->assertSame(AuditLog::CATEGORY_ACCOUNT, $row->category);
        $this->assertSame(AuditLog::SEVERITY_WARNING, $row->severity);
        $this->assertSame(2, $row->context['sessions']);
        $this->assertTrue($row->context['by_other_member']);
        $this->assertSame(0, DB::table('members_sessions')->where('member_id', $this->staff->id)->count());
    }

    public function test_sending_yourself_a_verification_email_is_recorded(): void
    {
        $this->markMailServerTested();

        $this->actingAs($this->staff, 'member')
            ->postJson(route('admin.members.send-verification-email', $this->staff))
            ->assertOk();

        $row = $this->onlyRow(AuditLog::ACTION_EMAIL_VERIFICATION_RESET);
        $this->assertRecordedBy($row, $this->staff, $this->staff);
        $this->assertFalse($row->context['by_other_member']);
    }

    public function test_a_refused_verification_email_writes_no_row(): void
    {
        // Mail server not tested: the action stops before touching the account.
        $this->memberSession($this->staff, 'staff-a');

        $this->actingAs($this->owner, 'member')
            ->postJson(route('admin.members.send-verification-email', $this->staff))
            ->assertStatus(400);

        $this->assertSame(0, AuditLog::where('action', AuditLog::ACTION_EMAIL_VERIFICATION_RESET)->count());
        $this->assertSame(1, DB::table('members_sessions')->where('member_id', $this->staff->id)->count());
    }

    public function test_revoking_own_passkeys_is_recorded_once(): void
    {
        $this->passkey($this->staff, 'phone');
        $this->passkey($this->staff, 'laptop');

        $this->actingAs($this->staff, 'member')
            ->deleteJson(route('admin.profile.passkey.revoke-all'))
            ->assertOk();

        $row = $this->onlyRow(AuditLog::ACTION_PASSKEY_REVOKED);
        $this->assertRecordedBy($row, $this->staff, $this->staff);
        $this->assertSame(AuditLog::SEVERITY_NOTICE, $row->severity);
        $this->assertFalse($row->context['by_other_member']);
        $this->assertSame(2, $row->context['count']);
    }

    public function test_issuing_own_recovery_codes_is_recorded_without_the_codes(): void
    {
        $response = $this->actingAs($this->staff, 'member')
            ->postJson(route('admin.profile.recovery-codes.generate'))
            ->assertOk();

        $row = $this->onlyRow(AuditLog::ACTION_RECOVERY_CODES_GENERATED);
        $this->assertRecordedBy($row, $this->staff, $this->staff);
        $this->assertSame(0, $row->context['replaced']);

        $stored = json_encode($row->getAttributes());
        foreach ($response->json('codes') as $code) {
            $this->assertStringNotContainsString($code, $stored);
        }
    }

    private function onlyRow(string $action): AuditLog
    {
        $rows = AuditLog::where('action', $action)->get();
        $this->assertCount(1, $rows, "expected one {$action} row");

        return $rows->first();
    }

    private function assertRecordedBy(AuditLog $row, Member $actor, Member $target): void
    {
        $this->assertSame(Member::class, $row->actor_type);
        $this->assertSame($actor->id, $row->actor_id);
        $this->assertSame(Member::class, $row->target_type);
        $this->assertSame($target->id, $row->target_id);
    }

    private function markMailServerTested(): void
    {
        Notification::fake();

        SiteSetting::setValue('mail_connection_tested', true);
        SiteSetting::setValue('mail_send_tested', true);
        SiteSetting::setValue('mail_receive_tested', true);
    }

    private function memberSession(Member $member, string $id): void
    {
        DB::table('members_sessions')->insert([
            'id' => $id,
            'member_id' => $member->id,
            'payload' => '',
            'last_activity' => time(),
        ]);
    }

    private function passkey(Member $member, string $name): Passkey
    {
        return $member->passkeys()->create([
            'name' => $name,
            'credential_id' => bin2hex(random_bytes(16)),
            'credential' => ['counter' => 0],
        ]);
    }
}
