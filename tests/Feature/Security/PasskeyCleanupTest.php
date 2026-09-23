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

namespace Tests\Feature\Security;

use App\Enums\MemberRole;
use App\Models\Member;
use App\Models\Passkey;
use App\Services\DatabaseCleanupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The passkey cleanup rule must age passkeys by last use, not registration.
 *
 * Keyed on created_at, "older than 365 days" deleted a passkey registered a
 * year ago even if it was used to sign in yesterday -- locking its owner out
 * of passkey login. That went unnoticed only because the rule's extra
 * condition referenced a last_used_at column the old table lacked, so the
 * whole cleanup failed with an SQL error.
 */
class PasskeyCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_cleanup_keeps_old_passkeys_that_are_still_in_use_and_drops_inactive_ones(): void
    {
        $member = Member::factory()->create(['role' => MemberRole::ADMIN]);

        $inUse = $this->passkey($member, 'in-use', created: now()->subYears(2), lastUsed: now()->subDay());
        $neverUsed = $this->passkey($member, 'never-used', created: now()->subYears(2), lastUsed: null);
        $inactive = $this->passkey($member, 'inactive', created: now()->subYears(3), lastUsed: now()->subYears(2));

        $result = app(DatabaseCleanupService::class)->cleanup('passkeys', 365, true);

        $this->assertTrue($result['success'], $result['message'] ?? '');
        $this->assertSame(1, $result['count']);
        $this->assertModelExists($inUse);
        $this->assertModelExists($neverUsed);
        $this->assertModelMissing($inactive);
    }

    private function passkey(Member $member, string $name, \DateTimeInterface $created, ?\DateTimeInterface $lastUsed): Passkey
    {
        $passkey = $member->passkeys()->create([
            'name' => $name,
            'credential_id' => bin2hex(random_bytes(16)),
            'credential' => ['counter' => 0],
        ]);

        $passkey->forceFill(['created_at' => $created, 'last_used_at' => $lastUsed])->save();

        return $passkey;
    }
}
