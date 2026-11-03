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

namespace Tests\Unit\Console;

use Tests\TestCase;

/**
 * `CoreVendorManager::swap()` renames the live vendor/ to vendor.old. A
 * successful update discards it (CoreUpdater) and a failed one restores it,
 * but the rollback's success path used to do neither, so every rollback that
 * swapped vendor/ left a vendor.old behind — which the preflight check then
 * reported as a leftover from an unfinished update until the next swap
 * deleted it (#854).
 *
 * Source-inspection style, the same choice as CoreRollbackOrderingTest:
 * reaching the success path in PHPUnit would mean orchestrating a release
 * download, a source snapshot restore and a vendor swap, while the invariant
 * that matters is textual — which calls the path makes, and in what order.
 */
class CoreRollbackVendorLeftoverTest extends TestCase
{
    private function source(): string
    {
        return (string) file_get_contents(app_path('Console/Commands/CoreRollback.php'));
    }

    public function test_the_success_path_discards_the_replaced_vendor(): void
    {
        $source = $this->source();

        $snapshotDiscard = strpos($source, '$snapshotter->discard($snapshotPath)');
        $vendorDiscard = strpos($source, '$vendorManager->discardPrevious()');
        $success = strpos($source, 'Core rolled back to v');

        $this->assertNotFalse($snapshotDiscard, 'The rollback point is no longer consumed.');
        $this->assertNotFalse($vendorDiscard, 'The rollback no longer discards vendor.old; a leftover is left on disk.');
        $this->assertNotFalse($success, 'The success message moved.');

        $this->assertGreaterThan(
            $snapshotDiscard,
            $vendorDiscard,
            'vendor.old must be discarded on the success path, after the rollback point is consumed.'
        );
        $this->assertLessThan(
            $success,
            $vendorDiscard,
            'vendor.old must be discarded before the command reports success.'
        );
    }

    public function test_the_discard_is_guarded_by_the_swap_flag(): void
    {
        $source = $this->source();
        $vendorDiscard = (int) strpos($source, '$vendorManager->discardPrevious()');

        // The nearest preceding condition has to be the swap flag: a rollback
        // that never swapped vendor/ has no vendor.old of its own, and
        // discarding unconditionally would delete one an earlier, unfinished
        // update left behind — the very state the preflight check reports.
        $before = substr($source, 0, $vendorDiscard);

        $this->assertStringEndsWith(
            "if (\$vendorSwapped) {\n                ",
            $before,
            'discardPrevious() must sit directly inside an if ($vendorSwapped) block.'
        );
    }

    public function test_the_failure_path_still_restores_the_vendor(): void
    {
        $this->assertStringContainsString(
            '$vendorManager->restorePrevious()',
            $this->source(),
            'A failed rollback must still put the previous vendor/ back.'
        );
    }
}
