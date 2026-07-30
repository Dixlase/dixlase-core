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

namespace Tests\Feature\Install;

use App\Http\Controllers\Install\InstallCompleteController;
use App\Models\CoreVersionHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Pins the baseline `core_version_history` row that the install
 * wizard writes to fix issue #171 Finding D. Without this row, the
 * first `dls:core:update` on a freshly-installed site records
 * `from: 0.0.0` — which then breaks rollback (Finding B).
 *
 * These tests exercise the extracted
 * `recordBaselineVersionHistoryIfNeeded()` method directly via
 * reflection. Calling `finalize()` would go through `updateEnv()`,
 * which mutates the process's `.env` file and leaves it in
 * `INSTALLED=true` state — that state then breaks every other
 * install-flow test that expects `INSTALLED=false`. The extracted
 * method has the same code path (reads VERSION, checks ledger,
 * inserts row) without the .env side effect.
 */
class InstallBaselineVersionHistoryTest extends TestCase
{
    use RefreshDatabase;

    private string $originalVersionContent = '';

    private bool $hadVersionFile = false;

    protected function setUp(): void
    {
        parent::setUp();
        $path = base_path('VERSION');
        if (file_exists($path)) {
            $this->hadVersionFile = true;
            $this->originalVersionContent = (string) @file_get_contents($path);
        }
    }

    protected function tearDown(): void
    {
        $path = base_path('VERSION');
        if ($this->hadVersionFile) {
            file_put_contents($path, $this->originalVersionContent);
        } elseif (file_exists($path)) {
            @unlink($path);
        }
        Cache::forget(CoreVersionHistory::CURRENT_VERSION_CACHE_KEY);
        parent::tearDown();
    }

    /**
     * Reflect through to the extracted method. It's `protected` on the
     * controller so tests can reach it without also being forced to run
     * the .env-mutating `finalize()`.
     */
    private function invokeRecord(): ?CoreVersionHistory
    {
        $controller = new InstallCompleteController();
        $method = new ReflectionMethod($controller, 'recordBaselineVersionHistoryIfNeeded');
        $method->setAccessible(true);

        return $method->invoke($controller);
    }

    public function test_baseline_history_row_is_written_when_version_file_exists_and_ledger_is_empty(): void
    {
        File::put(base_path('VERSION'), "0.3.3-dryrun-8\n");
        Cache::forget(CoreVersionHistory::CURRENT_VERSION_CACHE_KEY);
        $this->assertSame(0, CoreVersionHistory::count());

        $written = $this->invokeRecord();

        $this->assertNotNull($written, 'The method should return the created row when the guard succeeds.');
        $this->assertSame(1, CoreVersionHistory::count(), 'Exactly one baseline row should be inserted.');
        $row = CoreVersionHistory::first();
        $this->assertSame('install', $row->installation_method);
        $this->assertNull($row->old_version, 'The baseline row must have old_version=NULL (there is nothing before the install).');
        $this->assertSame('0.3.3-dryrun-8', $row->new_version);
        $this->assertNull($row->applied_by_id, 'No authenticated member during install — applied_by_id must be null.');
    }

    public function test_baseline_row_is_no_t_written_when_version_file_is_absent(): void
    {
        // Simulate a pre-VERSION-file release (dryrun-6 and earlier).
        // The guard must silently no-op so an old release install path
        // keeps working exactly as before (backward compat).
        @unlink(base_path('VERSION'));
        Cache::forget(CoreVersionHistory::CURRENT_VERSION_CACHE_KEY);
        $this->assertSame(0, CoreVersionHistory::count());

        $written = $this->invokeRecord();

        $this->assertNull($written);
        $this->assertSame(0, CoreVersionHistory::count(), 'No baseline row should be written when VERSION file is absent.');
    }

    public function test_baseline_row_is_no_t_written_when_ledger_already_has_a_row(): void
    {
        // A re-run on an already-installed site (edge case, but the
        // endpoint is technically reachable) must not double-insert.
        // Guard by "ledger empty" so the initial baseline is never
        // overwritten by a later re-submit.
        File::put(base_path('VERSION'), "0.3.3-dryrun-8\n");
        CoreVersionHistory::create([
            'old_version' => null,
            'new_version' => '0.2.4',
            'files_changed_count' => 0,
            'lines_added' => 0,
            'lines_removed' => 0,
            'signing_key_changed' => false,
            'author_id_changed' => false,
            'installation_method' => 'install',
            'installed_from_url' => null,
            'downloaded_sha256' => null,
            'applied_by_id' => null,
            'applied_at' => now(),
        ]);
        Cache::forget(CoreVersionHistory::CURRENT_VERSION_CACHE_KEY);
        $this->assertSame(1, CoreVersionHistory::count());

        $written = $this->invokeRecord();

        $this->assertNull($written);
        $this->assertSame(1, CoreVersionHistory::count(), 'Guard must skip write when the ledger is not empty.');
        $this->assertSame('0.2.4', CoreVersionHistory::first()->new_version, 'Existing row must not be overwritten.');
    }

    public function test_current_version_reflects_the_baseline_row_immediately(): void
    {
        // The cache is `rememberForever`, so the method must invalidate
        // it right after the write. Otherwise a stale null would linger
        // until the next cache clear, and the downgrade guard / drift
        // service would still see "no version" post-install.
        File::put(base_path('VERSION'), "0.3.3-dryrun-8\n");
        Cache::forget(CoreVersionHistory::CURRENT_VERSION_CACHE_KEY);
        $this->assertNull(CoreVersionHistory::currentVersion(), 'Precondition: ledger empty → currentVersion() null');

        $this->invokeRecord();

        $this->assertSame('0.3.3-dryrun-8', CoreVersionHistory::currentVersion(), 'Cache must be invalidated so currentVersion() sees the fresh baseline row.');
    }
}
