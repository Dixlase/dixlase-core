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

namespace Tests\Feature\Auth;

use App\Enums\MemberStatus;
use App\Models\Member;
use App\Services\Auth\SignedInMemberResolver;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * Reproduce the split session and pin what the resolver answers.
 *
 * The guard-aware driver keeps the admin login in `members_sessions` and
 * a front request's session in `sessions`, under one cookie, so
 * `auth('member')` is false on a front page while the browser is signed
 * in to the admin.
 *
 * The suite runs on the array session driver (`phpunit.xml`), so a test
 * session opens neither table -- which is what makes the split
 * reproducible here: the admin row is written by hand, and the guard is
 * left with nobody, exactly as a front request finds it.
 */
class SignedInMemberResolverTest extends TestCase
{
    use RefreshDatabase;

    private const SESSION_ID = 'HGcl2mV0gqWJy1hnFzWJ0kKRoqPmHq4bXXx0YmsE';

    protected function setUp(): void
    {
        parent::setUp();

        // Give the current request a session with a known id, so the row
        // written below and the row the resolver looks for share it the
        // way the two tables share the cookie.
        $session = $this->app['session']->driver();
        $session->setId(self::SESSION_ID);
        $session->start();
        request()->setLaravelSession($session);
    }

    public function test_it_finds_the_member_signed_in_to_the_admin(): void
    {
        $member = Member::factory()->create();
        $this->writeAdminSession($member->id);

        // The split itself: the guard sees nobody on this request.
        $this->assertFalse(auth('member')->check());

        $resolved = $this->resolver()->resolve();

        $this->assertInstanceOf(Member::class, $resolved);
        $this->assertSame($member->id, $resolved->id);
        $this->assertTrue($this->resolver()->check());
    }

    public function test_it_reads_the_session_on_the_session_store_connection_not_the_default(): void
    {
        // The member lives in the default database; the admin session row
        // lives only on a second connection that `session.connection` points
        // at -- the shape of a deployment that pins sessions to a dedicated
        // connection while the request's default connection is another
        // database. The resolver must look where the session handler writes,
        // not on the default, or it never sees the signed-in member.
        $member = Member::factory()->create();

        $this->pinSessionStoreToSecondConnection();
        $this->writeAdminSessionOn('session_store', $member->id);

        // The row is absent from the default connection on purpose: that is
        // exactly the state in which the old default-connection read failed.
        $this->assertSame(0, DB::table('members_sessions')->count());

        $resolved = $this->resolver()->resolve();

        $this->assertInstanceOf(Member::class, $resolved);
        $this->assertSame($member->id, $resolved->id);
    }

    public function test_it_answers_null_with_no_admin_session(): void
    {
        Member::factory()->create();

        $this->assertNull($this->resolver()->resolve());
        $this->assertFalse($this->resolver()->check());
    }

    public function test_it_ignores_an_admin_session_with_no_member(): void
    {
        $this->writeAdminSession(null);

        $this->assertNull($this->resolver()->resolve());
    }

    public function test_it_ignores_an_idle_admin_session(): void
    {
        $member = Member::factory()->create();
        $lifetime = (int) config('session.lifetime', 120);
        $this->writeAdminSession($member->id, time() - ($lifetime * 60) - 60);

        $this->assertNull($this->resolver()->resolve());
    }

    public function test_it_ignores_a_member_who_cannot_authenticate(): void
    {
        $member = Member::factory()->create(['status' => MemberStatus::Inactive]);
        $this->writeAdminSession($member->id);

        $this->assertNull($this->resolver()->resolve());
    }

    public function test_the_guard_is_authoritative_when_it_holds_a_member(): void
    {
        $member = Member::factory()->create();

        // No `members_sessions` row at all: an admin request, or a front
        // request a remember-me cookie signed back in.
        $this->actingAs($member, 'member');

        $this->assertSame($member->id, $this->resolver()->resolve()?->id);
    }

    public function test_the_admin_bar_renders_for_a_member_signed_in_to_the_admin(): void
    {
        $member = Member::factory()->create(['display_name' => 'Hanako Dixlase']);
        $this->writeAdminSession($member->id);

        $html = Blade::render('<x-ui-admin-bar />');

        $this->assertStringContainsString('Hanako Dixlase', $html);
    }

    public function test_the_admin_bar_renders_nothing_for_a_visitor(): void
    {
        Member::factory()->create(['display_name' => 'Hanako Dixlase']);

        $html = Blade::render('<x-ui-admin-bar />');

        $this->assertStringNotContainsString('Hanako Dixlase', $html);
    }

    public function test_the_admin_bar_offers_the_edit_link_a_front_page_sets(): void
    {
        $this->actingAs(Member::factory()->create(), 'member');

        View::startSection('admin_bar_edit_url', 'https://example.com/admin-custom/blog/1/edit?a=1&b=2');
        View::startSection('admin_bar_edit_label', 'Edit "post"');
        $html = Blade::render('<x-ui-admin-bar />');

        $this->assertStringContainsString('href="https://example.com/admin-custom/blog/1/edit?a=1&amp;b=2"', $html);
        $this->assertStringContainsString('aria-label="Edit &quot;post&quot;"', $html);
        // Not inside the admin layout, where the page is the edit screen.
        $this->assertStringNotContainsString('data-admin-bar-edit', Blade::render('<x-ui-admin-bar :isAdminLayout="true" />'));
    }

    public function test_the_admin_bar_edit_link_defaults_its_label_and_needs_a_url(): void
    {
        $this->actingAs(Member::factory()->create(), 'member');

        $this->assertStringNotContainsString('data-admin-bar-edit', Blade::render('<x-ui-admin-bar />'));

        View::startSection('admin_bar_edit_url', '/admin-custom/pages/3/edit');
        $html = Blade::render('<x-ui-admin-bar />');
        $this->assertStringContainsString('href="/admin-custom/pages/3/edit"', $html);
        $this->assertStringContainsString('aria-label="'.__('components/ui-admin-bar.edit').'"', $html);
    }

    public function test_the_admin_bar_edit_link_refuses_other_schemes(): void
    {
        $this->actingAs(Member::factory()->create(), 'member');

        foreach (['javascript:alert(1)', '//evil.example/edit', 'data:text/html,x'] as $url) {
            View::flushSections();
            View::startSection('admin_bar_edit_url', $url);

            $this->assertStringNotContainsString('data-admin-bar-edit', Blade::render('<x-ui-admin-bar />'), $url);
        }
    }

    public function test_a_visitor_never_sees_the_edit_link(): void
    {
        View::startSection('admin_bar_edit_url', '/admin-custom/blog/1/edit');

        $this->assertStringNotContainsString('data-admin-bar-edit', Blade::render('<x-ui-admin-bar />'));
    }

    private function resolver(): SignedInMemberResolver
    {
        // A fresh instance each time: the service caches its answer for the
        // request, and these tests change the row between calls.
        return new SignedInMemberResolver();
    }

    /**
     * Write the member guard's session row under the shared session id.
     */
    private function writeAdminSession(?int $memberId, ?int $lastActivity = null): void
    {
        $this->writeAdminSessionOn(DB::getDefaultConnection(), $memberId, $lastActivity);
    }

    /**
     * Write the member guard's session row on a named connection.
     */
    private function writeAdminSessionOn(string $connection, ?int $memberId, ?int $lastActivity = null): void
    {
        DB::connection($connection)->table('members_sessions')->insert([
            'id' => self::SESSION_ID,
            'member_id' => $memberId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'payload' => base64_encode(serialize([])),
            'last_activity' => $lastActivity ?? time(),
        ]);
    }

    /**
     * Point `session.connection` at a second, in-memory connection that
     * carries its own `members_sessions`, and rebuild the guard-aware
     * handler the way SessionServiceProvider builds it from config, so the
     * session store and the default connection are different databases.
     */
    private function pinSessionStoreToSecondConnection(): void
    {
        config()->set('database.connections.session_store', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);

        Schema::connection('session_store')->create('members_sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->unsignedBigInteger('member_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity');
        });

        config()->set('session.connection', 'session_store');

        // Drop the cached singleton so the next resolution rebuilds it from
        // the connection just configured.
        $this->app->forgetInstance('session.guard-aware');
    }
}
