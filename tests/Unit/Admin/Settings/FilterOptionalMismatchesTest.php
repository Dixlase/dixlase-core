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

namespace Tests\Unit\Admin\Settings;

use App\Http\Controllers\Admin\Settings\AdminPluginsSettingsController;
use App\Services\Plugin\PluginPermissionService;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Verify that AdminPluginsSettingsController::filterOptionalMismatches()
 * mirrors PluginHealthScorer's `_optional` filter so the score (which
 * already honours `_optional`) and the UI mismatch list (which previously
 * did not) cannot disagree on the same admin scan panel.
 *
 * See plan/handoff-plugin-audit-false-positives.md Fix B.
 */
class FilterOptionalMismatchesTest extends TestCase
{
    /**
     * Call the protected helper via reflection so we can test it in
     * isolation without going through the full admin HTTP stack.
     *
     * @param  array<string, mixed>  $auditArray
     * @param  array<int, string>  $optional
     * @return array<string, mixed>
     */
    private function callFilter(array $auditArray, array $optional, string $slug = 'test-plugin'): array
    {
        $permissionService = Mockery::mock(PluginPermissionService::class);
        $permissionService->shouldReceive('getOptionalPermissions')
            ->with($slug)
            ->andReturn($optional);

        $this->app->instance(PluginPermissionService::class, $permissionService);

        $controller = $this->app->make(AdminPluginsSettingsController::class);
        $ref = new ReflectionMethod($controller, 'filterOptionalMismatches');
        $ref->setAccessible(true);

        return $ref->invoke($controller, $slug, $auditArray);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_passes_through_when_optional_list_is_empty(): void
    {
        $audit = [
            'has_mismatches' => true,
            'mismatches' => [
                ['type' => 'unused_declaration', 'permission' => 'database.core_tables_read'],
            ],
        ];

        $filtered = $this->callFilter($audit, []);

        $this->assertCount(1, $filtered['mismatches']);
        $this->assertTrue($filtered['has_mismatches']);
    }

    public function test_strips_unused_declaration_listed_in_optional(): void
    {
        $audit = [
            'has_mismatches' => true,
            'mismatches' => [
                ['type' => 'unused_declaration', 'permission' => 'database.core_tables_read'],
            ],
        ];

        $filtered = $this->callFilter($audit, ['database.core_tables_read']);

        $this->assertSame([], $filtered['mismatches']);
        $this->assertFalse(
            $filtered['has_mismatches'],
            'has_mismatches must flip to false once the only mismatch is filtered out — otherwise the UI badge stays red.',
        );
    }

    public function test_keeps_undeclared_usage_even_when_listed_in_optional(): void
    {
        // _optional only excuses "I declared X but never used it" — it must
        // NOT excuse "I used X without declaring it". The latter is a real
        // permission contract violation regardless of plugin.json _optional.
        $audit = [
            'has_mismatches' => true,
            'mismatches' => [
                ['type' => 'undeclared_usage', 'permission' => 'database.core_tables_read'],
            ],
        ];

        $filtered = $this->callFilter($audit, ['database.core_tables_read']);

        $this->assertCount(
            1,
            $filtered['mismatches'],
            'undeclared_usage mismatches must NOT be suppressed by _optional — only unused_declaration is excused.',
        );
        $this->assertTrue($filtered['has_mismatches']);
    }

    public function test_keeps_unused_declaration_not_listed_in_optional(): void
    {
        $audit = [
            'has_mismatches' => true,
            'mismatches' => [
                ['type' => 'unused_declaration', 'permission' => 'mail.send'],
            ],
        ];

        $filtered = $this->callFilter($audit, ['database.core_tables_read']);

        $this->assertCount(
            1,
            $filtered['mismatches'],
            'Only permissions explicitly listed in _optional should be suppressed.',
        );
    }

    public function test_mixed_mismatches_only_strips_matching_optional_unused(): void
    {
        $audit = [
            'has_mismatches' => true,
            'mismatches' => [
                ['type' => 'unused_declaration', 'permission' => 'database.core_tables_read'], // filtered
                ['type' => 'unused_declaration', 'permission' => 'mail.send'],                  // kept
                ['type' => 'undeclared_usage', 'permission' => 'database.core_tables_read'],    // kept
            ],
        ];

        $filtered = $this->callFilter($audit, ['database.core_tables_read']);

        $this->assertCount(2, $filtered['mismatches']);
        $this->assertTrue($filtered['has_mismatches']);

        $permissionKeys = array_map(fn ($m) => [$m['type'], $m['permission']], $filtered['mismatches']);
        $this->assertContains(['unused_declaration', 'mail.send'], $permissionKeys);
        $this->assertContains(['undeclared_usage', 'database.core_tables_read'], $permissionKeys);
    }
}
