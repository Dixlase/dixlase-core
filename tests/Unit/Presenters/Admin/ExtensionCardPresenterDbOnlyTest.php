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

namespace Tests\Unit\Presenters\Admin;

use App\DTO\Plugin\HealthScoreResult;
use App\Enums\PluginHealthStatus;
use App\Presenters\Admin\ExtensionCardPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Phase A: presenter must source health/owned-tables data from plugin_audits cache,
 * never re-running live evaluators. These tests cover the helpers that decode the
 * persisted audit row back into the shape consumed by the card view.
 */
class ExtensionCardPresenterDbOnlyTest extends TestCase
{
    use RefreshDatabase;

    private function callBuildHealthResultFromAudit(?int $score, ?string $status, array $issuesRaw): HealthScoreResult
    {
        $method = new ReflectionMethod(ExtensionCardPresenter::class, 'buildHealthResultFromAudit');

        return $method->invoke(null, $score, $status, $issuesRaw);
    }

    public function test_synthesizes_health_result_from_persisted_audit(): void
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
                'type' => 'csp_inline_required',
                'severity' => 'critical',
                'description' => 'Inline script required',
                'evidence' => [],
                'deduction' => -30,
            ],
        ];

        $result = $this->callBuildHealthResultFromAudit(60, 'needs_attention', $issues);

        $this->assertSame(60, $result->score);
        $this->assertSame(PluginHealthStatus::NeedsAttention, $result->status);
        $this->assertCount(2, $result->issues);
        $this->assertTrue($result->hasCriticalIssue, 'critical severity entry should set hasCriticalIssue');
    }

    public function test_falls_back_to_not_verified_when_audit_missing(): void
    {
        $result = $this->callBuildHealthResultFromAudit(null, null, []);

        $this->assertSame(0, $result->score);
        $this->assertSame(PluginHealthStatus::NotVerified, $result->status);
        $this->assertSame([], $result->issues);
        $this->assertFalse($result->hasCriticalIssue);
    }

    public function test_unknown_status_string_falls_back_to_not_verified(): void
    {
        $result = $this->callBuildHealthResultFromAudit(50, 'bogus_status', []);

        $this->assertSame(50, $result->score);
        $this->assertSame(PluginHealthStatus::NotVerified, $result->status);
    }

    public function test_owned_tables_data_reads_from_audit_payload(): void
    {
        // theme/plugin directory not relevant; we pass a dummy directory and rely on
        // the audit array path. The plugin.json read is tolerant of missing files.
        $result = ExtensionCardPresenter::buildOwnedTablesData(
            'plugin',
            '__nonexistent_directory__',
            ['owned_tables' => ['plg_test_pages', 'plg_test_settings']]
        );

        $this->assertSame(['plg_test_pages', 'plg_test_settings'], $result['tables']);
        $this->assertSame('detected', $result['tables_source']);
        $this->assertTrue($result['has_migrations']);
        $this->assertSame([], $result['writes_to_other_plugin_tables']);
    }

    public function test_owned_tables_data_returns_empty_when_audit_has_none(): void
    {
        $result = ExtensionCardPresenter::buildOwnedTablesData(
            'plugin',
            '__nonexistent_directory__',
            []
        );

        $this->assertSame([], $result['tables']);
        $this->assertSame('none', $result['tables_source']);
        $this->assertFalse($result['has_migrations']);
    }

    public function test_scan_freshness_returns_unscanned_when_audited_at_is_null(): void
    {
        \App\Services\SecuritySettingsRegistry::set('extension_audit_max_age_days', 30);

        $result = ExtensionCardPresenter::buildScanFreshness(['audited_at' => null]);

        $this->assertSame('unscanned', $result['state']);
        $this->assertSame(30, $result['maxAgeDays']);
        $this->assertNull($result['ageDays']);
    }

    public function test_scan_freshness_returns_files_changed_when_flag_set(): void
    {
        \App\Services\SecuritySettingsRegistry::set('extension_audit_max_age_days', 30);

        $result = ExtensionCardPresenter::buildScanFreshness(
            ['audited_at' => now()->subDays(1)->toDateTimeString()],
            true,
        );

        $this->assertSame('files_changed', $result['state']);
    }

    public function test_scan_freshness_returns_expired_when_older_than_max_age(): void
    {
        \App\Services\SecuritySettingsRegistry::set('extension_audit_max_age_days', 30);

        $result = ExtensionCardPresenter::buildScanFreshness([
            'audited_at' => now()->subDays(45)->toDateTimeString(),
        ]);

        $this->assertSame('expired', $result['state']);
        $this->assertSame(30, $result['maxAgeDays']);
        $this->assertGreaterThanOrEqual(45, $result['ageDays']);
    }

    public function test_scan_freshness_returns_fresh_when_recent(): void
    {
        \App\Services\SecuritySettingsRegistry::set('extension_audit_max_age_days', 30);

        $result = ExtensionCardPresenter::buildScanFreshness([
            'audited_at' => now()->subDays(2)->toDateTimeString(),
        ]);

        $this->assertSame('fresh', $result['state']);
    }

    public function test_scan_freshness_uses_custom_max_age_setting(): void
    {
        \App\Services\SecuritySettingsRegistry::set('extension_audit_max_age_days', 7);

        $result = ExtensionCardPresenter::buildScanFreshness([
            'audited_at' => now()->subDays(10)->toDateTimeString(),
        ]);

        $this->assertSame('expired', $result['state']);
        $this->assertSame(7, $result['maxAgeDays']);
    }

    public function test_scan_freshness_files_changed_takes_precedence_over_expired(): void
    {
        \App\Services\SecuritySettingsRegistry::set('extension_audit_max_age_days', 30);

        $result = ExtensionCardPresenter::buildScanFreshness(
            ['audited_at' => now()->subDays(45)->toDateTimeString()],
            true,
        );

        $this->assertSame('files_changed', $result['state']);
    }
}
