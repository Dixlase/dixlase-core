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

namespace Tests\Unit\Support\Install;

use App\Support\Install\FinalizePendingMarker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Pins the marker that keeps the INSTALLED self-heal out of the window
 * between the install completion screen and finalize().
 *
 * The regression it guards: an asset request from the completion screen
 * healed the flag, the finalize POST was then treated as access to the
 * installer of an already-installed site and redirected to the front
 * page, and finalize() — session driver restore, install-time audit log
 * chaining — never ran.
 */
class FinalizePendingMarkerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        FinalizePendingMarker::clear();
    }

    protected function tearDown(): void
    {
        FinalizePendingMarker::clear();
        parent::tearDown();
    }

    #[Test]
    public function it_reports_nothing_pending_before_the_completion_screen(): void
    {
        $this->assertFalse(FinalizePendingMarker::isPending());
    }

    #[Test]
    public function it_reports_pending_once_the_completion_screen_marks_it(): void
    {
        FinalizePendingMarker::mark();

        $this->assertTrue(FinalizePendingMarker::isPending());
        $this->assertFileExists(FinalizePendingMarker::path());
    }

    #[Test]
    public function finalize_clears_it(): void
    {
        FinalizePendingMarker::mark();
        FinalizePendingMarker::clear();

        $this->assertFalse(FinalizePendingMarker::isPending());
        $this->assertFileDoesNotExist(FinalizePendingMarker::path());
    }

    #[Test]
    public function it_expires_so_an_abandoned_completion_screen_heals(): void
    {
        FinalizePendingMarker::mark();

        // Age the marker past its TTL: an operator who closed the tab must
        // not leave the self-heal switched off for good.
        touch(FinalizePendingMarker::path(), time() - FinalizePendingMarker::TTL_SECONDS - 1);
        clearstatcache(true, FinalizePendingMarker::path());

        $this->assertFalse(FinalizePendingMarker::isPending());
    }

    #[Test]
    public function marking_twice_refreshes_the_window(): void
    {
        FinalizePendingMarker::mark();
        touch(FinalizePendingMarker::path(), time() - FinalizePendingMarker::TTL_SECONDS - 1);
        clearstatcache(true, FinalizePendingMarker::path());
        $this->assertFalse(FinalizePendingMarker::isPending());

        // Reloading the completion screen starts the window again.
        FinalizePendingMarker::mark();
        clearstatcache(true, FinalizePendingMarker::path());

        $this->assertTrue(FinalizePendingMarker::isPending());
    }
}
