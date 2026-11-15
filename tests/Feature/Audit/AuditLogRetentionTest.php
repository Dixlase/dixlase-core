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

namespace Tests\Feature\Audit;

use App\Facades\Audit;
use App\Models\AuditLog;
use App\Models\AuditLogDailySeal;
use App\Models\AuditLogRetentionPoint;
use App\Services\AuditLogIntegrityService;
use App\Services\AuditLogRetentionService;
use App\Services\DatabaseCleanupService;
use App\Services\Security\IntegrityKeyring;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Core #1048: deleting old audit rows used to leave the daily seals of the
 * deleted days behind and cut the chain off its genesis row, so every
 * retention run made verification report tampering. Retention now goes
 * through one path that records itself, retires those seals and records a
 * signed retention point; a deletion any other way is still reported.
 */
class AuditLogRetentionTest extends TestCase
{
    use RefreshDatabase;

    private AuditLogIntegrityService $integrity;

    private AuditLogRetentionService $retention;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-06-15 12:00:00'));
        AuditLog::forgetChainLockAvailability();
        $this->useKey(null);
        $this->integrity = app(AuditLogIntegrityService::class);
        $this->retention = app(AuditLogRetentionService::class);
    }

    protected function tearDown(): void
    {
        AuditLog::forgetChainLockAvailability();
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * @return array<string, array{0: bool}>
     */
    public static function keying(): array
    {
        return ['without an integrity key' => [false], 'with an integrity key' => [true]];
    }

    #[DataProvider('keying')]
    public function test_after_retention_the_chain_and_the_seals_still_verify(bool $keyed): void
    {
        $this->useKey($keyed ? IntegrityKeyring::generate(2) : null);
        $this->sealedDays([10, 9, 3, 2]);

        $result = $this->retention->prune(5);

        $this->assertSame(4, $result['deleted']);
        $this->assertSame(2, $result['seals_removed']);
        $this->assertTrue($result['point_recorded']);
        $this->assertSame(['day.3', 'day.3', 'day.2', 'day.2'], $this->remainingFixtures());

        $chain = $this->integrity->verifyChain();
        $this->assertTrue($chain['is_valid'], json_encode($chain['errors']));
        $seals = $this->integrity->verifyAllSeals(true);
        $this->assertTrue($seals['is_valid'], json_encode($seals['errors']));
        $this->assertSame(2, $seals['total']);

        $point = AuditLogRetentionPoint::sole();
        $this->assertSame($keyed ? 2 : IntegrityKeyring::LEGACY_SEAL_VERSION, $point->key_version);
        $this->assertSame(4, $point->deleted_rows);
    }

    public function test_the_run_is_recorded_before_the_rows_go(): void
    {
        $this->sealedDays([10, 2]);

        $this->retention->prune(5, null, 'cli');

        $row = AuditLog::where('action', AuditLog::ACTION_AUDIT_LOG_CLEANUP)->sole();
        $this->assertSame(2, $row->context['deleted']);
        $this->assertSame(5, $row->context['older_than_days']);
        $this->assertSame('cli', $row->context['via']);
        $this->assertSame(Carbon::parse('2026-06-10 00:00:00')->toIso8601String(), $row->context['cutoff']);
    }

    public function test_whole_days_are_deleted(): void
    {
        // Early and late on the day the cutoff falls on: both stay.
        $this->writeAt('day.5.early', Carbon::parse('2026-06-10 00:30:00'));
        $this->writeAt('day.5.late', Carbon::parse('2026-06-10 23:30:00'));
        $this->writeAt('day.6', Carbon::parse('2026-06-09 23:59:00'));

        // The day.6 row is out of id order, so it stays: rows are cut off
        // the start of the chain only, never out of its middle.
        $this->retention->prune(5);

        $this->assertSame(['day.5.early', 'day.5.late', 'day.6'], $this->remainingFixtures());
        $this->assertTrue($this->integrity->verifyChain()['is_valid']);
    }

    public function test_rows_deleted_outside_retention_are_still_reported(): void
    {
        $this->sealedDays([10, 3, 2]);
        $this->retention->prune(5);

        // A later deletion of the first remaining row, with no retention run
        $first = AuditLog::withHashChain()->orderBy('id')->first();
        DB::table('audit_logs')->where('id', $first->id)->delete();

        $chain = $this->integrity->verifyChain();
        $this->assertFalse($chain['is_valid']);
        $this->assertContains('invalid_genesis', $chain['errors'][0]['errors']);
        $this->assertFalse($this->integrity->verifyAllSeals()['is_valid']);
    }

    public function test_a_retention_point_without_a_valid_signature_is_ignored(): void
    {
        $this->sealedDays([3, 2]);
        $first = AuditLog::withHashChain()->orderBy('id')->first();
        DB::table('audit_logs')->where('id', $first->id)->delete();
        $next = AuditLog::withHashChain()->orderBy('id')->first();

        DB::table('audit_log_retention_points')->insert([
            'first_log_id' => $next->id,
            'previous_hash' => $next->previous_hash,
            'cutoff_at' => now(),
            'deleted_rows' => 1,
            'key_version' => IntegrityKeyring::LEGACY_SEAL_VERSION,
            'signature' => str_repeat('0', 64),
            'created_at' => now(),
        ]);

        $chain = $this->integrity->verifyChain();
        $this->assertFalse($chain['is_valid']);
        $this->assertContains('invalid_genesis', $chain['errors'][0]['errors']);
    }

    public function test_a_point_for_another_row_does_not_vouch_for_this_one(): void
    {
        $this->sealedDays([10, 3, 2]);
        $this->retention->prune(5);

        // Delete the first remaining row as well: the recorded point names
        // that row, not the one that is first now.
        $first = AuditLog::withHashChain()->orderBy('id')->first();
        DB::table('audit_logs')->where('id', $first->id)->delete();

        $this->assertFalse($this->integrity->verifyChain()['is_valid']);
    }

    public function test_a_site_pruned_the_old_way_recovers_with_one_run(): void
    {
        $this->sealedDays([10, 9, 3]);

        // The old cleanup: rows deleted by date, half of a day included,
        // seals left behind.
        $ids = AuditLog::orderBy('id')->pluck('id')->all();
        DB::table('audit_logs')->whereIn('id', array_slice($ids, 0, 3))->delete();
        $this->assertFalse($this->integrity->verifyChain()['is_valid']);
        $this->assertFalse($this->integrity->verifyAllSeals()['is_valid']);

        // Nothing is old enough to delete now, but the run adopts the
        // current first row and retires the seals of the rows that are gone.
        $result = $this->retention->prune(365);

        $this->assertSame(0, $result['deleted']);
        $this->assertSame(2, $result['seals_removed']);
        $this->assertTrue($result['point_recorded']);
        $chain = $this->integrity->verifyChain();
        $this->assertTrue($chain['is_valid'], json_encode($chain['errors']));
        $seals = $this->integrity->verifyAllSeals(true);
        $this->assertTrue($seals['is_valid'], json_encode($seals['errors']));
    }

    public function test_a_chain_that_still_starts_at_genesis_gets_no_point(): void
    {
        $this->sealedDays([3, 2]);

        $result = $this->retention->prune(365);

        $this->assertSame(0, $result['deleted']);
        $this->assertFalse($result['point_recorded']);
        $this->assertSame(0, AuditLogRetentionPoint::count());
    }

    public function test_a_second_run_with_nothing_to_delete_adds_no_point(): void
    {
        $this->sealedDays([10, 2]);
        $this->retention->prune(5);
        $this->retention->prune(5);

        $this->assertSame(1, AuditLogRetentionPoint::count());
        $this->assertTrue($this->integrity->verifyChain()['is_valid']);
    }

    public function test_at_least_one_day_is_kept(): void
    {
        $this->sealedDays([2]);

        $this->expectException(\InvalidArgumentException::class);

        try {
            $this->retention->prune(0);
        } finally {
            $this->assertSame(2, AuditLog::where('category', 'fixture')->count());
        }
    }

    public function test_nothing_is_deleted_when_the_run_cannot_be_recorded(): void
    {
        $this->sealedDays([10, 2]);
        Audit::shouldReceive('log')->once()->andReturnNull();

        try {
            $this->retention->prune(5);
            $this->fail('An unrecorded retention run must not delete anything');
        } catch (\RuntimeException) {
            // expected
        }

        $this->assertSame(4, AuditLog::where('category', 'fixture')->count());
        $this->assertSame(2, AuditLogDailySeal::count());
    }

    public function test_the_generic_cleanup_goes_through_retention(): void
    {
        $this->sealedDays([10, 2]);

        $result = app(DatabaseCleanupService::class)->cleanup('audit_logs', 5, true);

        $this->assertTrue($result['success']);
        $this->assertSame(2, $result['count']);
        $this->assertTrue(AuditLog::where('action', AuditLog::ACTION_AUDIT_LOG_CLEANUP)->exists());
        $this->assertTrue($this->integrity->verifyChain()['is_valid']);
        $this->assertTrue($this->integrity->verifyAllSeals()['is_valid']);
    }

    public function test_the_generic_cleanup_cannot_empty_the_audit_log(): void
    {
        $this->sealedDays([10, 2]);

        $result = app(DatabaseCleanupService::class)->cleanup('audit_logs', 0, true);
        $all = app(DatabaseCleanupService::class)->cleanupAll(0, true);

        $this->assertFalse($result['success']);
        $this->assertFalse($all['details']['audit_logs']['success']);
        $this->assertSame(4, AuditLog::where('category', 'fixture')->count());
    }

    public function test_the_cleanup_command_goes_through_retention(): void
    {
        $this->sealedDays([10, 2]);

        $this->artisan('dls:cleanup', ['--type' => 'audit_logs', '--days' => 5, '--force' => true])
            ->assertSuccessful();

        $this->assertSame(['day.2', 'day.2'], $this->remainingFixtures());
        $this->assertSame('cli', AuditLog::where('action', AuditLog::ACTION_AUDIT_LOG_CLEANUP)->sole()->context['via']);
        $this->assertTrue($this->integrity->verifyChain()['is_valid']);
    }

    /**
     * Two chained rows on each of the given days ago, each day sealed.
     *
     * @param  list<int>  $daysAgo  Oldest first, so ids follow time
     */
    private function sealedDays(array $daysAgo): void
    {
        foreach ($daysAgo as $days) {
            $day = now()->subDays($days)->startOfDay();
            $this->writeAt("day.{$days}", $day->copy()->setTime(9, 0));
            $this->writeAt("day.{$days}", $day->copy()->setTime(17, 0));
        }

        foreach ($daysAgo as $days) {
            $this->assertNotNull($this->integrity->createDailySeal(now()->subDays($days)->startOfDay()));
        }
    }

    /**
     * @return list<string>
     */
    private function remainingFixtures(): array
    {
        return AuditLog::where('category', 'fixture')->orderBy('id')->pluck('action')->all();
    }

    private function useKey(?string $current): void
    {
        config([
            'security.integrity_keys.current' => $current,
            'security.integrity_keys.previous' => null,
        ]);
    }

    private function writeAt(string $action, Carbon $at): AuditLog
    {
        $log = AuditLog::create([
            'occurred_at' => $at,
            'severity' => AuditLog::SEVERITY_INFO,
            'outcome' => AuditLog::OUTCOME_SUCCESS,
            'category' => 'fixture',
            'action' => $action,
            'ip_address' => '127.0.0.1',
            'context' => [],
        ]);
        $log->saveWithHashChain();

        return $log;
    }
}
