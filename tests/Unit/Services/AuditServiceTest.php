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

namespace Tests\Unit\Services;

use App\Models\AuditLog;
use App\Models\Member;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuditService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AuditService::class);
    }

    public function test_log_creates_audit_entry(): void
    {
        $log = $this->service->log([
            'action' => AuditLog::ACTION_LOGIN,
            'category' => AuditLog::CATEGORY_AUTH,
            'severity' => AuditLog::SEVERITY_INFO,
            'outcome' => AuditLog::OUTCOME_SUCCESS,
        ]);

        $this->assertNotNull($log);
        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_LOGIN,
        ]);
    }

    public function test_log_auth_creates_auth_category_entry(): void
    {
        $log = $this->service->logAuth(AuditLog::ACTION_LOGIN, [
            'severity' => AuditLog::SEVERITY_INFO,
            'outcome' => AuditLog::OUTCOME_SUCCESS,
        ]);

        $this->assertNotNull($log);
        $this->assertEquals(AuditLog::CATEGORY_AUTH, $log->category);
    }

    public function test_log_security_creates_security_category_entry(): void
    {
        $log = $this->service->logSecurity('security_test', [
            'severity' => AuditLog::SEVERITY_WARNING,
            'outcome' => AuditLog::OUTCOME_FAILURE,
        ]);

        $this->assertNotNull($log);
        $this->assertEquals(AuditLog::CATEGORY_SECURITY, $log->category);
    }

    public function test_get_request_id_returns_consistent_value(): void
    {
        $first = $this->service->getRequestId();
        $second = $this->service->getRequestId();

        $this->assertEquals($first, $second);
        $this->assertNotEmpty($first);
    }

    public function test_set_request_id(): void
    {
        $this->service->setRequestId('custom-id-123');

        $this->assertEquals('custom-id-123', $this->service->getRequestId());
    }

    public function test_diff_detects_changes(): void
    {
        $old = ['name' => 'Old Name', 'email' => 'same@test.com'];
        $new = ['name' => 'New Name', 'email' => 'same@test.com'];

        $diff = $this->service->diff($old, $new);

        $this->assertArrayHasKey('name', $diff);
        $this->assertArrayNotHasKey('email', $diff);
    }

    public function test_diff_empty_when_no_changes(): void
    {
        $data = ['name' => 'Same', 'email' => 'same@test.com'];

        $diff = $this->service->diff($data, $data);

        $this->assertEmpty($diff);
    }

    public function test_set_actor_source(): void
    {
        $this->service->setActorSource('api');

        $this->assertEquals('api', $this->service->getActorSource());
    }

    public function test_file_logging_toggle(): void
    {
        $this->service->enableFileLogging();
        $this->assertTrue($this->service->isFileLoggingEnabled());

        $this->service->disableFileLogging();
        $this->assertFalse($this->service->isFileLoggingEnabled());
    }

    public function test_build_ai_context_returns_array(): void
    {
        $context = $this->service->buildAiContext('gpt-4', 'Test prompt', ['key' => 'value']);

        $this->assertIsArray($context);
        $this->assertNotEmpty($context);
    }

    public function test_log_with_actor(): void
    {
        $member = Member::factory()->create();

        $log = $this->service->log([
            'action' => 'test_action',
            'category' => AuditLog::CATEGORY_AUTH,
            'severity' => AuditLog::SEVERITY_INFO,
            'outcome' => AuditLog::OUTCOME_SUCCESS,
            'actor' => $member,
        ]);

        $this->assertNotNull($log);
        $this->assertEquals($member->id, $log->actor_id);
    }
}
