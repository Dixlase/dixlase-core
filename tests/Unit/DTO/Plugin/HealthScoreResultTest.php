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

namespace Tests\Unit\DTO\Plugin;

use App\DTO\Plugin\HealthIssue;
use App\DTO\Plugin\HealthScoreResult;
use App\Enums\PluginHealthStatus;
use Tests\TestCase;

class HealthScoreResultTest extends TestCase
{
    /**
     * 健全なスコア結果の生成テスト
     */
    public function test_can_create_healthy_result(): void
    {
        $result = new HealthScoreResult(
            score: 95,
            status: PluginHealthStatus::Healthy,
        );

        $this->assertEquals(95, $result->score);
        $this->assertEquals(PluginHealthStatus::Healthy, $result->status);
        $this->assertEmpty($result->issues);
        $this->assertFalse($result->hasCriticalIssue);
    }

    /**
     * 問題を含むスコア結果の生成テスト
     */
    public function test_can_create_result_with_issues(): void
    {
        $issues = [
            new HealthIssue(
                type: 'signature_unsigned',
                severity: 'info',
                description: '署名なし',
                deduction: -5,
            ),
            new HealthIssue(
                type: 'permission_undefined',
                severity: 'warning',
                description: '権限未定義',
                deduction: -10,
            ),
        ];

        $result = new HealthScoreResult(
            score: 85,
            status: PluginHealthStatus::Advisory,
            issues: $issues,
        );

        $this->assertEquals(85, $result->score);
        $this->assertEquals(PluginHealthStatus::Advisory, $result->status);
        $this->assertCount(2, $result->issues);
    }

    /**
     * isHealthy()のテスト
     */
    public function test_is_healthy_returns_true_for_healthy_status(): void
    {
        $result = new HealthScoreResult(
            score: 100,
            status: PluginHealthStatus::Healthy,
        );

        $this->assertTrue($result->isHealthy());
    }

    /**
     * isHealthy()が非Healthyでfalseを返すテスト
     */
    public function test_is_healthy_returns_false_for_non_healthy(): void
    {
        $advisory = new HealthScoreResult(score: 80, status: PluginHealthStatus::Advisory);
        $needsAttention = new HealthScoreResult(score: 50, status: PluginHealthStatus::NeedsAttention);
        $notVerified = new HealthScoreResult(score: 0, status: PluginHealthStatus::NotVerified);

        $this->assertFalse($advisory->isHealthy());
        $this->assertFalse($needsAttention->isHealthy());
        $this->assertFalse($notVerified->isHealthy());
    }

    /**
     * needsAttention()のテスト
     */
    public function test_needs_attention_returns_correct_value(): void
    {
        $needsAttention = new HealthScoreResult(score: 50, status: PluginHealthStatus::NeedsAttention);
        $healthy = new HealthScoreResult(score: 100, status: PluginHealthStatus::Healthy);

        $this->assertTrue($needsAttention->needsAttention());
        $this->assertFalse($healthy->needsAttention());
    }

    /**
     * issueCount()のテスト
     */
    public function test_issue_count_returns_correct_count(): void
    {
        $emptyResult = new HealthScoreResult(score: 100, status: PluginHealthStatus::Healthy);
        $this->assertEquals(0, $emptyResult->issueCount());

        $issues = [
            new HealthIssue(type: 'a', severity: 'info', description: 'test1', deduction: -1),
            new HealthIssue(type: 'b', severity: 'warning', description: 'test2', deduction: -5),
            new HealthIssue(type: 'c', severity: 'critical', description: 'test3', deduction: -10),
        ];

        $resultWithIssues = new HealthScoreResult(
            score: 84,
            status: PluginHealthStatus::Advisory,
            issues: $issues,
        );
        $this->assertEquals(3, $resultWithIssues->issueCount());
    }

    /**
     * jsonSerialize()が正しい構造を返すテスト
     */
    public function test_json_serialize_returns_correct_structure(): void
    {
        $issue = new HealthIssue(
            type: 'signature_unsigned',
            severity: 'info',
            description: '署名なし',
            deduction: -5,
        );

        $result = new HealthScoreResult(
            score: 95,
            status: PluginHealthStatus::Healthy,
            issues: [$issue],
            hasCriticalIssue: false,
        );

        $json = $result->jsonSerialize();

        $this->assertArrayHasKey('score', $json);
        $this->assertArrayHasKey('status', $json);
        $this->assertArrayHasKey('issues', $json);
        $this->assertArrayHasKey('has_critical_issue', $json);

        $this->assertEquals(95, $json['score']);
        $this->assertEquals('healthy', $json['status']);
        $this->assertCount(1, $json['issues']);
        $this->assertFalse($json['has_critical_issue']);
    }

    /**
     * toArray()がjsonSerialize()と同一の結果を返すテスト
     */
    public function test_to_array_matches_json_serialize(): void
    {
        $result = new HealthScoreResult(
            score: 70,
            status: PluginHealthStatus::Advisory,
        );

        $this->assertEquals($result->jsonSerialize(), $result->toArray());
    }

    /**
     * 致命的問題フラグのテスト
     */
    public function test_critical_issue_flag(): void
    {
        $result = new HealthScoreResult(
            score: 30,
            status: PluginHealthStatus::NeedsAttention,
            hasCriticalIssue: true,
        );

        $this->assertTrue($result->hasCriticalIssue);
    }

    /**
     * json_encode()が正常に動作するテスト
     */
    public function test_json_encode_works(): void
    {
        $result = new HealthScoreResult(
            score: 85,
            status: PluginHealthStatus::Advisory,
            issues: [
                new HealthIssue(type: 'test', severity: 'info', description: 'test', deduction: -5),
            ],
        );

        $encoded = json_encode($result);
        $this->assertIsString($encoded);

        $decoded = json_decode($encoded, true);
        $this->assertEquals(85, $decoded['score']);
        $this->assertEquals('advisory', $decoded['status']);
        $this->assertCount(1, $decoded['issues']);
    }
}
