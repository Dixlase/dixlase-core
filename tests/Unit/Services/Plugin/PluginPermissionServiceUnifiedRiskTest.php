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

use App\Services\Plugin\PluginPermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PluginPermissionServiceUnifiedRiskTest extends TestCase
{
    use RefreshDatabase;

    protected PluginPermissionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PluginPermissionService();
    }

    /**
     * 低リスク権限のみの場合は low を返すテスト
     */
    public function test_unified_risk_level_low_with_no_high_permissions(): void
    {
        $permissions = [
            'database' => ['own_tables' => true, 'core_tables' => []],
            'storage' => ['own_directory' => true, 'public_uploads' => false, 'temp_files' => false],
            'settings' => ['read_core' => false, 'write_own' => true],
            'members' => ['read' => true, 'write' => false, 'create' => false, 'delete' => false],
            'mail' => ['send' => false, 'bulk_send' => false],
            'content' => ['read_other_plugins' => false, 'write_other_plugins' => []],
            'system' => [
                'register_shortcodes' => true,
                'register_middleware' => false,
                'register_commands' => false,
                'register_blade_directives' => false,
                'modify_routes' => false,
            ],
        ];

        $result = $this->service->calculateUnifiedRiskLevel($permissions);

        $this->assertEquals('low', $result['level']);
        $this->assertEquals(0, $result['score']);
        $this->assertEmpty($result['reasons']);
    }

    /**
     * 中リスク権限がある場合は medium を返すテスト
     */
    public function test_unified_risk_level_medium_with_medium_permissions(): void
    {
        $permissions = [
            'database' => ['own_tables' => true, 'core_tables' => []],
            'storage' => ['own_directory' => true, 'public_uploads' => true, 'temp_files' => false],
            'settings' => ['read_core' => false, 'write_own' => true],
            'members' => ['read' => true, 'write' => false, 'create' => false, 'delete' => false],
            'mail' => ['send' => false, 'bulk_send' => false],
            'content' => ['read_other_plugins' => false, 'write_other_plugins' => true],
            'system' => [
                'register_shortcodes' => false,
                'register_middleware' => false,
                'register_commands' => false,
                'register_blade_directives' => false,
                'modify_routes' => false,
            ],
        ];

        $result = $this->service->calculateUnifiedRiskLevel($permissions);

        // storage.public_uploads (+2) + content.write_other_plugins (+2) = 4 → medium
        $this->assertEquals('medium', $result['level']);
        $this->assertEquals(4, $result['score']);
        $this->assertNotEmpty($result['reasons']);
    }

    /**
     * 高リスク権限がある場合は high を返すテスト
     */
    public function test_unified_risk_level_high_with_high_permissions(): void
    {
        $permissions = [
            'database' => ['own_tables' => true, 'core_tables' => []],
            'storage' => ['own_directory' => false, 'public_uploads' => true, 'temp_files' => false],
            'settings' => ['read_core' => false, 'write_own' => false],
            'members' => ['read' => true, 'write' => true, 'create' => true, 'delete' => false],
            'mail' => ['send' => false, 'bulk_send' => false],
            'content' => ['read_other_plugins' => false, 'write_other_plugins' => []],
            'system' => [
                'register_shortcodes' => false,
                'register_middleware' => false,
                'register_commands' => false,
                'register_blade_directives' => false,
                'modify_routes' => false,
            ],
        ];

        $result = $this->service->calculateUnifiedRiskLevel($permissions);

        $this->assertEquals('high', $result['level']);
        // members.write (+3) + members.create (+3) + storage.public_uploads (+2) = 8 >= 7
        $this->assertGreaterThanOrEqual(7, $result['score']);
    }

    /**
     * 未宣言使用の不一致がスコアに加算されるテスト
     */
    public function test_unified_risk_level_includes_mismatch_penalty(): void
    {
        // 低リスク宣言のみ（スコア0）
        $permissions = [
            'database' => ['own_tables' => true, 'core_tables' => []],
            'storage' => ['own_directory' => true, 'public_uploads' => false, 'temp_files' => false],
            'settings' => ['read_core' => false, 'write_own' => true],
            'members' => ['read' => false, 'write' => false, 'create' => false, 'delete' => false],
            'mail' => ['send' => false, 'bulk_send' => false],
            'content' => ['read_other_plugins' => false, 'write_other_plugins' => []],
            'system' => [
                'register_shortcodes' => false,
                'register_middleware' => false,
                'register_commands' => false,
                'register_blade_directives' => false,
                'modify_routes' => false,
            ],
        ];

        // 未宣言使用の不一致が2件（+2 * 2 = +4）
        $mismatches = [
            ['type' => 'undeclared_usage', 'permission' => 'mail.send'],
            ['type' => 'undeclared_usage', 'permission' => 'members.read'],
            ['type' => 'unused_declaration', 'permission' => 'database.own_tables'],
        ];

        $result = $this->service->calculateUnifiedRiskLevel($permissions, $mismatches);

        // undeclared_usage 2件 * 2 = 4 → medium (2 <= 4 < 5)
        $this->assertEquals('medium', $result['level']);
        $this->assertEquals(4, $result['score']);
    }

    /**
     * 未宣言使用のペナルティで high に到達するテスト
     */
    public function test_unified_risk_level_mismatch_penalty_reaches_high(): void
    {
        $permissions = [
            'database' => ['own_tables' => false, 'core_tables' => []],
            'storage' => ['own_directory' => false, 'public_uploads' => false, 'temp_files' => false],
            'settings' => ['read_core' => false, 'write_own' => false],
            'members' => ['read' => false, 'write' => false, 'create' => false, 'delete' => false],
            'mail' => ['send' => false, 'bulk_send' => false],
            'content' => ['read_other_plugins' => false, 'write_other_plugins' => []],
            'system' => [
                'register_shortcodes' => false,
                'register_middleware' => false,
                'register_commands' => false,
                'register_blade_directives' => false,
                'modify_routes' => false,
            ],
        ];

        // 未宣言使用が4件（+2 * 4 = +8 >= 7）
        $mismatches = [
            ['type' => 'undeclared_usage', 'permission' => 'mail.send'],
            ['type' => 'undeclared_usage', 'permission' => 'members.read'],
            ['type' => 'undeclared_usage', 'permission' => 'storage.public_uploads'],
            ['type' => 'undeclared_usage', 'permission' => 'members.write'],
        ];

        $result = $this->service->calculateUnifiedRiskLevel($permissions, $mismatches);

        $this->assertEquals('high', $result['level']);
        $this->assertGreaterThanOrEqual(7, $result['score']);
    }

    /**
     * reasons が統一形式で返されるテスト
     */
    public function test_unified_risk_level_reason_format(): void
    {
        $permissions = [
            'database' => ['own_tables' => true, 'core_tables' => []],
            'storage' => ['own_directory' => false, 'public_uploads' => true, 'temp_files' => false],
            'settings' => ['read_core' => false, 'write_own' => false],
            'members' => ['read' => false, 'write' => false, 'create' => false, 'delete' => false],
            'mail' => ['send' => false, 'bulk_send' => false],
            'content' => ['read_other_plugins' => false, 'write_other_plugins' => []],
            'system' => [
                'register_shortcodes' => false,
                'register_middleware' => false,
                'register_commands' => false,
                'register_blade_directives' => false,
                'modify_routes' => false,
            ],
        ];

        $mismatches = [
            ['type' => 'undeclared_usage', 'permission' => 'mail.send'],
        ];

        $result = $this->service->calculateUnifiedRiskLevel($permissions, $mismatches);

        $this->assertIsArray($result['reasons']);
        $this->assertNotEmpty($result['reasons']);

        // 全ての reason が統一形式であることを確認
        foreach ($result['reasons'] as $reason) {
            $this->assertArrayHasKey('key', $reason);
            $this->assertArrayHasKey('severity', $reason);
            $this->assertArrayHasKey('score', $reason);
            $this->assertContains($reason['severity'], ['low', 'medium', 'high']);
            $this->assertIsInt($reason['score']);
        }

        // mismatch reason が含まれていることを確認
        $mismatchReasons = array_filter($result['reasons'], fn ($r) => $r['key'] === 'mismatch.undeclared_usage');
        $this->assertNotEmpty($mismatchReasons);

        $mismatchReason = array_values($mismatchReasons)[0];
        $this->assertEquals('high', $mismatchReason['severity']);
        $this->assertEquals(2, $mismatchReason['score']);
        $this->assertEquals(1, $mismatchReason['count']);
    }

    /**
     * unused_declaration の不一致はスコアに加算されないテスト
     */
    public function test_unified_risk_level_ignores_unused_declaration(): void
    {
        $permissions = [
            'database' => ['own_tables' => false, 'core_tables' => []],
            'storage' => ['own_directory' => false, 'public_uploads' => false, 'temp_files' => false],
            'settings' => ['read_core' => false, 'write_own' => false],
            'members' => ['read' => false, 'write' => false, 'create' => false, 'delete' => false],
            'mail' => ['send' => false, 'bulk_send' => false],
            'content' => ['read_other_plugins' => false, 'write_other_plugins' => []],
            'system' => [
                'register_shortcodes' => false,
                'register_middleware' => false,
                'register_commands' => false,
                'register_blade_directives' => false,
                'modify_routes' => false,
            ],
        ];

        // unused_declaration のみ
        $mismatches = [
            ['type' => 'unused_declaration', 'permission' => 'database.own_tables'],
            ['type' => 'unused_declaration', 'permission' => 'storage.own_directory'],
        ];

        $result = $this->service->calculateUnifiedRiskLevel($permissions, $mismatches);

        $this->assertEquals('low', $result['level']);
        $this->assertEquals(0, $result['score']);
    }

    /**
     * 不一致なしの場合は宣言ベースのスコアのみを使用するテスト
     */
    public function test_unified_risk_level_without_mismatches(): void
    {
        $permissions = [
            'database' => ['own_tables' => true, 'core_tables' => []],
            'storage' => ['own_directory' => false, 'public_uploads' => true, 'temp_files' => false],
            'settings' => ['read_core' => false, 'write_own' => false],
            'members' => ['read' => false, 'write' => true, 'create' => false, 'delete' => false],
            'mail' => ['send' => false, 'bulk_send' => false],
            'content' => ['read_other_plugins' => false, 'write_other_plugins' => []],
            'system' => [
                'register_shortcodes' => false,
                'register_middleware' => false,
                'register_commands' => false,
                'register_blade_directives' => false,
                'modify_routes' => false,
            ],
        ];

        $resultWithout = $this->service->calculateUnifiedRiskLevel($permissions);
        $resultWith = $this->service->calculateUnifiedRiskLevel($permissions, []);

        // 不一致なしの場合、両方の結果が同一
        $this->assertEquals($resultWithout['level'], $resultWith['level']);
        $this->assertEquals($resultWithout['score'], $resultWith['score']);

        // storage.public_uploads (+2) + members.write (+3) = 5 → medium
        $this->assertEquals('medium', $resultWithout['level']);
        $this->assertEquals(5, $resultWithout['score']);
    }
}
