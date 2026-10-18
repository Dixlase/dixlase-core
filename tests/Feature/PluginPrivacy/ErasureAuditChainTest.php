<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

namespace Tests\Feature\PluginPrivacy;

use App\Enums\PluginPrivacy\DeletionMode;
use App\Facades\Audit;
use App\Models\AuditLog;
use App\Models\Member;
use App\Services\AuditLogIntegrityService;
use App\Services\Privacy\ErasureNotRecordedException;
use App\Services\Privacy\Providers\CoreMemberPrivacyProvider;
use App\Services\Privacy\UserPrivacyEraser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Core #478: erasing a member's personal data rewrote `ip_address`, which
 * the per-row hash covers, so every chained row of that member then failed
 * verification and the erasure left nothing in the trail to explain it.
 *
 * The erasure now records itself, stamps the rows it redacts with its id,
 * and verification reports those rows as redacted instead of tampered.
 */
class ErasureAuditChainTest extends TestCase
{
    use RefreshDatabase;

    private Member $subject;

    private Member $operator;

    private AuditLogIntegrityService $integrity;

    protected function setUp(): void
    {
        parent::setUp();

        $this->integrity = $this->app->make(AuditLogIntegrityService::class);

        $this->subject = Member::factory()->create([
            'email' => 'subject@example.test',
            'account_name' => 'subject',
            'display_name' => 'Erased Subject',
        ]);
        $this->operator = Member::factory()->create([
            'email' => 'operator@example.test',
            'account_name' => 'operator',
            'display_name' => 'Privacy Operator',
        ]);

        // A day of activity that has been chained and sealed, as the
        // scheduler would have left it, with another member's row between.
        $yesterday = Carbon::yesterday()->setTime(10, 0);
        $this->logFor($this->subject, '203.0.113.10', $yesterday);
        $this->logFor($this->operator, '198.51.100.20', $yesterday->copy()->addMinute());
        $this->logFor($this->subject, '203.0.113.11', $yesterday->copy()->addMinutes(2));

        $this->integrity->buildPendingChains();
        $this->assertNotNull($this->integrity->createDailySeal(Carbon::yesterday()));
    }

    private function logFor(Member $member, string $ip, Carbon $at): void
    {
        AuditLog::log([
            'category' => AuditLog::CATEGORY_AUTH,
            'action' => AuditLog::ACTION_LOGIN,
            'actor' => $member,
            'ip_address' => $ip,
            'user_agent' => 'TestUA/1.0',
            'occurred_at' => $at,
        ]);
    }

    private function erase(): array
    {
        return $this->app->make(UserPrivacyEraser::class)
            ->erase((int) $this->subject->id, DeletionMode::Anonymize, null, $this->operator);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, AuditLog>
     */
    private function subjectRows()
    {
        return AuditLog::where('actor_type', Member::class)
            ->where('actor_id', $this->subject->id)
            ->where('action', AuditLog::ACTION_LOGIN)
            ->orderBy('id')
            ->get();
    }

    public function test_the_chain_still_verifies_after_an_erasure(): void
    {
        $this->erase();
        $this->integrity->buildPendingChains();

        $result = $this->integrity->verifyIncremental(true);

        $this->assertTrue($result['is_valid'], json_encode($result['chain']['errors']));
        $this->assertSame(2, $result['chain']['redacted']);
        $this->assertSame(0, $result['chain']['invalid']);

        foreach ($this->subjectRows() as $row) {
            $this->assertNull($row->ip_address);
            $this->assertNotNull($row->redacted_at);
            $this->assertSame(AuditLog::VERIFICATION_REDACTED, $row->verification_status);
        }

        // The other member's row is untouched and still verifies on its own.
        $other = AuditLog::where('actor_id', $this->operator->id)->where('action', AuditLog::ACTION_LOGIN)->sole();
        $this->assertSame('198.51.100.20', $other->ip_address);
        $this->assertSame(AuditLog::VERIFICATION_VALID, $other->verification_status);
    }

    public function test_the_erasure_writes_its_own_row_without_personal_data(): void
    {
        $this->erase();

        $erasure = AuditLog::where('action', AuditLog::ACTION_PRIVACY_DATA_ERASED)->sole();

        $this->assertSame(Member::class, $erasure->actor_type);
        $this->assertSame($this->operator->id, (int) $erasure->actor_id);
        $this->assertSame(Member::class, $erasure->target_type);
        $this->assertSame($this->subject->id, (int) $erasure->target_id);
        $this->assertNull($erasure->target_label);
        $this->assertSame(DeletionMode::Anonymize->value, $erasure->context['mode']);

        $this->assertStringNotContainsString('subject@example.test', json_encode($erasure->toArray()));
        $this->assertStringNotContainsString('Erased Subject', json_encode($erasure->toArray()));

        foreach ($this->subjectRows() as $row) {
            $this->assertSame($erasure->request_id, $row->redaction_id);
        }
    }

    public function test_a_redaction_with_no_recorded_erasure_is_reported_as_tampering(): void
    {
        // The provider run outside the eraser: rows are redacted, but no
        // erasure is recorded to explain them.
        $this->app->make(CoreMemberPrivacyProvider::class)
            ->deleteUserData((int) $this->subject->id, DeletionMode::Anonymize);

        $result = $this->integrity->verifyChain();

        $this->assertFalse($result['is_valid']);
        $this->assertSame(2, $result['invalid']);
        $this->assertSame(['redaction_unrecorded'], $result['errors'][0]['errors']);
    }

    public function test_a_row_pointing_at_an_erasure_of_someone_else_is_reported_as_tampering(): void
    {
        $this->erase();

        $other = AuditLog::where('actor_id', $this->operator->id)->where('action', AuditLog::ACTION_LOGIN)->sole();
        $other->update([
            'ip_address' => null,
            'redacted_at' => now(),
            'redaction_id' => $this->subjectRows()->first()->redaction_id,
        ]);

        $result = $this->integrity->verifyChain();

        $this->assertFalse($result['is_valid']);
        $this->assertSame([['id' => $other->id, 'errors' => ['redaction_unrecorded']]], $result['errors']);
    }

    public function test_an_erasure_that_cannot_be_recorded_erases_nothing(): void
    {
        Audit::partialMock()->shouldReceive('logAccount')->andReturnNull();

        try {
            $this->erase();
            $this->fail('The erasure should have been refused.');
        } catch (ErasureNotRecordedException) {
            // Expected.
        }

        foreach ($this->subjectRows() as $row) {
            $this->assertNotNull($row->ip_address);
            $this->assertNull($row->redacted_at);
        }
        $this->assertSame('Erased Subject', $this->subject->fresh()->display_name);
        $this->assertTrue($this->integrity->verifyChain()['is_valid']);
    }
}
