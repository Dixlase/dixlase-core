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

namespace Tests\Unit\Services;

use App\Models\Member;
use App\Models\MemberLoginAttempt;
use App\Services\LoginBehaviorService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class LoginBehaviorServiceTest extends TestCase
{
    use RefreshDatabase;

    private LoginBehaviorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LoginBehaviorService();
    }

    public function test_record_login_attempt_populates_behavior_columns(): void
    {
        $request = Request::create('/login', 'POST');
        $request->headers->set('User-Agent', 'Mozilla/5.0 Test Browser');
        $request->headers->set('Accept-Language', 'en-US,en;q=0.9');

        $attempt = $this->service->recordLoginAttempt('test@example.com', $request, false);

        $this->assertNotNull($attempt->login_hour);
        $this->assertNotNull($attempt->login_day_of_week);
        $this->assertNotNull($attempt->device_fingerprint);
        $this->assertFalse($attempt->successful);
    }

    public function test_member_id_is_resolved_from_identifier(): void
    {
        $member = Member::factory()->create(['email' => 'resolved@example.com']);

        $request = Request::create('/login', 'POST');
        $request->headers->set('User-Agent', 'Mozilla/5.0');
        $request->headers->set('Accept-Language', 'en-US');

        $attempt = $this->service->recordLoginAttempt('resolved@example.com', $request, true);

        $this->assertEquals($member->id, $attempt->member_id);
    }

    public function test_member_id_is_null_for_unknown_identifier(): void
    {
        $request = Request::create('/login', 'POST');
        $request->headers->set('User-Agent', 'Mozilla/5.0');
        $request->headers->set('Accept-Language', 'en-US');

        $attempt = $this->service->recordLoginAttempt('unknown@example.com', $request, false);

        $this->assertNull($attempt->member_id);
    }

    public function test_bot_detection_flags_known_bot_user_agent(): void
    {
        $request = Request::create('/login', 'POST');
        $request->headers->set('User-Agent', 'Mozilla/5.0 (compatible; Googlebot/2.1)');
        $request->headers->set('Accept-Language', 'en-US');

        $result = $this->service->detectBotBehavior($request, 'test@example.com');

        $this->assertTrue($result['is_bot_suspected']);
        $this->assertContains('known_bot_ua', $result['signals']);
    }

    public function test_bot_detection_flags_missing_accept_language(): void
    {
        $request = Request::create('/login', 'POST');
        $request->headers->set('User-Agent', 'Mozilla/5.0');
        $request->headers->remove('Accept-Language');

        $result = $this->service->detectBotBehavior($request, 'test@example.com');

        $this->assertContains('missing_accept_language', $result['signals']);
    }

    public function test_bot_detection_flags_rapid_fire_attempts(): void
    {
        $ip = '10.0.0.99';

        // Create 6 recent attempts from same IP
        for ($i = 0; $i < 6; $i++) {
            MemberLoginAttempt::create([
                'identifier' => "user{$i}@example.com",
                'ip_address' => $ip,
                'successful' => false,
                'attempted_at' => Carbon::now()->subSeconds(30),
            ]);
        }

        $request = Request::create('/login', 'POST', [], [], [], ['REMOTE_ADDR' => $ip]);
        $request->headers->set('User-Agent', 'Mozilla/5.0');
        $request->headers->set('Accept-Language', 'en-US');

        $result = $this->service->detectBotBehavior($request, 'new@example.com');

        $this->assertTrue($result['is_bot_suspected']);
        $this->assertContains('rapid_fire_attempts', $result['signals']);
    }

    public function test_bot_detection_flags_credential_stuffing_pattern(): void
    {
        $ip = '10.0.0.88';

        // Create attempts with 4+ different identifiers from same IP
        for ($i = 0; $i < 4; $i++) {
            MemberLoginAttempt::create([
                'identifier' => "victim{$i}@example.com",
                'ip_address' => $ip,
                'successful' => false,
                'attempted_at' => Carbon::now()->subMinutes(2),
            ]);
        }

        $request = Request::create('/login', 'POST', [], [], [], ['REMOTE_ADDR' => $ip]);
        $request->headers->set('User-Agent', 'Mozilla/5.0');
        $request->headers->set('Accept-Language', 'en-US');

        $result = $this->service->detectBotBehavior($request, 'another@example.com');

        $this->assertTrue($result['is_bot_suspected']);
        $this->assertContains('credential_stuffing_pattern', $result['signals']);
    }

    public function test_normal_login_is_not_flagged_as_bot(): void
    {
        $request = Request::create('/login', 'POST');
        $request->headers->set('User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)');
        $request->headers->set('Accept-Language', 'en-US,en;q=0.9,ja;q=0.8');

        $result = $this->service->detectBotBehavior($request, 'normal@example.com');

        $this->assertFalse($result['is_bot_suspected']);
        $this->assertEmpty($result['signals']);
    }

    public function test_bot_suspected_flag_is_stored_in_attempt(): void
    {
        $request = Request::create('/login', 'POST');
        $request->headers->set('User-Agent', 'headless-chrome-bot');
        $request->headers->set('Accept-Language', 'en-US');

        $attempt = $this->service->recordLoginAttempt('test@example.com', $request, false);

        $this->assertTrue($attempt->is_bot_suspected);
        $this->assertNotNull($attempt->context);
        $this->assertArrayHasKey('bot_signals', $attempt->context);
    }

    public function test_scope_bot_suspected(): void
    {
        MemberLoginAttempt::create([
            'identifier' => 'normal@example.com',
            'ip_address' => '1.1.1.1',
            'successful' => true,
            'attempted_at' => now(),
            'is_bot_suspected' => false,
        ]);
        MemberLoginAttempt::create([
            'identifier' => 'bot@example.com',
            'ip_address' => '2.2.2.2',
            'successful' => false,
            'attempted_at' => now(),
            'is_bot_suspected' => true,
        ]);

        $bots = MemberLoginAttempt::botSuspected()->get();

        $this->assertCount(1, $bots);
        $this->assertEquals('bot@example.com', $bots->first()->identifier);
    }

    public function test_scope_for_member(): void
    {
        $member = Member::factory()->create();

        MemberLoginAttempt::create([
            'identifier' => $member->email,
            'ip_address' => '1.1.1.1',
            'successful' => true,
            'attempted_at' => now(),
            'member_id' => $member->id,
        ]);
        MemberLoginAttempt::create([
            'identifier' => 'other@example.com',
            'ip_address' => '2.2.2.2',
            'successful' => false,
            'attempted_at' => now(),
            'member_id' => null,
        ]);

        $memberAttempts = MemberLoginAttempt::forMember($member->id)->get();

        $this->assertCount(1, $memberAttempts);
        $this->assertEquals($member->email, $memberAttempts->first()->identifier);
    }
}
