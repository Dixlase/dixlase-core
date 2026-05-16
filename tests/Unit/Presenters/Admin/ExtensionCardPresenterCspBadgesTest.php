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

use App\Presenters\Admin\ExtensionCardPresenter;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Locks in the "Plan B" behaviour: until the CSP scanner is enhanced to detect
 * Alpine CSP build incompatibilities, Trusted Types violations, and SRI on
 * external scripts, the strict-mode badge MUST stay unknown — even when the
 * narrow scanner currently shows the plugin as clean.
 *
 * See .backlog/csp-strict-mode-readiness.md for the full criteria the badge
 * would need to verify before becoming green.
 */
class ExtensionCardPresenterCspBadgesTest extends TestCase
{
    private function buildBadges(array $cspCompatibility): array
    {
        $method = new ReflectionMethod(ExtensionCardPresenter::class, 'buildCspModeBadges');
        $method->setAccessible(true);

        return $method->invoke(null, $cspCompatibility);
    }

    public function test_strict_badge_is_unknown_even_when_scanner_reports_clean(): void
    {
        $badges = $this->buildBadges([
            'status' => 'csp_ready',
            'requires_inline_js' => false,
            'requires_inline_css' => false,
        ]);

        $this->assertSame(
            ['compatible' => null, 'checked' => false],
            $badges['strict'],
            'Strict badge must stay unknown until scanner can verify the full strict-mode criteria.'
        );
    }

    public function test_standard_badge_reflects_inline_js_scan_result(): void
    {
        $cleanBadges = $this->buildBadges([
            'status' => 'csp_ready',
            'requires_inline_js' => false,
            'requires_inline_css' => false,
        ]);

        $this->assertSame(
            ['compatible' => true, 'checked' => true],
            $cleanBadges['standard']
        );

        $dirtyBadges = $this->buildBadges([
            'status' => 'inline_required',
            'requires_inline_js' => true,
            'requires_inline_css' => false,
        ]);

        $this->assertSame(
            ['compatible' => false, 'checked' => true],
            $dirtyBadges['standard']
        );
    }

    public function test_development_badge_is_always_compatible_when_scan_ran(): void
    {
        $badges = $this->buildBadges([
            'status' => 'inline_required',
            'requires_inline_js' => true,
            'requires_inline_css' => true,
        ]);

        $this->assertSame(
            ['compatible' => true, 'checked' => true],
            $badges['development']
        );
    }

    public function test_all_badges_unknown_when_scan_not_run(): void
    {
        $badges = $this->buildBadges([
            'status' => 'unknown',
        ]);

        foreach (['development', 'standard', 'strict'] as $tier) {
            $this->assertSame(
                ['compatible' => null, 'checked' => false],
                $badges[$tier],
                "Tier {$tier} must be unknown when scan has not run."
            );
        }
    }
}
