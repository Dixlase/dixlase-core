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

declare(strict_types=1);

namespace Tests\Unit\DTO\Audit;

use App\DTO\Audit\AuditLogPayload;
use App\Events\AuditLogCreated;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AuditLogPayloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_schema_version_is_one(): void
    {
        $this->assertSame(1, AuditLogCreated::SCHEMA_VERSION);
    }

    public function test_payload_exposes_documented_fields_with_expected_types(): void
    {
        $payload = new AuditLogPayload(
            version: AuditLogCreated::SCHEMA_VERSION,
            id: 42,
            occurred_at: '2026-05-10T12:34:56+00:00',
            severity: AuditLog::SEVERITY_INFO,
            outcome: AuditLog::OUTCOME_SUCCESS,
            category: AuditLog::CATEGORY_AUTH,
            action: AuditLog::ACTION_LOGIN,
            site_id: 1,
            actor_type: 'App\\Models\\Member',
            actor_id: 7,
            actor_name: 'Alice',
            impersonated_by_id: null,
            target_type: null,
            target_id: null,
            target_label: null,
            ip_address: '203.0.113.4',
            user_agent: 'Mozilla/5.0',
            request_id: 'req-abc',
            session_id: 'sess-xyz',
            plugin_name: null,
            plugin_version: null,
            actor_source: 'web',
            is_ai_generated: false,
            context: ['message' => 'login ok'],
            record_hash: str_repeat('a', 64),
            chain_sequence: 100,
        );

        $this->assertSame(1, $payload->version);
        $this->assertSame(42, $payload->id);
        $this->assertSame('2026-05-10T12:34:56+00:00', $payload->occurred_at);
        $this->assertSame('info', $payload->severity);
        $this->assertSame('success', $payload->outcome);
        $this->assertSame('auth', $payload->category);
        $this->assertSame('login', $payload->action);
        $this->assertSame(1, $payload->site_id);
        $this->assertSame('App\\Models\\Member', $payload->actor_type);
        $this->assertSame(7, $payload->actor_id);
        $this->assertSame('Alice', $payload->actor_name);
        $this->assertNull($payload->impersonated_by_id);
        $this->assertSame(['message' => 'login ok'], $payload->context);
        $this->assertSame(str_repeat('a', 64), $payload->record_hash);
        $this->assertSame(100, $payload->chain_sequence);
    }

    public function test_to_array_emits_documented_keys_in_documented_order(): void
    {
        $payload = $this->makeSamplePayload();

        $expectedKeys = [
            'version',
            'id',
            'occurred_at',
            'severity',
            'outcome',
            'category',
            'action',
            'site_id',
            'actor_type',
            'actor_id',
            'actor_name',
            'impersonated_by_id',
            'target_type',
            'target_id',
            'target_label',
            'ip_address',
            'user_agent',
            'request_id',
            'session_id',
            'plugin_name',
            'plugin_version',
            'actor_source',
            'is_ai_generated',
            'context',
            'record_hash',
            'chain_sequence',
        ];

        $this->assertSame($expectedKeys, array_keys($payload->toArray()));
    }

    public function test_json_serialize_matches_to_array(): void
    {
        $payload = $this->makeSamplePayload();

        $this->assertSame($payload->toArray(), $payload->jsonSerialize());
    }

    public function test_does_not_expose_internal_integrity_columns(): void
    {
        $payload = $this->makeSamplePayload();
        $array = $payload->toArray();

        // Integrity-chain internals must not leak to subscribers.
        $this->assertArrayNotHasKey('previous_hash', $array);
        $this->assertArrayNotHasKey('hash_algorithm', $array);
        $this->assertArrayNotHasKey('verification_status', $array);
        $this->assertArrayNotHasKey('last_verified_at', $array);
        // Per-row schema_version is replaced by the payload's own version field.
        $this->assertArrayNotHasKey('schema_version', $array);
    }

    public function test_from_audit_log_maps_columns_to_payload(): void
    {
        $now = Carbon::parse('2026-05-10T12:34:56+00:00');

        $auditLog = new AuditLog;
        $auditLog->id = 42;
        $auditLog->occurred_at = $now;
        $auditLog->severity = AuditLog::SEVERITY_INFO;
        $auditLog->outcome = AuditLog::OUTCOME_SUCCESS;
        $auditLog->category = AuditLog::CATEGORY_AUTH;
        $auditLog->action = AuditLog::ACTION_LOGIN;
        $auditLog->site_id = 1;
        $auditLog->actor_type = 'App\\Models\\Member';
        $auditLog->actor_id = 7;
        $auditLog->actor_name = 'Alice';
        $auditLog->ip_address = '203.0.113.4';
        $auditLog->user_agent = 'Mozilla/5.0';
        $auditLog->is_ai_generated = false;
        $auditLog->context = ['message' => 'login ok'];
        $auditLog->record_hash = str_repeat('a', 64);
        $auditLog->chain_sequence = 100;

        $payload = AuditLogPayload::fromAuditLog($auditLog);

        $this->assertSame(AuditLogCreated::SCHEMA_VERSION, $payload->version);
        $this->assertSame(42, $payload->id);
        $this->assertSame('2026-05-10T12:34:56+00:00', $payload->occurred_at);
        $this->assertSame('info', $payload->severity);
        $this->assertSame('login', $payload->action);
        $this->assertSame(7, $payload->actor_id);
        $this->assertSame(['message' => 'login ok'], $payload->context);
    }

    public function test_audit_service_dispatches_event_carrying_payload(): void
    {
        Event::fake([AuditLogCreated::class]);

        \App\Facades\Audit::log([
            'category' => AuditLog::CATEGORY_AUTH,
            'action' => AuditLog::ACTION_LOGIN,
            'outcome' => AuditLog::OUTCOME_SUCCESS,
            'context' => ['message' => 'Test login'],
        ]);

        Event::assertDispatched(AuditLogCreated::class, function (AuditLogCreated $event) {
            $this->assertInstanceOf(AuditLogPayload::class, $event->payload);
            $this->assertSame(1, $event->payload->version);
            $this->assertSame('auth', $event->payload->category);
            $this->assertSame('login', $event->payload->action);
            $this->assertSame('success', $event->payload->outcome);

            return true;
        });
    }

    private function makeSamplePayload(): AuditLogPayload
    {
        return new AuditLogPayload(
            version: AuditLogCreated::SCHEMA_VERSION,
            id: 42,
            occurred_at: '2026-05-10T12:34:56+00:00',
            severity: AuditLog::SEVERITY_INFO,
            outcome: AuditLog::OUTCOME_SUCCESS,
            category: AuditLog::CATEGORY_AUTH,
            action: AuditLog::ACTION_LOGIN,
            site_id: 1,
            actor_type: 'App\\Models\\Member',
            actor_id: 7,
            actor_name: 'Alice',
            impersonated_by_id: null,
            target_type: null,
            target_id: null,
            target_label: null,
            ip_address: '203.0.113.4',
            user_agent: 'Mozilla/5.0',
            request_id: 'req-abc',
            session_id: 'sess-xyz',
            plugin_name: null,
            plugin_version: null,
            actor_source: 'web',
            is_ai_generated: false,
            context: ['message' => 'login ok'],
            record_hash: str_repeat('a', 64),
            chain_sequence: 100,
        );
    }
}
