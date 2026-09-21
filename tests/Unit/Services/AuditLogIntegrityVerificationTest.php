<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

use App\Models\AuditLog;
use App\Models\AuditLogDailySeal;
use App\Services\AuditLogIntegrityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the manual verification pass (all seals + incremental chain) and
 * the dashboard health snapshot of AuditLogIntegrityService.
 */
class AuditLogIntegrityVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected AuditLogIntegrityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AuditLogIntegrityService();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ========================================
    // verifyAllSeals()
    // ========================================

    public function test_verify_all_seals_passes_for_valid_seals(): void
    {
        $this->sealDay(now()->subDays(2)->startOfDay(), 2);
        $this->sealDay(now()->subDay()->startOfDay(), 3);

        $result = $this->service->verifyAllSeals();

        $this->assertSame(2, $result['total']);
        $this->assertSame(2, $result['valid']);
        $this->assertSame(0, $result['invalid']);
        $this->assertTrue($result['is_valid']);
    }

    public function test_verify_all_seals_detects_deleted_row_without_chain_pass(): void
    {
        $day = now()->subDay()->startOfDay();
        $logs = $this->sealDay($day, 3);

        AuditLog::where('id', $logs[1]->id)->delete();

        $result = $this->service->verifyAllSeals(false);

        $this->assertSame(1, $result['invalid']);
        $this->assertFalse($result['is_valid']);
        $this->assertSame($day->format('Y-m-d'), $result['errors'][0]['date']);
        $this->assertFalse($result['errors'][0]['checks']['log_count']);
        $this->assertArrayNotHasKey('chain', $result['errors'][0]['checks'], 'The light pass must not run the chain check.');
        $this->assertSame(AuditLogDailySeal::STATUS_INVALID, AuditLogDailySeal::forDate($day)->verification_status);
    }

    public function test_verify_all_seals_with_chain_detects_in_place_edit(): void
    {
        $day = now()->subDay()->startOfDay();
        $logs = $this->sealDay($day, 3);

        // Edit a middle row without touching record_hash: invisible to the
        // O(1) checks, visible to the chain pass.
        AuditLog::where('id', $logs[1]->id)->update(['action' => 'tampered']);

        $this->assertTrue($this->service->verifyAllSeals(false)['is_valid']);
        $this->assertFalse($this->service->verifyAllSeals(true)['is_valid']);
    }

    // ========================================
    // verifyIncremental()
    // ========================================

    public function test_incremental_verification_resumes_from_last_verified_record(): void
    {
        $first = $this->chainedLogs(3);
        $this->service->verifyChain();
        $this->assertSame($first[2]->id, $this->service->getLastVerifiedId());

        $more = $this->chainedLogs(2);

        $result = $this->service->verifyIncremental();

        $this->assertFalse($result['full_chain']);
        $this->assertSame($first[2]->id, $result['from_id']);
        // The anchor row is re-checked together with the two new rows.
        $this->assertSame(3, $result['chain']['total']);
        $this->assertTrue($result['is_valid']);
        $this->assertSame($more[1]->id, $this->service->getLastVerifiedId());
    }

    public function test_incremental_verification_runs_full_chain_when_nothing_verified_yet(): void
    {
        $this->chainedLogs(4);

        $result = $this->service->verifyIncremental();

        $this->assertTrue($result['full_chain']);
        $this->assertNull($result['from_id']);
        $this->assertSame(4, $result['chain']['total']);
    }

    public function test_incremental_verification_reports_previously_detected_tampering(): void
    {
        $logs = $this->chainedLogs(3);
        AuditLog::where('id', $logs[0]->id)->update(['action' => 'tampered']);
        $this->service->verifyChain(); // marks row 1 invalid

        $result = $this->service->verifyIncremental();

        $this->assertTrue($result['chain']['is_valid'], 'Rows after the last valid record are untouched.');
        $this->assertSame(1, $result['tampered_total']);
        $this->assertFalse($result['is_valid'], 'A standing tampered record must fail the pass.');
    }

    public function test_full_verification_recheck_every_row(): void
    {
        $logs = $this->chainedLogs(3);
        $this->service->verifyChain();
        AuditLog::where('id', $logs[0]->id)->update(['action' => 'tampered']);

        $this->assertTrue($this->service->verifyIncremental(false)['chain']['is_valid']);

        $full = $this->service->verifyIncremental(true);

        $this->assertTrue($full['full_chain']);
        $this->assertSame(3, $full['chain']['total']);
        $this->assertFalse($full['is_valid']);
    }

    // ========================================
    // getHealth()
    // ========================================

    public function test_health_is_empty_without_entries(): void
    {
        $this->assertSame(AuditLogIntegrityService::HEALTH_EMPTY, $this->service->getHealth()['state']);
    }

    public function test_health_is_ok_on_install_day_without_seal(): void
    {
        // Day one: entries exist, nothing is sealed, nothing is verified.
        $this->chainedLogs(2);

        $health = $this->service->getHealth();

        $this->assertSame(AuditLogIntegrityService::HEALTH_OK, $health['state']);
        $this->assertNull($health['unsealed_date']);
    }

    public function test_health_warns_when_chain_build_is_stalled(): void
    {
        $log = $this->createAuditLog();
        AuditLog::where('id', $log->id)->update([
            'created_at' => now()->subHours(AuditLogIntegrityService::CHAIN_STALL_HOURS + 1),
        ]);

        $health = $this->service->getHealth();

        $this->assertSame(AuditLogIntegrityService::HEALTH_CHAIN_STALLED, $health['state']);
        $this->assertSame(1, $health['stalled']);
    }

    public function test_health_ignores_recently_written_unchained_rows(): void
    {
        $this->createAuditLog();

        $this->assertSame(AuditLogIntegrityService::HEALTH_OK, $this->service->getHealth()['state']);
    }

    public function test_health_warns_when_seal_is_overdue(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-19 12:00:00'));
        $this->chainedLogs(2, Carbon::parse('2026-09-18 10:00:00'));
        $this->service->verifyChain();

        $health = $this->service->getHealth();

        $this->assertSame(AuditLogIntegrityService::HEALTH_SEAL_OVERDUE, $health['state']);
        $this->assertSame('2026-09-18', $health['unsealed_date']);
    }

    public function test_health_gives_the_scheduler_time_to_seal_yesterday(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-19 02:00:00'));
        $this->chainedLogs(2, Carbon::parse('2026-09-18 10:00:00'));
        $this->service->verifyChain();

        $this->assertNotSame(AuditLogIntegrityService::HEALTH_SEAL_OVERDUE, $this->service->getHealth()['state']);
    }

    public function test_health_recommends_verification_when_rows_waited_too_long_unverified(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-19 12:00:00'));
        $day = Carbon::parse('2026-09-18 10:00:00');
        $logs = $this->sealDay($day->copy()->startOfDay(), 2, $day);

        // Freshly chained, never verified: not stale yet.
        $this->assertSame(AuditLogIntegrityService::HEALTH_OK, $this->service->getHealth()['state']);

        // The same rows after 40 days without any verification: stale.
        AuditLog::whereIn('id', collect($logs)->pluck('id'))->update(['created_at' => now()->subDays(40)]);
        $this->assertSame(AuditLogIntegrityService::HEALTH_VERIFICATION_STALE, $this->service->getHealth()['state']);

        $this->service->verifyChain();

        $this->assertSame(AuditLogIntegrityService::HEALTH_OK, $this->service->getHealth()['state']);
    }

    public function test_health_ignores_rows_chained_after_the_last_verification(): void
    {
        // The production case: verify ran, then the hourly build chained new
        // rows. Those rows are unverified by design and must not trigger the
        // recommendation.
        Carbon::setTestNow(Carbon::parse('2026-09-19 12:00:00'));
        $day = Carbon::parse('2026-09-18 10:00:00');
        $this->sealDay($day->copy()->startOfDay(), 2, $day);
        $this->service->verifyChain();

        $this->chainedLogs(3);

        $health = $this->service->getHealth();
        $this->assertSame(AuditLogIntegrityService::HEALTH_OK, $health['state']);
        $this->assertNotNull($health['last_verified_at']);
    }

    public function test_health_staleness_follows_the_oldest_verification_not_the_newest(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-19 12:00:00'));
        $day = Carbon::parse('2026-09-18 10:00:00');
        $logs = $this->sealDay($day->copy()->startOfDay(), 2, $day);
        $this->service->verifyChain();
        $this->assertSame(AuditLogIntegrityService::HEALTH_OK, $this->service->getHealth()['state']);

        // An old row whose verification is 40 days old is not refreshed by
        // an incremental pass that only touches the newest rows.
        AuditLog::where('id', $logs[0]->id)->update(['last_verified_at' => now()->subDays(40)]);
        $this->service->verifyIncremental();

        $health = $this->service->getHealth();
        $this->assertSame(AuditLogIntegrityService::HEALTH_VERIFICATION_STALE, $health['state']);

        $this->service->verifyIncremental(true);

        $this->assertSame(AuditLogIntegrityService::HEALTH_OK, $this->service->getHealth()['state']);
    }

    public function test_health_cache_is_cleared_by_the_command(): void
    {
        $this->assertSame(AuditLogIntegrityService::HEALTH_EMPTY, $this->service->getHealthCached()['state']);

        $this->chainedLogs(1);
        $this->assertSame(AuditLogIntegrityService::HEALTH_EMPTY, $this->service->getHealthCached()['state'], 'Snapshot is served from cache.');

        $this->artisan('audit:integrity stats')->assertExitCode(0);

        $this->assertSame(AuditLogIntegrityService::HEALTH_OK, $this->service->getHealthCached()['state']);
    }

    public function test_health_is_critical_when_tampering_was_detected(): void
    {
        $logs = $this->chainedLogs(2);
        AuditLog::where('id', $logs[0]->id)->update(['action' => 'tampered']);
        $this->service->verifyChain();

        $health = $this->service->getHealth();

        $this->assertSame(AuditLogIntegrityService::HEALTH_TAMPERED, $health['state']);
        $this->assertSame(1, $health['tampered']);
    }

    public function test_health_is_critical_when_a_seal_is_invalid(): void
    {
        $day = now()->subDay()->startOfDay();
        $logs = $this->sealDay($day, 2);
        AuditLog::where('id', $logs[0]->id)->delete();
        $this->service->verifyAllSeals();

        $health = $this->service->getHealth();

        $this->assertSame(AuditLogIntegrityService::HEALTH_TAMPERED, $health['state']);
        $this->assertSame(1, $health['invalid_seals']);
    }

    // ========================================
    // Helpers
    // ========================================

    /**
     * @return array<int, AuditLog>
     */
    protected function chainedLogs(int $count, ?Carbon $occurredAt = null): array
    {
        $logs = [];
        for ($i = 0; $i < $count; $i++) {
            $log = $this->createAuditLog([
                'occurred_at' => ($occurredAt ?? now())->copy()->addMinutes($i),
            ]);
            $log->saveWithHashChain();
            $logs[] = $log;
        }

        return $logs;
    }

    /**
     * Chain $count entries on $day and seal the day.
     *
     * @return array<int, AuditLog>
     */
    protected function sealDay(Carbon $day, int $count, ?Carbon $occurredAt = null): array
    {
        $logs = $this->chainedLogs($count, $occurredAt ?? $day->copy()->addHours(9));
        $seal = $this->service->createDailySeal($day);
        $this->assertNotNull($seal);

        return $logs;
    }

    protected function createAuditLog(array $attributes = []): AuditLog
    {
        return AuditLog::create(array_merge([
            'occurred_at' => now(),
            'severity' => AuditLog::SEVERITY_INFO,
            'outcome' => AuditLog::OUTCOME_SUCCESS,
            'category' => AuditLog::CATEGORY_AUTH,
            'action' => AuditLog::ACTION_LOGIN,
            'ip_address' => '127.0.0.1',
            'context' => [],
        ], $attributes));
    }
}
