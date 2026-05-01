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

use App\Models\ThemeAudit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase C: theme_audits parity with plugin_audits — health_score / health_status /
 * health_issues / owned_tables / files_hash columns must round-trip.
 */
class ThemeAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_save_audit_result_stores_health_fields(): void
    {
        $audit = ThemeAudit::saveAuditResult('test-theme', [
            'has_mismatches' => false,
            'health_score' => 85,
            'health_status' => 'advisory',
        ]);

        $this->assertDatabaseHas('theme_audits', [
            'theme_slug' => 'test-theme',
            'health_score' => 85,
            'health_status' => 'advisory',
        ]);

        $this->assertSame(85, $audit->health_score);
        $this->assertSame('advisory', $audit->health_status);
    }

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
        ];

        ThemeAudit::saveAuditResult('test-theme', [
            'health_score' => 90,
            'health_status' => 'healthy',
            'health_issues' => $issues,
        ]);

        $fresh = ThemeAudit::getBySlug('test-theme');
        $this->assertIsArray($fresh->health_issues);
        $this->assertCount(1, $fresh->health_issues);
        $this->assertSame('signature_unsigned', $fresh->health_issues[0]['type']);
    }

    public function test_save_audit_result_stores_owned_tables(): void
    {
        ThemeAudit::saveAuditResult('test-theme', [
            'health_score' => 100,
            'owned_tables' => ['thm_test_settings'],
        ]);

        $fresh = ThemeAudit::getBySlug('test-theme');
        $this->assertSame(['thm_test_settings'], $fresh->owned_tables);
    }

    public function test_save_audit_result_stores_files_hash(): void
    {
        ThemeAudit::saveAuditResult('test-theme', [
            'files_hash' => 'abc123def456',
        ]);

        $fresh = ThemeAudit::getBySlug('test-theme');
        $this->assertSame('abc123def456', $fresh->files_hash);
    }

    public function test_save_audit_result_defaults_health_issues_and_owned_tables_to_empty(): void
    {
        ThemeAudit::saveAuditResult('test-theme', [
            'health_score' => 100,
        ]);

        $fresh = ThemeAudit::getBySlug('test-theme');
        $this->assertSame([], $fresh->health_issues);
        $this->assertSame([], $fresh->owned_tables);
    }

    public function test_to_audit_array_includes_new_fields(): void
    {
        $audit = ThemeAudit::saveAuditResult('test-theme', [
            'health_score' => 90,
            'health_status' => 'healthy',
            'health_issues' => [['type' => 'foo', 'severity' => 'info', 'description' => '', 'evidence' => [], 'deduction' => 0]],
            'owned_tables' => ['thm_a', 'thm_b'],
            'files_hash' => 'xyz',
        ]);

        $array = $audit->toAuditArray();

        $this->assertArrayHasKey('health_score', $array);
        $this->assertArrayHasKey('health_status', $array);
        $this->assertArrayHasKey('health_issues', $array);
        $this->assertArrayHasKey('owned_tables', $array);
        $this->assertArrayHasKey('files_hash', $array);
        $this->assertSame(['thm_a', 'thm_b'], $array['owned_tables']);
        $this->assertSame('xyz', $array['files_hash']);
    }
}
