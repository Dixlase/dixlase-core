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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Pins the baseline `core_version_history` row that the install
 * wizard's `finalize()` writes to fix issue #171 Finding D. Without
 * this row, the first `dls:core:update` on a freshly-installed site
 * records `from: 0.0.0` — which then breaks rollback (Finding B)
 * because rollback re-fetches vendor from release `v0.0.0`, which
 * does not exist.
 *
 * The tests stage a temporary VERSION file at the app's base path so
 * the finalize() call's `CoreUpdater::readVersionFromDisk()` returns
 * a known value without depending on whatever the repo happens to
 * ship as VERSION at test time.
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
     * Reflect into finalize() so tests exercise only the ledger-write
     * branch, not the full HTTP install flow (session bootstrap, .env
     * rewrite, etc. — those have their own coverage). The route also
     * requires the `install.steps` middleware which is not fully
     * satisfiable in a unit-test environment.
     */
    private function invokeFinalize(): void
    {
        $controller = new InstallCompleteController();

        // The relevant block lives after `updateEnv()`, which does .env I/O.
        // Rather than run the full method, we replicate the exact code
        // block under test via reflection is overkill; simpler: call the
        // whole method with a bogus request and let updateEnv see the
        // test .env (Laravel's TestCase points APP_ENV to testing).
        // If updateEnv throws (e.g. because .env is read-only in CI), we
        // still care only about whether the history row was written —
        // wrap in try/catch and inspect the DB afterwards.
        try {
            $controller->finalize(new Request());
        } catch (\Throwable $e) {
            // Ignore — we only test the CoreVersionHistory side effect below.
        }
    }

    public function test_baseline_history_row_is_written_when_version_file_exists_and_ledger_is_empty(): void
    {
        File::put(base_path('VERSION'), "0.3.3-dryrun-8\n");
        Cache::forget(CoreVersionHistory::CURRENT_VERSION_CACHE_KEY);
        $this->assertSame(0, CoreVersionHistory::count());

        $this->invokeFinalize();

        $this->assertSame(1, CoreVersionHistory::count(), 'finalize() should insert exactly one baseline row when VERSION exists and ledger is empty.');
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

        $this->invokeFinalize();

        $this->assertSame(0, CoreVersionHistory::count(), 'No baseline row should be written when VERSION file is absent.');
    }

    public function test_baseline_row_is_no_t_written_when_ledger_already_has_a_row(): void
    {
        // A re-run of finalize() on an already-installed site (edge case,
        // but the endpoint is technically reachable) must not
        // double-insert. Guard by "ledger empty" so the initial baseline
        // is never overwritten by a later re-submit.
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

        $this->invokeFinalize();

        $this->assertSame(1, CoreVersionHistory::count(), 'Guard must skip write when the ledger is not empty.');
        $this->assertSame('0.2.4', CoreVersionHistory::first()->new_version, 'Existing row must not be overwritten.');
    }

    public function test_current_version_reflects_the_baseline_row_immediately(): void
    {
        // The cache is `rememberForever`, so the guard must invalidate it
        // right after the write. Otherwise a stale null would linger
        // until the next cache clear, and the downgrade guard / drift
        // service would still see "no version" post-install.
        File::put(base_path('VERSION'), "0.3.3-dryrun-8\n");
        Cache::forget(CoreVersionHistory::CURRENT_VERSION_CACHE_KEY);
        $this->assertNull(CoreVersionHistory::currentVersion(), 'Precondition: ledger empty → currentVersion() null');

        $this->invokeFinalize();

        $this->assertSame('0.3.3-dryrun-8', CoreVersionHistory::currentVersion(), 'Cache must be invalidated so currentVersion() sees the fresh baseline row.');
    }
}
