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

    /**
     * health_issues 列が JSON として保存・復元されるテスト
     */
    public function test_save_audit_result_stores_health_issues(): void
    {
        $issues = [
            [
                'type' => 'signature_unsigned',
                'severity' => 'warning',
                'description' => 'No signature',
                'evidence' => [],
                'deduction' => -10,
            ],
            [
                'type' => 'permission_undefined',
                'severity' => 'warning',
                'description' => 'permissions セクションが未定義です。',
                'evidence' => [],
                'deduction' => -10,
            ],
        ];

        PluginAudit::saveAuditResult('test-plugin', [
            'health_score' => 80,
            'health_status' => 'advisory',
            'health_issues' => $issues,
        ]);

        $fresh = PluginAudit::getBySlug('test-plugin');
        $this->assertIsArray($fresh->health_issues);
        $this->assertCount(2, $fresh->health_issues);
        $this->assertSame('signature_unsigned', $fresh->health_issues[0]['type']);
        $this->assertSame(-10, $fresh->health_issues[0]['deduction']);
    }

    /**
     * owned_tables 列が JSON として保存・復元されるテスト
     */
    public function test_save_audit_result_stores_owned_tables(): void
    {
        PluginAudit::saveAuditResult('test-plugin', [
            'health_score' => 100,
            'health_status' => 'healthy',
            'owned_tables' => ['plg_test_pages', 'plg_test_settings'],
        ]);

        $fresh = PluginAudit::getBySlug('test-plugin');
        $this->assertSame(['plg_test_pages', 'plg_test_settings'], $fresh->owned_tables);

        $array = $fresh->toAuditArray();
        $this->assertArrayHasKey('owned_tables', $array);
        $this->assertSame(['plg_test_pages', 'plg_test_settings'], $array['owned_tables']);
    }

    /**
     * health_issues / owned_tables 未指定時は空配列にフォールバックするテスト
     */
    public function test_save_audit_result_defaults_health_issues_and_owned_tables_to_empty(): void
    {
        PluginAudit::saveAuditResult('test-plugin', [
            'health_score' => 100,
        ]);

        $fresh = PluginAudit::getBySlug('test-plugin');
        $this->assertSame([], $fresh->health_issues);
        $this->assertSame([], $fresh->owned_tables);
    }

    /**
     * toAuditArray() が health_issues / owned_tables を含むテスト
     */
    public function test_to_audit_array_includes_health_issues_and_owned_tables(): void
    {
        $audit = PluginAudit::saveAuditResult('test-plugin', [
            'health_score' => 90,
            'health_issues' => [['type' => 'foo', 'severity' => 'info', 'description' => '', 'evidence' => [], 'deduction' => 0]],
            'owned_tables' => ['plg_a', 'plg_b'],
        ]);

        $array = $audit->toAuditArray();

        $this->assertArrayHasKey('health_issues', $array);
        $this->assertArrayHasKey('owned_tables', $array);
        $this->assertCount(1, $array['health_issues']);
        $this->assertSame(['plg_a', 'plg_b'], $array['owned_tables']);
    }
}
