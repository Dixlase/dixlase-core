<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
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

namespace Tests\Feature\Http\Middleware;

use App\Enums\MemberRole;
use App\Http\Middleware\FrontIpFilter;
use App\Models\Member;
use App\Models\SecuritySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Core #588: the administrator bypass read `$member->is_admin`, which Member
 * does not have, so it never fired. The bypass now means: ADMIN or above,
 * signed in to the admin panel, is let past the front allow list. The block
 * list still applies to everyone.
 */
class FrontIpFilterTest extends TestCase
{
    use RefreshDatabase;

    private const SESSION_ID = 'Fq0yWm4dC7nLr2Kx9TbVhP5sJz1QeA8gUo3iNcMw';

    private const ALLOWED_IP = '192.0.2.10';

    private const OUTSIDE_IP = '198.51.100.7';

    protected function setUp(): void
    {
        parent::setUp();

        // The filter is skipped on a local box with debug on.
        config(['app.debug' => false]);
    }

    private function allowOnly(string $ip): void
    {
        SecuritySetting::set('enable_allowed_front_ips', '1');
        SecuritySetting::set('allowed_front_ips', $ip);
    }

    private function block(string $ip): void
    {
        SecuritySetting::set('enable_blocked_front_ips', '1');
        SecuritySetting::set('blocked_front_ips', $ip);
    }

    private function member(MemberRole $role): Member
    {
        return Member::factory()->create(['role' => $role]);
    }

    /**
     * Run the middleware for a request from $ip and return the status it ends with.
     */
    private function statusFor(string $ip): int
    {
        $request = Request::create('/', 'GET', server: ['REMOTE_ADDR' => $ip]);
        $request->setLaravelSession($this->app['session']->driver());
        $this->app->instance('request', $request);

        try {
            return (new FrontIpFilter())
                ->handle($request, fn () => new Response('ok'))
                ->getStatusCode();
        } catch (HttpException $e) {
            return $e->getStatusCode();
        }
    }

    public function test_a_visitor_off_the_allow_list_is_refused(): void
    {
        $this->allowOnly(self::ALLOWED_IP);

        $this->assertSame(403, $this->statusFor(self::OUTSIDE_IP));
        $this->assertSame(200, $this->statusFor(self::ALLOWED_IP));
    }

    public function test_an_admin_and_a_super_admin_are_let_past_the_allow_list(): void
    {
        $this->allowOnly(self::ALLOWED_IP);

        $this->actingAs($this->member(MemberRole::ADMIN), 'member');
        $this->assertSame(200, $this->statusFor(self::OUTSIDE_IP));

        $this->actingAs($this->member(MemberRole::SUPER_ADMIN), 'member');
        $this->assertSame(200, $this->statusFor(self::OUTSIDE_IP));
    }

    public function test_a_role_below_admin_is_refused_like_a_visitor(): void
    {
        $this->allowOnly(self::ALLOWED_IP);

        $this->actingAs($this->member(MemberRole::EDITOR), 'member');

        $this->assertSame(403, $this->statusFor(self::OUTSIDE_IP));
    }

    public function test_an_admin_signed_in_only_to_the_admin_panel_is_recognised(): void
    {
        $this->allowOnly(self::ALLOWED_IP);

        // A front request sees the admin login only through the shared
        // session id; the guard on this request holds nobody.
        $session = $this->app['session']->driver();
        $session->setId(self::SESSION_ID);
        $session->start();

        DB::table('members_sessions')->insert([
            'id' => self::SESSION_ID,
            'member_id' => $this->member(MemberRole::ADMIN)->id,
            'ip_address' => self::OUTSIDE_IP,
            'user_agent' => 'phpunit',
            'payload' => base64_encode(serialize([])),
            'last_activity' => time(),
        ]);

        $this->assertFalse(auth('member')->check());
        $this->assertSame(200, $this->statusFor(self::OUTSIDE_IP));
    }

    public function test_the_block_list_applies_to_an_admin_too(): void
    {
        $this->block(self::OUTSIDE_IP);

        $this->actingAs($this->member(MemberRole::SUPER_ADMIN), 'member');

        $this->assertSame(403, $this->statusFor(self::OUTSIDE_IP));
    }
}
