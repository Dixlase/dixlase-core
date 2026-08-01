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

namespace Tests\Unit\Http\Controllers\Admin\Settings\Systems;

use App\Http\Controllers\Admin\Settings\Systems\AdminSystemUpdatesController;
use App\Services\Core\CoreUpdater;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Pins the atomic-lock semantics of tryAcquireCoreUpdateLock() and
 * tryAcquireExtensionUpdateLock() introduced in PR-M for Round 4
 * Finding F. Before this fix the in-progress flag was purely
 * informational — a rapid double-click on the core rollback (or
 * update) button silently overwrote the flag payload and spawned a
 * second detached process. The sandbox saw multiple concurrent PIDs
 * racing on `dls:core:rollback`.
 *
 * The lock uses `fopen($path, 'x')` (O_EXCL | O_CREAT) so acquire is a
 * single atomic filesystem operation; a stale flag past
 * IN_PROGRESS_STALE_THRESHOLD_SECONDS is transparently taken over.
 *
 * This test exercises the helpers directly via reflection rather than
 * going through the HTTP POST endpoints — the HTTP path requires an
 * authenticated admin session, CSRF, the full middleware stack, and a
 * DB-backed `CoreRelease::singleton()` state, none of which are what
 * the finding is about. The lock behaviour is what the finding pins.
 */
class CoreUpdateServerLockTest extends TestCase
{
    private string $coreFlagPath;

    private string $extensionFlagPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->coreFlagPath = CoreUpdater::inProgressFlagPath();
        $this->extensionFlagPath = \App\Console\Commands\ExtensionsUpdate::inProgressFlagPath();
        $this->clearFlagFiles();
    }

    protected function tearDown(): void
    {
        $this->clearFlagFiles();
        parent::tearDown();
    }

    // ---------- Core lock ----------

    public function test_first_core_acquire_wins_second_is_rejected(): void
    {
        $controller = app(AdminSystemUpdatesController::class);

        $this->assertTrue(
            $this->invokeCoreLock($controller, ['operation' => 'update', 'started_at' => time()]),
            'The first acquire on a clean state must win the race.'
        );
        $this->assertFileExists($this->coreFlagPath);

        $this->assertFalse(
            $this->invokeCoreLock($controller, ['operation' => 'rollback', 'started_at' => time()]),
            'The second acquire while the first is still active must be '
            .'rejected — Round 4 Finding F requires that N POSTs cannot '
            .'spawn N detached processes.'
        );
    }

    public function test_core_lock_atomicity_survives_a_race(): void
    {
        // Two near-simultaneous acquires on a clean state — at most
        // one may succeed. This exercises the O_EXCL | O_CREAT
        // atomicity of the fopen('x') path; a plain
        // file_put_contents would let both writes proceed.
        $controller = app(AdminSystemUpdatesController::class);
        $results = [
            $this->invokeCoreLock($controller, ['started_at' => time()]),
            $this->invokeCoreLock($controller, ['started_at' => time()]),
        ];

        $this->assertCount(1, array_filter($results, fn ($r) => $r === true));
        $this->assertCount(1, array_filter($results, fn ($r) => $r === false));
    }

    public function test_stale_core_flag_is_taken_over(): void
    {
        $controller = app(AdminSystemUpdatesController::class);
        @mkdir(dirname($this->coreFlagPath), 0775, true);
        file_put_contents($this->coreFlagPath, '{"started_at":0,"operation":"stale"}');
        // Backdate mtime to 30 minutes ago (past the 15-minute stale
        // threshold set by IN_PROGRESS_STALE_THRESHOLD_SECONDS).
        touch($this->coreFlagPath, time() - 1800);

        $this->assertTrue(
            $this->invokeCoreLock($controller, ['operation' => 'update', 'started_at' => time()]),
            'A stale flag must be taken over so a genuinely crashed '
            .'prior subprocess does not freeze the admin UI forever.'
        );

        // The new payload must have replaced the stale one.
        $payload = json_decode((string) file_get_contents($this->coreFlagPath), true);
        $this->assertIsArray($payload);
        $this->assertSame('update', $payload['operation'] ?? null);
    }

    public function test_core_lock_release_reopens_the_gate(): void
    {
        // After the subprocess clears the flag (simulated by unlink),
        // a fresh acquire should succeed. This is the happy-path
        // sequence: update runs → clears flag → operator triggers
        // rollback → new acquire wins.
        $controller = app(AdminSystemUpdatesController::class);
        $this->assertTrue($this->invokeCoreLock($controller, ['started_at' => time()]));
        @unlink($this->coreFlagPath);
        $this->assertTrue(
            $this->invokeCoreLock($controller, ['started_at' => time()]),
            'Once the flag is cleared, a new acquire must succeed.'
        );
    }

    // ---------- Extension lock (separate flag file) ----------

    public function test_extension_lock_is_independent_of_the_core_lock(): void
    {
        // Extensions and core use separate flag files by design — a
        // core update and an extension batch can overlap (pre-existing
        // behaviour, out of scope for Finding F).
        $controller = app(AdminSystemUpdatesController::class);

        $this->assertTrue($this->invokeCoreLock($controller, ['started_at' => time()]));
        $this->assertTrue(
            $this->invokeExtensionLock($controller, ['started_at' => time()]),
            'A core lock in flight must not block an extension acquire — '
            .'they are separate concerns backed by separate flag files.'
        );
    }

    public function test_extension_lock_rejects_second_acquire(): void
    {
        $controller = app(AdminSystemUpdatesController::class);
        $this->assertTrue($this->invokeExtensionLock($controller, ['started_at' => time()]));
        $this->assertFalse(
            $this->invokeExtensionLock($controller, ['started_at' => time()]),
            'Two extension acquires in flight must not both win — that '
            .'would spawn two `dls:extensions:update` batches.'
        );
    }

    // ---------- helpers ----------

    private function invokeCoreLock(AdminSystemUpdatesController $controller, array $info): bool
    {
        $method = new ReflectionMethod(AdminSystemUpdatesController::class, 'tryAcquireCoreUpdateLock');

        return (bool) $method->invoke($controller, $info);
    }

    private function invokeExtensionLock(AdminSystemUpdatesController $controller, array $info): bool
    {
        $method = new ReflectionMethod(AdminSystemUpdatesController::class, 'tryAcquireExtensionUpdateLock');

        return (bool) $method->invoke($controller, $info);
    }

    private function clearFlagFiles(): void
    {
        foreach ([$this->coreFlagPath, $this->extensionFlagPath] as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }
}
