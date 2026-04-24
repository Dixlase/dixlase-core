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

namespace Tests\Unit;

use App\Facades\Audit;
use App\Models\AuditLog;
use App\Models\Member;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_facade_is_registered(): void
    {
        $this->assertInstanceOf(AuditService::class, app('audit'));
    }

    public function test_can_log_basic_audit_event(): void
    {
        $log = Audit::log([
            'category' => AuditLog::CATEGORY_AUTH,
            'action' => AuditLog::ACTION_LOGIN,
            'outcome' => AuditLog::OUTCOME_SUCCESS,
            'context' => ['message' => 'Test login'],
        ]);

        $this->assertNotNull($log);
        $this->assertDatabaseHas('audit_logs', [
            'category' => 'auth',
            'action' => 'login',
            'outcome' => 'success',
        ]);
    }

    public function test_can_log_auth_event(): void
    {
        $log = Audit::logAuth(AuditLog::ACTION_LOGIN, [
            'outcome' => AuditLog::OUTCOME_SUCCESS,
        ]);

        $this->assertNotNull($log);
        $this->assertEquals('auth', $log->category);
        $this->assertEquals('login', $log->action);
    }

    public function test_can_log_security_event(): void
    {
        $log = Audit::logSecurity(AuditLog::ACTION_IP_BLOCKED, [
            'context' => ['blocked_ip' => '192.168.1.1'],
        ]);

        $this->assertNotNull($log);
        $this->assertEquals('security', $log->category);
        $this->assertEquals('warning', $log->severity);
    }

    public function test_can_log_with_actor_model(): void
    {
        $member = Member::factory()->create(['display_name' => 'Test User']);

        $log = Audit::logAuth(AuditLog::ACTION_LOGIN, [
            'actor' => $member,
            'outcome' => AuditLog::OUTCOME_SUCCESS,
        ]);

        $this->assertNotNull($log);
        $this->assertEquals(Member::class, $log->actor_type);
        $this->assertEquals($member->id, $log->actor_id);
        $this->assertEquals('Test User', $log->actor_name);
    }

    public function test_can_log_with_target_model(): void
    {
        $member = Member::factory()->create(['display_name' => 'Target User']);

        $log = Audit::logAccount(AuditLog::ACTION_MEMBER_UPDATED, [
            'target' => $member,
        ]);

        $this->assertNotNull($log);
        $this->assertEquals(Member::class, $log->target_type);
        $this->assertEquals($member->id, $log->target_id);
    }

    public function test_request_id_is_consistent_within_request(): void
    {
        $requestId1 = Audit::getRequestId();
        $requestId2 = Audit::getRequestId();

        $this->assertEquals($requestId1, $requestId2);
    }

    public function test_can_set_plugin_context(): void
    {
        Audit::setPluginContext('test-plugin', '1.0.0');

        $log = Audit::logPlugin('custom_action', []);

        $this->assertEquals('test-plugin', $log->plugin_name);
        $this->assertEquals('1.0.0', $log->plugin_version);

        Audit::clearPluginContext();
    }

    public function test_can_generate_diff(): void
    {
        $before = ['name' => 'Old Name', 'email' => 'old@example.com'];
        $after = ['name' => 'New Name', 'email' => 'old@example.com'];

        $diff = Audit::diff($before, $after);

        $this->assertArrayHasKey('name', $diff);
        $this->assertEquals('Old Name', $diff['name']['from']);
        $this->assertEquals('New Name', $diff['name']['to']);
        $this->assertArrayNotHasKey('email', $diff);
    }

    public function test_can_log_settings_change(): void
    {
        $log = Audit::logSettingsChange(
            'site_name',
            'Old Site',
            'New Site'
        );

        $this->assertNotNull($log);
        $this->assertEquals('system', $log->category);
        $this->assertEquals('settings_updated', $log->action);
        $this->assertEquals('site_name', $log->target_label);
        $this->assertEquals('Old Site', $log->context['before']);
        $this->assertEquals('New Site', $log->context['after']);
    }

    public function test_audit_log_scopes(): void
    {
        // Create test logs
        Audit::logAuth(AuditLog::ACTION_LOGIN, ['outcome' => AuditLog::OUTCOME_SUCCESS]);
        Audit::logAuth(AuditLog::ACTION_LOGIN_FAILED, ['outcome' => AuditLog::OUTCOME_FAILURE]);
        Audit::logSecurity(AuditLog::ACTION_IP_BLOCKED, []);

        // Test category scope
        $authLogs = AuditLog::inCategory(AuditLog::CATEGORY_AUTH)->get();
        $this->assertEquals(2, $authLogs->count());

        // Test action scope
        $loginLogs = AuditLog::withAction(AuditLog::ACTION_LOGIN)->get();
        $this->assertEquals(1, $loginLogs->count());

        // Test failed scope
        $failedLogs = AuditLog::failed()->get();
        $this->assertEquals(1, $failedLogs->count());
    }

    public function test_audit_log_helper_methods(): void
    {
        // Test getCategoryForAction
        $this->assertEquals(
            AuditLog::CATEGORY_AUTH,
            AuditLog::getCategoryForAction(AuditLog::ACTION_LOGIN)
        );
        $this->assertEquals(
            AuditLog::CATEGORY_SECURITY,
            AuditLog::getCategoryForAction(AuditLog::ACTION_IP_BLOCKED)
        );

        // Test getDefaultSeverity
        $this->assertEquals(
            AuditLog::SEVERITY_CRITICAL,
            AuditLog::getDefaultSeverity(AuditLog::ACTION_LOCKOUT_TRIGGERED)
        );
        $this->assertEquals(
            AuditLog::SEVERITY_WARNING,
            AuditLog::getDefaultSeverity(AuditLog::ACTION_LOGIN_FAILED)
        );
    }

    // ========================================
    // AI Operation Tests
    // ========================================

    public function test_can_log_ai_operation(): void
    {
        $log = Audit::logAi(AuditLog::ACTION_AI_CONTENT_GENERATED, [
            'context' => Audit::buildAiContext(
                'Auto-generated blog post based on trending topics',
                'content_generation'
            ),
        ]);

        $this->assertNotNull($log);
        $this->assertEquals(AuditLog::CATEGORY_AI, $log->category);
        $this->assertEquals(AuditLog::ACTION_AI_CONTENT_GENERATED, $log->action);
        $this->assertTrue($log->is_ai_generated);
        $this->assertEquals(AuditLog::ACTOR_SOURCE_AI_PLUGIN, $log->actor_source);
    }

    public function test_build_ai_context_includes_reason_and_intent(): void
    {
        $context = Audit::buildAiContext('SEO optimization', 'improve_ranking', ['model' => 'gpt-4']);

        $this->assertEquals('SEO optimization', $context['reason']);
        $this->assertEquals('improve_ranking', $context['intent']);
        $this->assertEquals('gpt-4', $context['model']);
    }

    public function test_actor_source_is_stored_in_log(): void
    {
        Audit::setActorSource(AuditLog::ACTOR_SOURCE_API);

        $log = Audit::log([
            'category' => AuditLog::CATEGORY_SYSTEM,
            'action' => AuditLog::ACTION_SETTINGS_UPDATED,
        ]);

        $this->assertEquals(AuditLog::ACTOR_SOURCE_API, $log->actor_source);

        // Reset
        Audit::setActorSource(null);
    }

    public function test_is_ai_generated_defaults_to_false(): void
    {
        $log = Audit::log([
            'category' => AuditLog::CATEGORY_AUTH,
            'action' => AuditLog::ACTION_LOGIN,
        ]);

        $this->assertFalse($log->is_ai_generated);
    }

    public function test_scope_ai_generated(): void
    {
        Audit::logAi(AuditLog::ACTION_AI_CONTENT_GENERATED);
        Audit::log([
            'category' => AuditLog::CATEGORY_AUTH,
            'action' => AuditLog::ACTION_LOGIN,
        ]);

        $aiLogs = AuditLog::aiGenerated()->get();
        $this->assertCount(1, $aiLogs);
        $this->assertEquals(AuditLog::ACTION_AI_CONTENT_GENERATED, $aiLogs->first()->action);
    }

    public function test_scope_from_source(): void
    {
        Audit::setActorSource(AuditLog::ACTOR_SOURCE_CLI);
        Audit::log([
            'category' => AuditLog::CATEGORY_SYSTEM,
            'action' => AuditLog::ACTION_SETTINGS_UPDATED,
        ]);

        Audit::setActorSource(AuditLog::ACTOR_SOURCE_WEB);
        Audit::log([
            'category' => AuditLog::CATEGORY_AUTH,
            'action' => AuditLog::ACTION_LOGIN,
        ]);

        $cliLogs = AuditLog::fromSource(AuditLog::ACTOR_SOURCE_CLI)->get();
        $this->assertCount(1, $cliLogs);

        // Reset
        Audit::setActorSource(null);
    }

    public function test_scope_bot_related(): void
    {
        Audit::logSecurity(AuditLog::ACTION_BOT_LOGIN_DETECTED);
        Audit::logAuth(AuditLog::ACTION_LOGIN);

        $botLogs = AuditLog::botRelated()->get();
        $this->assertCount(1, $botLogs);
    }

    public function test_ai_category_mapping(): void
    {
        $this->assertEquals(
            AuditLog::CATEGORY_AI,
            AuditLog::getCategoryForAction(AuditLog::ACTION_AI_CONTENT_GENERATED)
        );
        $this->assertEquals(
            AuditLog::CATEGORY_AI,
            AuditLog::getCategoryForAction(AuditLog::ACTION_AI_BULK_OPERATION)
        );
    }

    public function test_bot_actions_map_to_security_category(): void
    {
        $this->assertEquals(
            AuditLog::CATEGORY_SECURITY,
            AuditLog::getCategoryForAction(AuditLog::ACTION_BOT_LOGIN_DETECTED)
        );
        $this->assertEquals(
            AuditLog::CATEGORY_SECURITY,
            AuditLog::getCategoryForAction(AuditLog::ACTION_BOT_SCRAPING_DETECTED)
        );
    }

    public function test_ai_bulk_operation_is_high_risk(): void
    {
        $riskLevel = AuditLog::getRiskLevelForAction(AuditLog::ACTION_AI_BULK_OPERATION);
        $this->assertTrue($riskLevel->isDangerous());
    }

    public function test_audit_log_created_event_is_dispatched(): void
    {
        \Illuminate\Support\Facades\Event::fake([\App\Events\AuditLogCreated::class]);

        Audit::log([
            'category' => AuditLog::CATEGORY_AUTH,
            'action' => AuditLog::ACTION_LOGIN,
        ]);

        \Illuminate\Support\Facades\Event::assertDispatched(\App\Events\AuditLogCreated::class, function ($event) {
            return $event->auditLog->action === AuditLog::ACTION_LOGIN;
        });
    }

    // ========================================
    // Bulk Settings Change Tests
    // ========================================

    public function test_log_bulk_settings_change_records_diff(): void
    {
        $before = ['password_min_length' => '8', 'password_require_symbol' => '0'];
        $after = ['password_min_length' => '12', 'password_require_symbol' => '1'];

        $log = Audit::logBulkSettingsChange('security.password', $before, $after);

        $this->assertNotNull($log);
        $this->assertEquals(AuditLog::CATEGORY_SECURITY, $log->category);
        $this->assertEquals(AuditLog::ACTION_SETTINGS_UPDATED, $log->action);
        $this->assertEquals('security.password', $log->target_label);
        $this->assertEquals(AuditLog::SEVERITY_WARNING, $log->severity);
        $this->assertEquals(2, $log->context['changed_count']);
        $this->assertContains('password_min_length', $log->context['changed_keys']);
        $this->assertEquals('8', $log->context['diff']['password_min_length']['from']);
        $this->assertEquals('12', $log->context['diff']['password_min_length']['to']);
    }

    public function test_log_bulk_settings_change_skips_when_no_changes(): void
    {
        $before = ['key1' => 'value1', 'key2' => 'value2'];
        $after = ['key1' => 'value1', 'key2' => 'value2'];

        $log = Audit::logBulkSettingsChange('security.test', $before, $after);

        $this->assertNull($log);
        $this->assertDatabaseMissing('audit_logs', ['target_label' => 'security.test']);
    }

    public function test_log_bulk_settings_change_masks_sensitive_keys(): void
    {
        $before = ['site_key' => 'old-key', 'secret_key' => 'old-secret'];
        $after = ['site_key' => 'new-key', 'secret_key' => 'new-secret'];

        $log = Audit::logBulkSettingsChange(
            'security.captcha',
            $before,
            $after,
            null,
            ['secret_key']
        );

        $this->assertNotNull($log);
        $this->assertEquals('old-key', $log->context['diff']['site_key']['from']);
        $this->assertEquals('new-key', $log->context['diff']['site_key']['to']);
        $this->assertEquals('********', $log->context['diff']['secret_key']['from']);
        $this->assertEquals('********', $log->context['diff']['secret_key']['to']);
    }
}
