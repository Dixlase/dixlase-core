<?php

namespace Tests\Unit\Models;

use App\Models\PluginAudit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PluginAuditTest extends TestCase
{
    use RefreshDatabase;

    /**
     * saveAuditResult()で健全性フィールドが保存されるテスト
     */
    public function test_save_audit_result_stores_health_fields(): void
    {
        $audit = PluginAudit::saveAuditResult('test-plugin', [
            'has_mismatches' => false,
            'mismatches' => [],
            'matches_count' => 5,
            'total_checked' => 10,
            'health_score' => 85,
            'health_status' => 'advisory',
        ]);

        $this->assertDatabaseHas('plugin_audits', [
            'plugin_slug' => 'test-plugin',
            'health_score' => 85,
            'health_status' => 'advisory',
        ]);

        $this->assertEquals(85, $audit->health_score);
        $this->assertEquals('advisory', $audit->health_status);
    }

    /**
     * health_scoreがintegerにキャストされるテスト
     */
    public function test_health_score_is_cast_to_integer(): void
    {
        $audit = PluginAudit::saveAuditResult('test-plugin', [
            'health_score' => 90,
        ]);

        $fresh = PluginAudit::getBySlug('test-plugin');
        $this->assertIsInt($fresh->health_score);
    }

    /**
     * health_scoreがnullの場合のテスト
     */
    public function test_health_score_can_be_null(): void
    {
        $audit = PluginAudit::saveAuditResult('test-plugin', [
            'has_mismatches' => false,
        ]);

        $fresh = PluginAudit::getBySlug('test-plugin');
        $this->assertNull($fresh->health_score);
        $this->assertNull($fresh->health_status);
    }

    /**
     * toAuditArray()に健全性フィールドが含まれるテスト
     */
    public function test_to_audit_array_includes_health_fields(): void
    {
        $audit = PluginAudit::saveAuditResult('test-plugin', [
            'health_score' => 95,
            'health_status' => 'healthy',
        ]);

        $array = $audit->toAuditArray();

        $this->assertArrayHasKey('health_score', $array);
        $this->assertArrayHasKey('health_status', $array);
        $this->assertEquals(95, $array['health_score']);
        $this->assertEquals('healthy', $array['health_status']);
    }

    /**
     * saveAuditResult()がupdateOrCreateで動作するテスト
     */
    public function test_save_audit_result_updates_existing_record(): void
    {
        // 初回保存
        PluginAudit::saveAuditResult('test-plugin', [
            'health_score' => 70,
            'health_status' => 'advisory',
        ]);

        // 更新
        PluginAudit::saveAuditResult('test-plugin', [
            'health_score' => 95,
            'health_status' => 'healthy',
        ]);

        $this->assertDatabaseCount('plugin_audits', 1);

        $audit = PluginAudit::getBySlug('test-plugin');
        $this->assertEquals(95, $audit->health_score);
        $this->assertEquals('healthy', $audit->health_status);
    }

    /**
     * getBySlug()で取得できるテスト
     */
    public function test_get_by_slug_returns_record(): void
    {
        PluginAudit::saveAuditResult('my-plugin', [
            'health_score' => 100,
            'health_status' => 'healthy',
        ]);

        $result = PluginAudit::getBySlug('my-plugin');
        $this->assertNotNull($result);
        $this->assertEquals('my-plugin', $result->plugin_slug);
    }

    /**
     * getBySlug()で存在しないスラッグはnullを返すテスト
     */
    public function test_get_by_slug_returns_null_for_missing(): void
    {
        $result = PluginAudit::getBySlug('nonexistent-plugin');
        $this->assertNull($result);
    }
}
