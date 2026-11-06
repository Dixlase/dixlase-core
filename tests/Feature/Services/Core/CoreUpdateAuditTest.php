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
use App\Services\Core\CoreUpdater;
use App\Support\Core\CoreOperationContext;
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

    public function test_an_update_started_from_the_panel_records_where_it_came_from(): void
    {
        // What the panel writes before it spawns the detached process.
        $lock = CoreUpdater::inProgressFlagPath();
        @mkdir(dirname($lock), 0775, true);
        $saved = is_file($lock) ? file_get_contents($lock) : null;
        file_put_contents($lock, (string) json_encode([
            'started_at' => time(),
            'target_version' => '0.3.57',
            'started_by_id' => 1,
            'ip_address' => '203.0.113.7',
            'user_agent' => 'Mozilla/5.0 (test)',
            'session_id' => 'sess-1',
            'request_id' => 'req-abc',
        ]));
        CoreOperationContext::forget();

        try {
            CoreUpdateAudit::record(AuditLog::ACTION_CORE_UPDATED, true, '0.3.56', '0.3.57', null, []);

            $row = AuditLog::where('action', AuditLog::ACTION_CORE_UPDATED)->sole();
            $this->assertSame('203.0.113.7', $row->ip_address);
            $this->assertSame('Mozilla/5.0 (test)', $row->user_agent);
            $this->assertSame('sess-1', $row->session_id);
            $this->assertSame('req-abc', $row->request_id);
            $this->assertSame(AuditLog::ACTOR_SOURCE_WEB, $row->actor_source);
        } finally {
            @unlink($lock);
            if ($saved !== null) {
                file_put_contents($lock, $saved);
            }
            CoreOperationContext::forget();
        }
    }

    public function test_an_update_run_from_a_shell_is_still_recorded_as_cli(): void
    {
        $lock = CoreUpdater::inProgressFlagPath();
        $saved = is_file($lock) ? file_get_contents($lock) : null;
        @unlink($lock);
        CoreOperationContext::forget();

        try {
            CoreUpdateAudit::record(AuditLog::ACTION_CORE_UPDATED, true, '0.3.56', '0.3.57', null, []);

            $row = AuditLog::where('action', AuditLog::ACTION_CORE_UPDATED)->sole();
            $this->assertSame(AuditLog::ACTOR_SOURCE_CLI, $row->actor_source);

            // Nothing was injected. ip_address cannot be asserted null
            // here: the test harness always has a request, so
            // AuditLog::log()'s own fallback fills it with the loopback
            // address. In a detached `dls:core:update` there is no
            // request and the fallback yields null, which is the state
            // this entry describes.
            $this->assertNotSame('203.0.113.7', $row->ip_address);
            $this->assertNotSame('Mozilla/5.0 (test)', $row->user_agent);
        } finally {
            if ($saved !== null) {
                file_put_contents($lock, $saved);
            }
            CoreOperationContext::forget();
        }
    }

    public function test_a_rollback_records_what_an_update_records(): void
    {
        $rollback = (string) file_get_contents(app_path('Console/Commands/CoreRollback.php'));

        // The ledger row is kept, so its id can be recorded...
        $this->assertStringContainsString('$history = CoreVersionHistory::create([', $rollback);
        // ...and the archive it restored dependencies from is hashed
        // while the file is certainly still on disk.
        $this->assertStringContainsString('hash_file(\'sha256\', $vendorZip)', $rollback);

        $recordAt = strpos($rollback, 'CoreUpdateAudit::record(AuditLog::ACTION_CORE_ROLLED_BACK, true');
        $this->assertNotFalse($recordAt);
        $entry = substr($rollback, $recordAt, 400);

        foreach (['history_id', 'dependency_update', 'backup_record_id', 'downloaded_sha256'] as $key) {
            $this->assertStringContainsString($key, $entry, "A rollback must record {$key}, as an update does.");
        }
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
