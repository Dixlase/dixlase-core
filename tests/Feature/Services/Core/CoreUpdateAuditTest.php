<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

namespace Tests\Feature\Services\Core;

use App\Models\AuditLog;
use App\Models\Member;
use App\Services\Core\CoreUpdateAudit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Core update and rollback must leave an audit-log entry.
 *
 * Regression: on a Docker installer sandbox, a core update (0.3.56 → 0.3.57)
 * and its rollback left no row at all in dls_audit_logs — only the ledger in
 * CoreVersionHistory, which is not the hash-chained log audit:integrity
 * verifies. "Who updated the core, and when" had no answer there.
 */
class CoreUpdateAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_successful_update_is_recorded_with_its_actor(): void
    {
        $member = Member::factory()->create();

        CoreUpdateAudit::record(AuditLog::ACTION_CORE_UPDATED, true, '0.3.56', '0.3.57', $member->id, ['history_id' => 7]);

        $row = AuditLog::where('action', AuditLog::ACTION_CORE_UPDATED)->sole();
        $this->assertSame(AuditLog::CATEGORY_SYSTEM, $row->category);
        $this->assertSame(AuditLog::OUTCOME_SUCCESS, $row->outcome);
        $this->assertSame(AuditLog::SEVERITY_NOTICE, $row->severity);
        $this->assertSame($member->id, (int) $row->actor_id);
        $this->assertSame('Dixlase core v0.3.56 → v0.3.57', $row->target_label);
        $this->assertSame('0.3.56', $row->context['from']);
        $this->assertSame('0.3.57', $row->context['to']);
        $this->assertSame(7, $row->context['history_id']);
    }

    public function test_a_failed_rollback_from_the_cli_is_recorded_without_an_actor(): void
    {
        CoreUpdateAudit::record(AuditLog::ACTION_CORE_ROLLBACK_FAILED, false, '0.3.57', '0.3.56', null, ['error' => 'boom']);

        $row = AuditLog::where('action', AuditLog::ACTION_CORE_ROLLBACK_FAILED)->sole();
        $this->assertSame(AuditLog::OUTCOME_FAILURE, $row->outcome);
        $this->assertSame(AuditLog::SEVERITY_ERROR, $row->severity);
        $this->assertNull($row->actor_id);
        $this->assertSame('boom', $row->context['error']);
    }

    public function test_update_and_rollback_record_every_outcome(): void
    {
        $updater = (string) file_get_contents(app_path('Services/Core/CoreUpdater.php'));
        $rollback = (string) file_get_contents(app_path('Console/Commands/CoreRollback.php'));

        $this->assertStringContainsString('CoreUpdateAudit::record(AuditLog::ACTION_CORE_UPDATED, true', $updater);
        // Preflight refusal and the catch block both record a failure.
        $this->assertSame(2, substr_count($updater, 'CoreUpdateAudit::record(AuditLog::ACTION_CORE_UPDATE_FAILED, false'));
        $this->assertStringContainsString('$this->auditPreflightRefusal(', $updater);
        $this->assertStringContainsString('CoreUpdateAudit::record(AuditLog::ACTION_CORE_ROLLED_BACK, true', $rollback);
        $this->assertStringContainsString('CoreUpdateAudit::record(AuditLog::ACTION_CORE_ROLLBACK_FAILED, false', $rollback);
    }
}
