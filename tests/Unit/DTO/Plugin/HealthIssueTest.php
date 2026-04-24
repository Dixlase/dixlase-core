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
use Tests\TestCase;

class HealthIssueTest extends TestCase
{
    /**
     * 基本的なDTOの生成テスト
     */
    public function test_can_create_health_issue(): void
    {
        $issue = new HealthIssue(
            type: 'signature_unsigned',
            severity: 'info',
            description: '署名がありません。',
            deduction: -5,
        );

        $this->assertEquals('signature_unsigned', $issue->type);
        $this->assertEquals('info', $issue->severity);
        $this->assertEquals('署名がありません。', $issue->description);
        $this->assertEquals([], $issue->evidence);
        $this->assertEquals(-5, $issue->deduction);
    }

    /**
     * evidenceを含むDTOの生成テスト
     */
    public function test_can_create_with_evidence(): void
    {
        $evidence = [
            ['file' => 'src/Service.php', 'line' => 10, 'match' => 'exec()'],
        ];

        $issue = new HealthIssue(
            type: 'dangerous_api_exec',
            severity: 'critical',
            description: '危険なAPIが検出されました',
            evidence: $evidence,
            deduction: -30,
        );

        $this->assertCount(1, $issue->evidence);
        $this->assertEquals('src/Service.php', $issue->evidence[0]['file']);
    }

    /**
     * isCritical()がcriticalの場合trueを返すテスト
     */
    public function test_is_critical_returns_true_for_critical_severity(): void
    {
        $issue = new HealthIssue(
            type: 'signature_invalid',
            severity: 'critical',
            description: '署名が無効です。',
            deduction: -50,
        );

        $this->assertTrue($issue->isCritical());
    }

    /**
     * isCritical()がcritical以外の場合falseを返すテスト
     */
    public function test_is_critical_returns_false_for_non_critical_severity(): void
    {
        $warningIssue = new HealthIssue(
            type: 'permission_undefined',
            severity: 'warning',
            description: '権限未定義',
            deduction: -10,
        );

        $infoIssue = new HealthIssue(
            type: 'scan_outdated',
            severity: 'info',
            description: 'スキャンが古い',
            deduction: -5,
        );

        $this->assertFalse($warningIssue->isCritical());
        $this->assertFalse($infoIssue->isCritical());
    }

    /**
     * jsonSerialize()が正しい構造を返すテスト
     */
    public function test_json_serialize_returns_correct_structure(): void
    {
        $issue = new HealthIssue(
            type: 'signature_unsigned',
            severity: 'info',
            description: '署名がありません。',
            evidence: [['file' => 'test.php']],
            deduction: -5,
        );

        $json = $issue->jsonSerialize();

        $this->assertArrayHasKey('type', $json);
        $this->assertArrayHasKey('severity', $json);
        $this->assertArrayHasKey('description', $json);
        $this->assertArrayHasKey('evidence', $json);
        $this->assertArrayHasKey('deduction', $json);
        $this->assertEquals('signature_unsigned', $json['type']);
        $this->assertEquals(-5, $json['deduction']);
    }

    /**
     * toArray()がjsonSerialize()と同一の結果を返すテスト
     */
    public function test_to_array_matches_json_serialize(): void
    {
        $issue = new HealthIssue(
            type: 'csp_inline_js_required',
            severity: 'warning',
            description: 'インラインJS必要',
            deduction: -10,
        );

        $this->assertEquals($issue->jsonSerialize(), $issue->toArray());
    }

    /**
     * json_encode()が正常に動作するテスト
     */
    public function test_json_encode_works(): void
    {
        $issue = new HealthIssue(
            type: 'scan_not_performed',
            severity: 'warning',
            description: '監査スキャンが実行されていません。',
            deduction: -10,
        );

        $encoded = json_encode($issue);
        $this->assertIsString($encoded);

        $decoded = json_decode($encoded, true);
        $this->assertEquals('scan_not_performed', $decoded['type']);
        $this->assertEquals(-10, $decoded['deduction']);
    }
}
