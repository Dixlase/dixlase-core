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

namespace Tests\Feature\Admin\Members;

use App\Actions\Member\UpdateMemberAction;
use App\Actors\MemberActor;
use App\Enums\MemberRole;
use App\Models\Member;
use App\Services\Member\MemberHierarchyGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The member form's verification control, and the one case it may not do.
 *
 * The form sends `email_verified` ('0' / '1'); the column is
 * `email_verified_at`. CreateMemberAction translated one into the other and
 * the update path did not, so the control was discarded in silence while
 * the form's help text said it reset the verification status (#928).
 *
 * Making it work hands an operator a way to clear their own verification,
 * which sends the account to the verification notice on its next request —
 * on a site whose mail is not working, for good. So the guard refuses that
 * one direction for your own account and for the initial administrator,
 * next to the role and status rules it already enforces on both.
 */
class MemberVerificationControlTest extends TestCase
{
    use RefreshDatabase;

    private function actor(Member $member): MemberActor
    {
        return new MemberActor($member);
    }

    private function superAdmin(array $attributes = []): Member
    {
        return Member::factory()->create(array_merge([
            'role' => MemberRole::SUPER_ADMIN,
            'email_verified_at' => now(),
        ], $attributes));
    }

    #[Test]
    public function choosing_unverified_clears_another_member_s_verification(): void
    {
        $actor = $this->superAdmin();
        $target = Member::factory()->create([
            'role' => MemberRole::ADMIN,
            'email_verified_at' => now(),
        ]);

        (new UpdateMemberAction($target))->execute($this->actor($actor), ['email_verified' => '0']);

        $this->assertNull($target->fresh()->email_verified_at);
    }

    #[Test]
    public function choosing_verified_sets_it(): void
    {
        $actor = $this->superAdmin();
        $target = Member::factory()->create([
            'role' => MemberRole::ADMIN,
            'email_verified_at' => null,
        ]);

        (new UpdateMemberAction($target))->execute($this->actor($actor), ['email_verified' => '1']);

        $this->assertNotNull($target->fresh()->email_verified_at);
    }

    #[Test]
    public function marking_an_already_verified_member_verified_keeps_the_original_time(): void
    {
        $actor = $this->superAdmin();
        $verifiedAt = now()->subDays(30);
        $target = Member::factory()->create([
            'role' => MemberRole::ADMIN,
            'email_verified_at' => $verifiedAt,
        ]);

        (new UpdateMemberAction($target))->execute($this->actor($actor), ['email_verified' => '1']);

        // Member does not cast email_verified_at, so it reads back as the
        // raw column value.
        $this->assertSame(
            $verifiedAt->toDateTimeString(),
            (string) $target->fresh()->email_verified_at,
            'Re-affirming a verification is not a new verification.'
        );
    }

    #[Test]
    public function an_update_without_the_field_leaves_the_state_alone(): void
    {
        $actor = $this->superAdmin();
        $target = Member::factory()->create([
            'role' => MemberRole::ADMIN,
            'email_verified_at' => now(),
        ]);

        (new UpdateMemberAction($target))->execute($this->actor($actor), ['display_name' => 'Renamed']);

        $fresh = $target->fresh();
        $this->assertSame('Renamed', $fresh->display_name);
        $this->assertNotNull($fresh->email_verified_at, 'Every other path that updates a member must be unaffected.');
    }

    #[Test]
    public function you_cannot_clear_your_own_verification(): void
    {
        $actor = $this->superAdmin();

        try {
            (new UpdateMemberAction($actor))->execute($this->actor($actor), ['email_verified' => '0']);
            $this->fail('Clearing your own verification must be refused.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('email_verified', $e->errors());
        }

        $this->assertNotNull($actor->fresh()->email_verified_at);
    }

    #[Test]
    public function you_may_still_mark_your_own_account_verified(): void
    {
        // The refusal is about taking access away, so the other direction
        // has to stay open.
        $actor = $this->superAdmin(['email_verified_at' => null]);

        (new UpdateMemberAction($actor))->execute($this->actor($actor), ['email_verified' => '1']);

        $this->assertNotNull($actor->fresh()->email_verified_at);
    }

    #[Test]
    public function the_initial_administrator_s_verification_cannot_be_cleared(): void
    {
        // Created first, so the id the guard keys on is free.
        $initial = Member::factory()->create([
            'id' => MemberHierarchyGuard::INITIAL_ADMIN_ID,
            'role' => MemberRole::SUPER_ADMIN,
            'email_verified_at' => now(),
        ]);
        $actor = $this->superAdmin();

        try {
            (new UpdateMemberAction($initial))->execute($this->actor($actor), ['email_verified' => '0']);
            $this->fail('Clearing the initial administrator\'s verification must be refused.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('email_verified', $e->errors());
        }

        $this->assertNotNull($initial->fresh()->email_verified_at);
    }

    #[Test]
    public function clearing_a_verification_that_is_already_absent_is_not_refused(): void
    {
        // Nothing is taken away, so there is nothing to refuse — and a
        // save of the form on an unverified own account must not fail.
        $actor = $this->superAdmin(['email_verified_at' => null]);

        (new UpdateMemberAction($actor))->execute($this->actor($actor), ['email_verified' => '0', 'display_name' => 'Renamed']);

        $fresh = $actor->fresh();
        $this->assertNull($fresh->email_verified_at);
        $this->assertSame('Renamed', $fresh->display_name);
    }

    #[Test]
    public function both_messages_exist_in_both_locales(): void
    {
        foreach (['en', 'ja'] as $locale) {
            $messages = require base_path("lang/{$locale}/admin/members/validation.php");

            foreach (['initial_admin_verification_locked', 'own_verification_locked'] as $key) {
                $this->assertArrayHasKey($key, $messages, "lang/{$locale} is missing {$key}.");
                $this->assertNotSame('', trim((string) $messages[$key]));
            }
        }
    }
}
