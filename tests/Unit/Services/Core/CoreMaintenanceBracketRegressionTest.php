<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

namespace Tests\Unit\Services\Core;

use Tests\TestCase;

/**
 * Regression pin for Round 4 Finding D. Before PR-K, the maintenance
 * mode bracket in CoreUpdater::update() and CoreRollback::handle() was
 * only entered when `$dependencyUpdate` (composer.lock changed) was
 * true. On slow disks the source-apply / source-restore rsync window
 * stretches to minutes, and any HTTP request landing in that window
 * fatally errored on `require(app/Helpers/AssetHelper.php)` or
 * `require(config/trustedproxy.php)` because the target file was
 * momentarily absent — the sandbox observed exactly this on a
 * bind-mount install (see scratchpad/core-team-tasks-dryrun8-ui-round.md
 * Finding D).
 *
 * The fix is trivial (drop the `if ($dependencyUpdate)` gate around
 * the `Artisan::call('down', ...)` line) but the class of regression
 * is easy to reintroduce silently — "surely we don't need to bring
 * the site down for a source-only swap". This test reads the two
 * source files back and pins the invariant, so a well-meaning refactor
 * that re-adds the gate is caught in CI rather than in a slow-disk
 * production install.
 *
 * The test is intentionally source-inspection rather than behavioural
 * (mocking `CoreUpdater`'s many collaborators to reach the down call
 * is heavier than the payoff, and sandbox verification will confirm
 * the runtime behaviour on the real dryrun-9 → dryrun-10 flow).
 */
class CoreMaintenanceBracketRegressionTest extends TestCase
{
    public function test_core_updater_calls_down_without_a_dependency_update_gate(): void
    {
        $this->assertDownCallIsUnconditional(
            app_path('Services/Core/CoreUpdater.php'),
            'CoreUpdater::update()'
        );
    }

    public function test_core_rollback_calls_down_without_a_dependency_update_gate(): void
    {
        $this->assertDownCallIsUnconditional(
            app_path('Console/Commands/CoreRollback.php'),
            'CoreRollback::handle()'
        );
    }

    public function test_core_updater_resets_opcache_after_source_apply(): void
    {
        // Opcache may hold stale entries for files just replaced by
        // applyToLiveTree(); a reset inside the down window ensures
        // the subsequent migrate + cache clears re-parse the fresh
        // tree. Belt and braces for slow-disk / validate_timestamps=0
        // hosts.
        $source = (string) file_get_contents(app_path('Services/Core/CoreUpdater.php'));
        $this->assertStringContainsString(
            'opcache_reset()',
            $source,
            'CoreUpdater::update() must reset opcache after source apply — '
            .'without it, FPM may serve stale entries pointing at the '
            .'just-swapped files (Round 4 Finding D follow-up).'
        );
    }

    public function test_core_rollback_resets_opcache_after_source_restore(): void
    {
        $source = (string) file_get_contents(app_path('Console/Commands/CoreRollback.php'));
        $this->assertStringContainsString(
            'opcache_reset()',
            $source,
            'CoreRollback::handle() must reset opcache after source '
            .'restore — same reasoning as the update path.'
        );
    }

    private function assertDownCallIsUnconditional(string $file, string $label): void
    {
        $source = (string) file_get_contents($file);

        // Locate the maintenance-down call. The exact args (--retry /
        // --refresh values) are not what we're pinning; the invariant
        // is that the call is present and unconditional.
        $pos = strpos($source, "Artisan::call('down',");
        $this->assertNotFalse(
            $pos,
            "$label must call `Artisan::call('down', …)` — the ".
            'maintenance bracket that guards the source-apply window.'
        );

        // Grab the ~500 bytes immediately preceding the down call and
        // assert that a `if ($dependencyUpdate)` guard is not the last
        // thing before it. That specific pattern is the pre-Finding-D
        // regression — a future refactor that re-adds it would silently
        // re-expose the mid-apply fatals on slow disks.
        $lookBack = substr($source, max(0, $pos - 500), min(500, $pos));
        $this->assertStringNotContainsString(
            'if ($dependencyUpdate)',
            $lookBack,
            "$label: `Artisan::call('down', …)` must NOT be immediately "
            .'preceded by an `if ($dependencyUpdate)` gate. The Round 4 '
            .'Finding D fix (PR-K) made the bracket unconditional; '
            .'sandbox verification on slow bind-mount confirmed the '
            .'previous conditional bracket left source-apply and '
            .'source-restore rsync windows exposed to web requests, '
            .'producing fatal `require(...)` errors that hid the real '
            .'update outcome. Do not re-introduce the gate.'
        );
    }
}
