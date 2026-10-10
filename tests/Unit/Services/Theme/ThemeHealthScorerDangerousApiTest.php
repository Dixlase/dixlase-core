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

namespace Tests\Unit\Services\Theme;

use App\DTO\Plugin\HealthIssue;
use App\DTO\Plugin\HealthScoreResult;
use App\Enums\PluginHealthStatus;
use App\Models\ThemeAudit;
use App\Services\Theme\ThemeHealthScorer;
use App\Services\Theme\ThemePermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Dangerous API calls count in the theme health score whether or not the
 * manifest declares them: undeclared use is critical, declared use is a
 * warning-level deduction.
 *
 * The slug has no directory under themes/, so the manifest-based evaluator
 * (API version) stays silent and only the permissions and audit rows
 * created here drive the score.
 */
class ThemeHealthScorerDangerousApiTest extends TestCase
{
    use RefreshDatabase;

    private const SLUG = 'dangerous-api-scoring-fixture';

    protected ThemePermissionService|Mockery\MockInterface $permissionService;

    protected ThemeHealthScorer $scorer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->permissionService = Mockery::mock(ThemePermissionService::class);
        $this->permissionService->shouldReceive('getSignatureInfo')->with(self::SLUG)->andReturn(['status' => 'valid'])->byDefault();
        $this->permissionService->shouldReceive('getOptionalPermissions')->with(self::SLUG)->andReturn([])->byDefault();

        $this->scorer = new ThemeHealthScorer($this->permissionService);
    }

    public function test_declared_dangerous_api_in_use_is_deducted_as_a_warning(): void
    {
        $result = $this->score(['dangerous_api' => ['exec' => true]], []);

        $declared = $this->issuesOfType($result, 'dangerous_api_declared');
        $this->assertCount(1, $declared);
        $this->assertSame('warning', $declared[0]->severity);
        $this->assertSame(-12, $declared[0]->deduction);
        $this->assertSame([], $this->issuesOfType($result, 'dangerous_api_exec'));
        $this->assertSame(88, $result->score);
        $this->assertSame(PluginHealthStatus::Advisory, $result->status);
        $this->assertFalse($result->hasCriticalIssue);
    }

    public function test_undeclared_dangerous_api_stays_critical(): void
    {
        $result = $this->score(
            ['assets' => ['custom_css' => true]],
            [[
                'type' => 'undeclared_usage',
                'permission' => 'dangerous_api.exec',
                'evidence' => [['file' => 'app/Runner.php', 'line' => 12]],
            ]],
        );

        $critical = $this->issuesOfType($result, 'dangerous_api_exec');
        $this->assertCount(1, $critical);
        $this->assertSame('critical', $critical[0]->severity);
        $this->assertSame([], $this->issuesOfType($result, 'dangerous_api_declared'));
        $this->assertTrue($result->hasCriticalIssue);
        $this->assertSame(PluginHealthStatus::NeedsAttention, $result->status);
    }

    public function test_theme_without_dangerous_api_is_unaffected(): void
    {
        $result = $this->score(['assets' => ['custom_css' => true]], []);

        $this->assertSame([], $this->issuesOfType($result, 'dangerous_api_declared'));
        $this->assertSame([], $this->issuesOfType($result, 'dangerous_api_exec'));
        $this->assertSame(100, $result->score);
        $this->assertSame(PluginHealthStatus::Healthy, $result->status);
    }

    /**
     * Store an audit row with the given mismatches and score the fixture.
     *
     * @param  array<string, mixed>  $permissions
     * @param  array<int, array<string, mixed>>  $mismatches
     */
    private function score(array $permissions, array $mismatches): HealthScoreResult
    {
        $this->permissionService->shouldReceive('getPermissions')->with(self::SLUG)->andReturn($permissions);

        ThemeAudit::saveAuditResult(self::SLUG, [
            'has_mismatches' => $mismatches !== [],
            'mismatches' => $mismatches,
            'matches_count' => 0,
            'total_checked' => 5,
        ]);

        return $this->scorer->calculate(self::SLUG);
    }

    /**
     * @return list<HealthIssue>
     */
    private function issuesOfType(HealthScoreResult $result, string $type): array
    {
        return array_values(array_filter(
            $result->issues,
            fn (HealthIssue $issue) => $issue->type === $type,
        ));
    }
}
