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

namespace Tests\Feature\Install;

use App\Support\Install\InstallRunLock;
use Tests\TestCase;

/**
 * The install execution rewrites .env, migrates, seeds and creates the
 * first administrator, and used to accept a second run straight through
 * the middle of the first — a double-submitted form or a proxy retry was
 * enough, and two concurrent `migrate:fresh` calls against one database
 * is the worst way to find that out.
 *
 * The same marker records an interrupted run, so the next attempt can say
 * what happened instead of pretending nothing did.
 */
class InstallRunLockTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        InstallRunLock::release();
    }

    protected function tearDown(): void
    {
        InstallRunLock::release();
        parent::tearDown();
    }

    public function test_the_first_run_takes_the_lock(): void
    {
        $this->assertTrue(InstallRunLock::acquire());
        $this->assertTrue(InstallRunLock::isRunning());
        $this->assertFalse(InstallRunLock::wasInterrupted());
    }

    public function test_a_second_run_is_refused_while_the_first_holds_it(): void
    {
        InstallRunLock::acquire();

        $this->assertFalse(InstallRunLock::acquire());
    }

    public function test_the_lock_is_free_again_after_release(): void
    {
        InstallRunLock::acquire();
        InstallRunLock::release();

        $this->assertFalse(InstallRunLock::isRunning());
        $this->assertFalse(InstallRunLock::wasInterrupted());
        $this->assertTrue(InstallRunLock::acquire());
    }

    public function test_a_stale_marker_reads_as_an_interrupted_run(): void
    {
        InstallRunLock::acquire();
        $this->ageMarker(InstallRunLock::TTL_SECONDS + 60);

        $this->assertFalse(InstallRunLock::isRunning());
        $this->assertTrue(InstallRunLock::wasInterrupted());
    }

    public function test_a_stale_marker_does_not_block_a_retry(): void
    {
        InstallRunLock::acquire();
        $this->ageMarker(InstallRunLock::TTL_SECONDS + 60);

        $this->assertTrue(InstallRunLock::acquire(), 'A killed run must not lock the installer out.');
        $this->assertTrue(InstallRunLock::isRunning());
    }

    public function test_no_marker_means_neither_running_nor_interrupted(): void
    {
        $this->assertNull(InstallRunLock::startedAt());
        $this->assertFalse(InstallRunLock::isRunning());
        $this->assertFalse(InstallRunLock::wasInterrupted());
    }

    private function ageMarker(int $seconds): void
    {
        touch(InstallRunLock::path(), time() - $seconds);
        clearstatcache(true, InstallRunLock::path());
    }
}
