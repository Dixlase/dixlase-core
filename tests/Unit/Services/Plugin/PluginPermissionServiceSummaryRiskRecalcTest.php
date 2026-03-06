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

namespace Tests\Unit\Services\Plugin;

use App\Models\PluginAudit;
use App\Services\Plugin\PluginPermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * getSummary() が常に現在のスコアリングルールでリスクレベルを再計算することを確認するテスト
 *
 * Issue 4: スコアリングルール変更後に監査DBの古い risk_reasons が表示される問題の修正を検証
 */
class PluginPermissionServiceSummaryRiskRecalcTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 低リスクの宣言権限のみの場合、監査DBに古い高リスク結果があっても
     * getSummary() は現在のルールで再計算した低リスクを返すことを確認
     */
    public function test_summary_recalculates_risk_instead_of_using_stale_db_cache(): void
    {
        $pluginSlug = 'test-recalc-plugin';

        // 低リスクの権限を返すモックを作成
        $lowRiskPermissions = [
            'database' => ['own_tables' => true, 'core_tables_read' => [], 'core_tables_write' => []],
            'storage' => ['own_directory' => true, 'public_uploads' => false, 'temp_files' => false],
            'settings' => ['read_core' => false, 'write_own' => true],
            'members' => ['read' => true, 'write' => false, 'create' => false, 'delete' => false],
            'mail' => ['send' => false, 'bulk_send' => false],
            'content' => ['read_other_plugins' => [], 'write_other_plugins' => []],
            'system' => [
                'register_shortcodes' => false,
                'register_middleware' => false,
                'register_commands' => false,
                'register_blade_directives' => false,
                'modify_routes' => false,
            ],
        ];

        // 監査DBに古い「高リスク」結果を保存（スコアリングルール変更前の古いデータを再現）
        PluginAudit::saveAuditResult($pluginSlug, [
            'risk_level' => 'high',
            'risk_reasons' => [
                ['key' => 'members.write', 'severity' => 'high', 'score' => 3],
                ['key' => 'members.delete', 'severity' => 'high', 'score' => 4],
            ],
            'mismatches' => [],
            'has_mismatches' => false,
            'audited_at' => now()->toIso8601String(),
        ]);

        // getSummary() が現在のルールで再計算することを確認するため、
        // getPermissions() と getSignatureInfo() をモック
        $service = $this->createPartialMock(PluginPermissionService::class, [
            'getPermissions',
            'getSignatureInfo',
        ]);

        $service->method('getPermissions')
            ->with($pluginSlug)
            ->willReturn($lowRiskPermissions);

        $service->method('getSignatureInfo')
            ->with($pluginSlug)
            ->willReturn(['status' => 'unsigned', 'type' => null, 'signed_by' => null, 'signed_at' => null, 'key_id' => null]);

        $summary = $service->getSummary($pluginSlug);

        // 監査DBには 'high' が保存されているが、現在のルールで再計算すると 'low' になるはず
        $this->assertEquals('low', $summary['risk_level']);
        $this->assertEquals(0, $summary['risk_score']);
        $this->assertEmpty($summary['risk_reasons']);
    }

    /**
     * 監査DBの mismatches が再計算に反映されることを確認
     */
    public function test_summary_includes_mismatches_in_recalculation(): void
    {
        $pluginSlug = 'test-mismatch-recalc-plugin';

        $lowRiskPermissions = [
            'database' => ['own_tables' => true, 'core_tables_read' => [], 'core_tables_write' => []],
            'storage' => ['own_directory' => false, 'public_uploads' => false, 'temp_files' => false],
            'settings' => ['read_core' => false, 'write_own' => true],
            'members' => ['read' => false, 'write' => false, 'create' => false, 'delete' => false],
            'mail' => ['send' => false, 'bulk_send' => false],
            'content' => ['read_other_plugins' => [], 'write_other_plugins' => []],
            'system' => [
                'register_shortcodes' => false,
                'register_middleware' => false,
                'register_commands' => false,
                'register_blade_directives' => false,
                'modify_routes' => false,
            ],
        ];

        // 未宣言使用の不一致を2件含む監査結果を保存
        PluginAudit::saveAuditResult($pluginSlug, [
            'risk_level' => 'low',
            'risk_reasons' => [],
            'mismatches' => [
                ['type' => 'undeclared_usage', 'permission' => 'members.read', 'details' => 'found usage'],
                ['type' => 'undeclared_usage', 'permission' => 'mail.send', 'details' => 'found usage'],
            ],
            'has_mismatches' => true,
            'audited_at' => now()->toIso8601String(),
        ]);

        $service = $this->createPartialMock(PluginPermissionService::class, [
            'getPermissions',
            'getSignatureInfo',
        ]);

        $service->method('getPermissions')
            ->with($pluginSlug)
            ->willReturn($lowRiskPermissions);

        $service->method('getSignatureInfo')
            ->with($pluginSlug)
            ->willReturn(['status' => 'unsigned', 'type' => null, 'signed_by' => null, 'signed_at' => null, 'key_id' => null]);

        $summary = $service->getSummary($pluginSlug);

        // 2件の undeclared_usage 不一致 → +4 スコア → medium
        $this->assertEquals('medium', $summary['risk_level']);
        $this->assertEquals(4, $summary['risk_score']);
        $this->assertNotEmpty($summary['risk_reasons']);

        // 不一致理由が含まれることを確認
        $mismatchReasons = array_filter($summary['risk_reasons'], fn ($r) => $r['key'] === 'mismatch.undeclared_usage');
        $this->assertCount(1, $mismatchReasons);
    }

    /**
     * has_permissions が true で返ることを確認
     */
    public function test_summary_has_permissions_true_when_permissions_exist(): void
    {
        $pluginSlug = 'test-has-permissions-plugin';

        $permissions = [
            'database' => ['own_tables' => true, 'core_tables_read' => [], 'core_tables_write' => []],
            'storage' => ['own_directory' => false, 'public_uploads' => false, 'temp_files' => false],
            'settings' => ['read_core' => false, 'write_own' => false],
            'members' => ['read' => false, 'write' => false, 'create' => false, 'delete' => false],
            'mail' => ['send' => false, 'bulk_send' => false],
            'content' => ['read_other_plugins' => [], 'write_other_plugins' => []],
            'system' => [
                'register_shortcodes' => false,
                'register_middleware' => false,
                'register_commands' => false,
                'register_blade_directives' => false,
                'modify_routes' => false,
            ],
        ];

        $service = $this->createPartialMock(PluginPermissionService::class, [
            'getPermissions',
            'getSignatureInfo',
        ]);

        $service->method('getPermissions')
            ->with($pluginSlug)
            ->willReturn($permissions);

        $service->method('getSignatureInfo')
            ->with($pluginSlug)
            ->willReturn(['status' => 'unsigned', 'type' => null, 'signed_by' => null, 'signed_at' => null, 'key_id' => null]);

        $summary = $service->getSummary($pluginSlug);

        $this->assertTrue($summary['has_permissions']);
    }

    /**
     * permissions が null の場合、has_permissions が false で返ることを確認
     */
    public function test_summary_has_permissions_false_when_no_permissions(): void
    {
        $pluginSlug = 'test-no-permissions-plugin';

        $service = $this->createPartialMock(PluginPermissionService::class, [
            'getPermissions',
            'getSignatureInfo',
        ]);

        $service->method('getPermissions')
            ->with($pluginSlug)
            ->willReturn(null);

        $service->method('getSignatureInfo')
            ->with($pluginSlug)
            ->willReturn(['status' => 'unsigned', 'type' => null, 'signed_by' => null, 'signed_at' => null, 'key_id' => null]);

        $summary = $service->getSummary($pluginSlug);

        $this->assertFalse($summary['has_permissions']);
    }
}
