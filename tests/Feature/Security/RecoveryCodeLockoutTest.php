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
use App\Models\SecuritySetting;
use App\Services\TwoFa\TwoFaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * verifyRecoveryCode() checks the 2FA lockout before validating, and its
 * comment says the intent is to stop recovery codes becoming an un-throttled
 * brute-force channel. But the validation went through
 * verifyRecoveryCodeValue(), which called TwoFaRecoveryCodeService::validate()
 * directly and therefore never reached TwoFaAttemptService::recordAttempt().
 *
 * Nothing counted the failures, so the counter the lockout reads never
 * advanced and the lockout could not fire regardless of how many attempts were
 * made. The email and passkey paths already went through TwoFaService, which
 * records on both success and failure; this was the one method that did not --
 * and it guards the fallback credential.
 */
class RecoveryCodeLockoutTest extends TestCase
{
    use RefreshDatabase;

    private function service(): TwoFaService
    {
        return app(TwoFaService::class, [
            'settingModelClass' => SecuritySetting::class,
            'context' => 'admin',
        ]);
    }

    private function member(): Member
    {
        return Member::factory()->create(['role' => MemberRole::ADMIN]);
    }

    /**
     * Invoke the trait method the login flow actually uses.
     *
     * Going through a real class that uses the trait, rather than calling
     * TwoFaService directly: the service always recorded attempts, so a test
     * that called it would pass with the defect still in place. The bug was in
     * which service the trait reached for, and only this route sees that.
     */
    private function verifyThroughTrait(Member $member, string $code): bool
    {
        $controller = app(\App\Http\Controllers\Admin\AdminTwoFaController::class);

        $method = new \ReflectionMethod($controller, 'verifyRecoveryCodeValue');
        $method->setAccessible(true);

        return $method->invoke($controller, $member, $code);
    }

    /**
     * The behaviour that was missing: a wrong code has to leave a trace.
     */
    public function test_a_failed_recovery_code_is_recorded(): void
    {
        $member = $this->member();

        $before = $member->twoFaAttempts()->count();

        $this->assertFalse(
            $this->verifyThroughTrait($member, 'definitely-not-a-valid-code'),
            'An invalid recovery code must not validate.'
        );

        $this->assertGreaterThan(
            $before,
            $member->twoFaAttempts()->count(),
            'A failed recovery-code attempt must be recorded, otherwise the lockout counter never advances.'
        );
    }

    /**
     * Repeated failures must eventually lock the account out. Without the
     * recording above this loop could run indefinitely.
     */
    public function test_repeated_failures_eventually_lock_out(): void
    {
        $member = $this->member();

        for ($i = 0; $i < 10; $i++) {
            $this->verifyThroughTrait($member, "wrong-code-{$i}");
        }

        $this->assertTrue(
            $this->service()->checkLockout($member)['locked_out'],
            'Recovery-code brute force must trip the same 2FA lockout as the other methods.'
        );
    }

    /**
     * The trait is where the defect lived: it reached past TwoFaService to the
     * lower-level service. Pinning the wiring, since the recording only
     * happens on the route through TwoFaService.
     */
    public function test_the_trait_routes_through_the_recording_service(): void
    {
        $source = file_get_contents(base_path('app/Traits/TwoFa/TwoFaAuthenticationTrait.php'));

        $this->assertStringContainsString(
            'validateRecoveryCode($user, $code)',
            $source,
            'verifyRecoveryCodeValue() must go through TwoFaService::validateRecoveryCode(), which records the attempt.'
        );

        $this->assertStringNotContainsString(
            '$recoveryCodeService->validate($user, $code)',
            $source,
            'Calling TwoFaRecoveryCodeService directly bypasses recordAttempt() and disables the lockout.'
        );
    }

    /**
     * The sibling methods are pinned too. The defect was one path diverging
     * from the others, so the value is in them agreeing.
     */
    public function test_every_second_factor_records_its_attempts(): void
    {
        $source = file_get_contents(base_path('app/Services/TwoFa/TwoFaService.php'));

        foreach (["'email'", "'passkey'", "'recovery_code'"] as $method) {
            $this->assertStringContainsString(
                "recordAttempt(\$user, {$method}",
                $source,
                "Attempts for {$method} must be recorded so the shared lockout can see them.",
            );
        }
    }
}
