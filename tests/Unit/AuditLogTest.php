<?php

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
        $member = Member::factory()->create(['name' => 'Test User']);

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
        $member = Member::factory()->create(['name' => 'Target User']);

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
}
