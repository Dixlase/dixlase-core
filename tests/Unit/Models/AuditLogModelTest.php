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

namespace Tests\Unit\Models;

use App\Models\AuditLog;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogModelTest extends TestCase
{
    use RefreshDatabase;

    private function createAuditLog(array $overrides = []): AuditLog
    {
        return AuditLog::create(array_merge([
            'occurred_at' => now(),
            'severity' => AuditLog::SEVERITY_INFO,
            'outcome' => AuditLog::OUTCOME_SUCCESS,
            'category' => AuditLog::CATEGORY_AUTH,
            'action' => AuditLog::ACTION_LOGIN,
            'actor_type' => Member::class,
            'actor_id' => 1,
            'actor_name' => 'Test User',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'context' => ['test' => true],
            'schema_version' => 1,
        ], $overrides));
    }

    public function test_audit_log_can_be_created(): void
    {
        $log = $this->createAuditLog();

        $this->assertDatabaseHas('audit_logs', ['id' => $log->id]);
    }

    public function test_context_is_cast_to_array(): void
    {
        $context = ['login_method' => 'password', 'device' => 'desktop'];
        $log = $this->createAuditLog(['context' => $context]);

        $log->refresh();
        $this->assertIsArray($log->context);
        $this->assertEquals($context, $log->context);
    }

    public function test_occurred_at_is_cast_to_datetime(): void
    {
        $log = $this->createAuditLog();

        $this->assertInstanceOf(\Carbon\Carbon::class, $log->occurred_at);
    }

    public function test_dangerous_operations_scope_executes_and_filters_by_risk(): void
    {
        // Regression: scopeDangerousOperations() called self::cases() on this
        // non-enum model and threw BadMethodCallException whenever the
        // "dangerous operations" audit query ran (e.g. AuditService).
        $login = $this->createAuditLog(['action' => AuditLog::ACTION_LOGIN]);
        $uninstall = $this->createAuditLog(['action' => AuditLog::ACTION_PLUGIN_UNINSTALLED]);

        // Executes without throwing.
        $rows = AuditLog::dangerousOperations()->get();
        $ids = $rows->pluck('id');

        // Every returned row is classified as a dangerous action.
        foreach ($rows as $row) {
            $this->assertTrue(
                AuditLog::getRiskLevelForAction($row->action)->isDangerous(),
                "Action {$row->action} should be dangerous"
            );
        }

        // A non-dangerous action is not included; a dangerous one is — asserted
        // against the live risk classification so the test is not brittle.
        if (! AuditLog::getRiskLevelForAction(AuditLog::ACTION_LOGIN)->isDangerous()) {
            $this->assertNotContains($login->id, $ids);
        }
        if (AuditLog::getRiskLevelForAction(AuditLog::ACTION_PLUGIN_UNINSTALLED)->isDangerous()) {
            $this->assertContains($uninstall->id, $ids);
        }
    }

    public function test_is_ai_generated_is_cast_to_boolean(): void
    {
        $log = $this->createAuditLog(['is_ai_generated' => true]);

        $this->assertIsBool($log->is_ai_generated);
        $this->assertTrue($log->is_ai_generated);
    }

    public function test_severity_constants_exist(): void
    {
        $this->assertNotNull(AuditLog::SEVERITY_INFO);
        $this->assertNotNull(AuditLog::SEVERITY_WARNING);
    }

    public function test_outcome_constants_exist(): void
    {
        $this->assertNotNull(AuditLog::OUTCOME_SUCCESS);
        $this->assertNotNull(AuditLog::OUTCOME_FAILURE);
    }

    public function test_category_constants_exist(): void
    {
        $this->assertNotNull(AuditLog::CATEGORY_AUTH);
    }

    public function test_actor_morph_relationship(): void
    {
        $member = Member::factory()->create();
        $log = $this->createAuditLog([
            'actor_type' => Member::class,
            'actor_id' => $member->id,
        ]);

        $this->assertNotNull($log->actor);
        $this->assertInstanceOf(Member::class, $log->actor);
        $this->assertEquals($member->id, $log->actor->id);
    }

    public function test_multiple_logs_can_be_created(): void
    {
        $this->createAuditLog(['action' => AuditLog::ACTION_LOGIN]);
        $this->createAuditLog(['action' => AuditLog::ACTION_LOGOUT]);

        $this->assertEquals(2, AuditLog::count());
    }
}
