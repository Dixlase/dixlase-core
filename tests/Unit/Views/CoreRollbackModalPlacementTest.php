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

namespace Tests\Unit\Views;

use PHPUnit\Framework\TestCase;

/**
 * Pins where the core rollback modals sit in the system updates page.
 *
 * Regression: the rollback confirm modal was nested inside
 * `@if($totalCount > 0)`. Right after a core update nothing is left to
 * update, so totalCount is 0 while the rollback button is still shown —
 * the button called openModal('confirmCoreRollbackModal') on an element
 * that was never rendered, and rollback from the admin panel did nothing.
 */
class CoreRollbackModalPlacementTest extends TestCase
{
    private const VIEW = __DIR__.'/../../../resources/views/admin/settings/systems/updates/index.blade.php';

    public function test_rollback_modals_are_rendered_outside_the_updatable_items_block(): void
    {
        $source = (string) file_get_contents(self::VIEW);
        [$start, $end] = $this->blockRange($source, '@if($totalCount > 0)');

        foreach (['confirmCoreRollbackModal', 'updatesInProgressModal'] as $id) {
            $position = strpos($source, 'id="'.$id.'"');

            $this->assertNotFalse($position, "{$id} is missing from the view");
            $this->assertFalse(
                $position > $start && $position < $end,
                "{$id} must not be inside @if(\$totalCount > 0): the rollback button is shown when nothing is left to update"
            );
        }
    }

    /**
     * Offsets of an @if block, from its opening directive to the matching @endif.
     *
     * @return array{int, int}
     */
    private function blockRange(string $source, string $opening): array
    {
        $start = strpos($source, $opening);
        $this->assertNotFalse($start, "{$opening} not found");

        preg_match_all('/@(if|endif)\b/', $source, $matches, PREG_OFFSET_CAPTURE, $start);
        $depth = 0;
        foreach ($matches[1] as [$directive, $offset]) {
            $depth += $directive === 'if' ? 1 : -1;
            if ($depth === 0) {
                return [$start, $offset];
            }
        }

        $this->fail("No matching @endif for {$opening}");
    }
}
