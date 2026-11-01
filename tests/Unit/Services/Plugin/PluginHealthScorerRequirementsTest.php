<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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

declare(strict_types=1);

namespace Tests\Unit\Services\Plugin;

use App\DTO\Plugin\HealthScoreResult;
use App\Enums\PluginHealthStatus;
use App\Models\PluginAudit;
use App\Services\Licensing\LicenseValidator;
use App\Services\Plugin\PluginHealthScorer;
use App\Services\Plugin\PluginPermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * An extension's requires.dixlase / requires.php are checked where the
 * extension is used, not only where an install or update command decided it.
 *
 * dixlase-core#473 put the check in EnforcesExtensionRequirements, which only
 * the install and update commands use. An extension placed as files -- a git
 * checkout, an rsync, a restored backup -- reached a site that did not meet
 * its requirements with nothing to say so, and the boot-time check reads
 * requires.dixlase_api, a different field.
 *
 * The manifest is read through readManifest(), which the stub below replaces,
 * so these run without a directory under plugins/.
 */
class PluginHealthScorerRequirementsTest extends TestCase
{
    use RefreshDatabase;

    private const SLUG = 'requirement-scoring-fixture';

    private PluginHealthScorer $scorer;

    protected function setUp(): void
    {
        parent::setUp();

        $permissions = Mockery::mock(PluginPermissionService::class);
        $permissions->shouldReceive('getSignatureInfo')->andReturn(['status' => 'valid'])->byDefault();
        $permissions->shouldReceive('getOptionalPermissions')->andReturn([])->byDefault();
        $permissions->shouldReceive('getPermissions')->andReturn([])->byDefault();

        ManifestStubScorer::$manifest = null;
        $this->scorer = new ManifestStubScorer($permissions, new LicenseValidator());
    }

    private function scoreWith(?array $requires): HealthScoreResult
    {
        ManifestStubScorer::$manifest = $requires === null ? null : ['requires' => $requires];

        // The scorer returns NotVerified without an audit row, so the
        // requirement evaluator would never be reached.
        PluginAudit::saveAuditResult(self::SLUG, [
            'has_mismatches' => false,
            'mismatches' => [],
            'matches_count' => 0,
            'total_checked' => 5,
        ]);

        return $this->scorer->calculate(self::SLUG);
    }

    /**
     * @return array<\App\DTO\Plugin\HealthIssue>
     */
    private function issuesOfType(HealthScoreResult $result, string $type): array
    {
        return array_values(array_filter($result->issues, fn ($i) => $i->type === $type));
    }

    public function test_a_core_release_the_site_does_not_meet_is_critical(): void
    {
        $result = $this->scoreWith(['dixlase' => '^9.9.9']);

        $issues = $this->issuesOfType($result, 'unmet_requirement');
        $this->assertCount(1, $issues);
        $this->assertSame('critical', $issues[0]->severity);
        $this->assertSame(-40, $issues[0]->deduction);
        $this->assertTrue($result->hasCriticalIssue, 'An extension that says it will not run here is a critical finding.');
    }

    /**
     * The message is what an operator reads in the admin panel, so it has to
     * name both sides of the comparison.
     */
    public function test_the_message_names_what_was_required_and_what_is_here(): void
    {
        $issues = $this->issuesOfType($this->scoreWith(['dixlase' => '^9.9.9']), 'unmet_requirement');

        $this->assertStringContainsString('^9.9.9', $issues[0]->description);
        $this->assertStringContainsString('dixlase', $issues[0]->description);
    }

    public function test_a_php_requirement_the_site_does_not_meet_is_reported(): void
    {
        $issues = $this->issuesOfType($this->scoreWith(['php' => '>=99.0']), 'unmet_requirement');

        $this->assertCount(1, $issues);
        $this->assertSame('critical', $issues[0]->severity);
    }

    public function test_a_requirement_the_site_meets_is_not_reported(): void
    {
        $result = $this->scoreWith(['dixlase' => '>=0.1.0', 'php' => '>=8.3']);

        $this->assertSame([], $this->issuesOfType($result, 'unmet_requirement'));
        $this->assertFalse($result->hasCriticalIssue);
    }

    /**
     * A constraint nobody can parse is the author's mistake, not a statement
     * that the site is wrong. Reported, but not as a refusal.
     */
    public function test_an_unparseable_constraint_is_a_warning_not_a_critical(): void
    {
        $result = $this->scoreWith(['dixlase' => 'not-a-constraint']);

        $malformed = $this->issuesOfType($result, 'malformed_requirement');
        $this->assertCount(1, $malformed);
        $this->assertSame('warning', $malformed[0]->severity);
        $this->assertSame(-10, $malformed[0]->deduction);
        $this->assertFalse($result->hasCriticalIssue);
    }

    public function test_a_manifest_that_declares_nothing_is_not_penalised(): void
    {
        $result = $this->scoreWith([]);

        $this->assertSame([], $this->issuesOfType($result, 'unmet_requirement'));
        $this->assertSame([], $this->issuesOfType($result, 'malformed_requirement'));
    }

    public function test_a_missing_manifest_is_not_penalised(): void
    {
        $result = $this->scoreWith(null);

        $this->assertSame([], $this->issuesOfType($result, 'unmet_requirement'));
    }

    /**
     * The status an operator sees has to reflect it, or the finding is only
     * in a list nobody opens.
     */
    public function test_the_status_reflects_an_unmet_requirement(): void
    {
        $result = $this->scoreWith(['dixlase' => '^9.9.9']);

        $this->assertNotSame(PluginHealthStatus::Healthy, $result->status);
    }

    public function test_unmet_requirement_counts_as_a_critical_issue_type(): void
    {
        $this->assertTrue(PluginHealthStatus::isCriticalIssue('unmet_requirement'));
        $this->assertFalse(PluginHealthStatus::isCriticalIssue('malformed_requirement'));
    }
}

/**
 * Reads its manifest from a property instead of plugins/<name>/plugin.json,
 * so the tests need no directory in the working tree.
 */
class ManifestStubScorer extends PluginHealthScorer
{
    /** @var array<string, mixed>|null */
    public static ?array $manifest = null;

    protected function readManifest(string $pluginSlug): ?array
    {
        return static::$manifest;
    }
}
