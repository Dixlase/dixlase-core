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

namespace Tests\Feature\Console;

use App\Models\AuditLog;
use App\Services\AuditLogIntegrityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `audit:integrity verify` without options = all daily seals + the chain
 * since the last verified record; `--all` re-verifies every row.
 */
class AuditLogIntegrityVerifyCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_passes_on_a_clean_chain_with_seals(): void
    {
        $service = new AuditLogIntegrityService();
        $day = now()->subDay()->startOfDay();
        $this->chainedLogs(2, $day->copy()->addHours(9));
        $service->createDailySeal($day);
        $this->chainedLogs(2);

        $this->artisan('audit:integrity verify')
            ->expectsOutput(__('admin/command/audit.integrity.verifying_seals'))
            ->expectsOutput(__('admin/command/audit.integrity.chain_valid'))
            ->assertExitCode(0);

        $this->assertSame(4, AuditLog::verified()->count());
    }

    public function test_verify_fails_when_a_sealed_day_lost_a_row(): void
    {
        $service = new AuditLogIntegrityService();
        $day = now()->subDay()->startOfDay();
        $logs = $this->chainedLogs(3, $day->copy()->addHours(9));
        $service->createDailySeal($day);
        AuditLog::where('id', $logs[1]->id)->delete();

        $this->artisan('audit:integrity verify')
            ->expectsOutput(__('admin/command/audit.integrity.invalid_seals_detected'))
            ->assertExitCode(1);
    }

    public function test_verify_resumes_from_last_verified_record_and_all_rechecks_everything(): void
    {
        $logs = $this->chainedLogs(3);
        (new AuditLogIntegrityService())->verifyChain();

        // In-place edit of an already verified row.
        AuditLog::where('id', $logs[0]->id)->update(['action' => 'tampered']);

        $this->artisan('audit:integrity verify')
            ->expectsOutput(__('admin/command/audit.integrity.verifying_chain_from', ['id' => $logs[2]->id]))
            ->assertExitCode(0);

        $this->artisan('audit:integrity verify --all')
            ->expectsOutput(__('admin/command/audit.integrity.chain_invalid'))
            ->assertExitCode(1);

        $this->assertSame(1, AuditLog::tampered()->count());
    }

    public function test_explicit_range_still_verifies_only_that_range(): void
    {
        $logs = $this->chainedLogs(4);

        $this->artisan("audit:integrity verify --from={$logs[1]->id} --to={$logs[2]->id}")
            ->assertExitCode(0);

        $this->assertSame(2, AuditLog::verified()->count());
    }

    /**
     * @return array<int, AuditLog>
     */
    protected function chainedLogs(int $count, ?\Carbon\Carbon $occurredAt = null): array
    {
        $logs = [];
        for ($i = 0; $i < $count; $i++) {
            $log = AuditLog::create([
                'occurred_at' => ($occurredAt ?? now())->copy()->addMinutes($i),
                'severity' => AuditLog::SEVERITY_INFO,
                'outcome' => AuditLog::OUTCOME_SUCCESS,
                'category' => AuditLog::CATEGORY_AUTH,
                'action' => AuditLog::ACTION_LOGIN,
                'ip_address' => '127.0.0.1',
                'context' => [],
            ]);
            $log->saveWithHashChain();
            $logs[] = $log;
        }

        return $logs;
    }
}
