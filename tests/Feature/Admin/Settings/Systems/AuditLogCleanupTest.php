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

namespace Tests\Feature\Admin\Settings\Systems;

use App\Enums\MemberRole;
use App\Http\Middleware\CheckInstallationReady;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The audit-log cleanup route was gated on `settings.systems.logs`, a menu key
 * with no definition of its own that resolves through its ADMIN-level children,
 * so an ADMIN could truncate the whole audit log (`days=0`), including the
 * record of what they had just done. A negative value deleted everything too.
 */
class AuditLogCleanupTest extends TestCase
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

        foreach ([400, 200, 5] as $daysAgo) {
            DB::table('audit_logs')->insert([
                'occurred_at' => now()->subDays($daysAgo),
                'category' => 'test',
                'action' => "fixture.{$daysAgo}",
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
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

    private function fixtureRows(): int
    {
        return DB::table('audit_logs')->where('category', 'test')->count();
    }

    public function test_an_admin_cannot_clean_up_the_audit_log(): void
    {
        $this->actingAs($this->member(MemberRole::ADMIN), 'member')
            ->post(route('admin.settings.systems.logs.audit.cleanup'), ['days' => 0])
            ->assertForbidden();

        $this->assertSame(3, $this->fixtureRows());
    }

    /**
     * @return array<string, array{0: int}>
     */
    public static function wipeEverythingValues(): array
    {
        return ['zero' => [0], 'negative' => [-30]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('wipeEverythingValues')]
    public function test_a_super_admin_cannot_wipe_the_whole_log(int $days): void
    {
        $this->actingAs($this->member(MemberRole::SUPER_ADMIN), 'member')
            ->post(route('admin.settings.systems.logs.audit.cleanup'), ['days' => $days])
            ->assertSessionHasErrors('days');

        $this->assertSame(3, $this->fixtureRows());
    }

    public function test_a_super_admin_removes_only_older_entries_and_the_cleanup_is_recorded(): void
    {
        $this->actingAs($this->member(MemberRole::SUPER_ADMIN), 'member')
            ->post(route('admin.settings.systems.logs.audit.cleanup'), ['days' => 100])
            ->assertRedirect();

        $this->assertSame(['fixture.5'], DB::table('audit_logs')->where('category', 'test')->pluck('action')->all());
        $this->assertTrue(DB::table('audit_logs')->where('action', 'audit_log.cleanup')->exists());
    }
}
