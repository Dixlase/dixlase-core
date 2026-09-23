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

namespace Tests\Feature\Console;

use App\Models\CoreRelease;
use App\Models\CoreVersionHistory;
use App\Services\Core\VersionDriftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Pins `dls:core:reconcile` (App\Console\Commands\CoreReconcile). The
 * command writes one synthetic `core_version_history` row when the
 * on-disk VERSION disagrees with the ledger's currentVersion(). We fake
 * the drift service so tests do not depend on the real repo-root
 * VERSION file (which moves every release).
 */
class CoreReconcileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget(CoreVersionHistory::CURRENT_VERSION_CACHE_KEY);
    }

    private function stubDrift(?string $onDisk, ?string $ledger): void
    {
        // Bind a fake VersionDriftService that returns the drift shape
        // we want to exercise, without needing a real VERSION file or a
        // seeded history row. Overrides detect() to skip the real
        // filesystem / DB reads and delegate to the (still real)
        // classify() with the stub values.
        $this->app->instance(VersionDriftService::class, new class($onDisk, $ledger) extends VersionDriftService
        {
            public function __construct(private readonly ?string $stubbedOnDisk, private readonly ?string $stubbedLedger) {}

            public function detect(): array
            {
                return $this->classify($this->stubbedOnDisk, $this->stubbedLedger);
            }
        });
    }

    public function test_dry_run_reports_drift_and_makes_no_row(): void
    {
        $this->stubDrift(onDisk: '0.3.1', ledger: '0.2.4');

        $this->assertSame(0, CoreVersionHistory::count());

        $this->artisan('dls:core:reconcile')
            ->expectsOutputToContain('Detected drift:')
            ->expectsOutputToContain('On-disk (VERSION file): v0.3.1')
            ->expectsOutputToContain('Ledger (currentVersion): v0.2.4')
            ->expectsOutputToContain('Dry-run only.')
            ->assertSuccessful();

        $this->assertSame(0, CoreVersionHistory::count());
    }

    public function test_confirm_writes_a_synthetic_row_marked_reconcile(): void
    {
        $this->stubDrift(onDisk: '0.3.1', ledger: '0.2.4');

        $this->artisan('dls:core:reconcile --confirm')
            ->expectsOutputToContain('Wrote synthetic history row')
            ->assertSuccessful();

        $this->assertSame(1, CoreVersionHistory::count());
        $row = CoreVersionHistory::first();
        $this->assertSame('reconcile', $row->installation_method);
        $this->assertSame('0.2.4', $row->old_version);
        $this->assertSame('0.3.1', $row->new_version);
        $this->assertNull($row->applied_by_id);
    }

    public function test_no_op_when_ledger_already_matches_on_disk(): void
    {
        $this->stubDrift(onDisk: '0.3.1', ledger: '0.3.1');

        $this->artisan('dls:core:reconcile --confirm')
            ->expectsOutputToContain('Nothing to do.')
            ->assertSuccessful();

        $this->assertSame(0, CoreVersionHistory::count());
    }

    /**
     * Record the marker an interrupted update leaves behind — the pair
     * the Updates screen renders in red.
     */
    protected function markPreviousUpdateFailed(string $reason = 'Maintenance mode lifted automatically: the core update process (pid 3687) is no longer running.'): void
    {
        CoreRelease::singleton()->forceFill([
            'update_failed_at' => now(),
            'update_failure_reason' => $reason,
        ])->save();
    }

    public function test_confirm_clears_the_failure_marker_left_by_an_interrupted_update(): void
    {
        $this->stubDrift(onDisk: '0.3.1', ledger: '0.2.4');
        $this->markPreviousUpdateFailed();

        $this->artisan('dls:core:reconcile --confirm')
            ->expectsOutputToContain('A previous update or rollback left a failure marker')
            ->expectsOutputToContain('Wrote synthetic history row')
            ->expectsOutputToContain('Cleared the failure marker')
            ->assertSuccessful();

        $state = CoreRelease::singleton()->fresh();
        $this->assertNull($state->update_failed_at);
        $this->assertNull($state->update_failure_reason);
    }

    public function test_dry_run_reports_the_failure_marker_without_clearing_it(): void
    {
        $this->stubDrift(onDisk: '0.3.1', ledger: '0.2.4');
        $this->markPreviousUpdateFailed();

        $this->artisan('dls:core:reconcile')
            ->expectsOutputToContain('A previous update or rollback left a failure marker')
            ->expectsOutputToContain('clear the failure marker above')
            ->assertSuccessful();

        $this->assertNotNull(CoreRelease::singleton()->fresh()->update_failed_at);
    }

    public function test_a_leftover_marker_is_cleared_even_when_the_ledger_already_matches(): void
    {
        $this->stubDrift(onDisk: '0.3.1', ledger: '0.3.1');
        $this->markPreviousUpdateFailed();

        // Without --confirm the marker survives and the operator is told
        // no ledger change is needed.
        $this->artisan('dls:core:reconcile')
            ->expectsOutputToContain('the ledger itself needs no change')
            ->assertSuccessful();
        $this->assertNotNull(CoreRelease::singleton()->fresh()->update_failed_at);

        $this->artisan('dls:core:reconcile --confirm')
            ->expectsOutputToContain('Cleared the failure marker')
            ->assertSuccessful();

        $this->assertNull(CoreRelease::singleton()->fresh()->update_failed_at);
        $this->assertSame(0, CoreVersionHistory::count(), 'no drift means no synthetic row');
    }

    public function test_refuses_reverse_drift_without_force(): void
    {
        // On-disk older than ledger — unusual. Should refuse to bury
        // the ledger evidence.
        $this->stubDrift(onDisk: '0.2.4', ledger: '0.3.1');

        $this->artisan('dls:core:reconcile --confirm')
            ->expectsOutputToContain('is NEWER than on-disk')
            ->expectsOutputToContain('Pass --force if you are sure')
            ->assertFailed();

        $this->assertSame(0, CoreVersionHistory::count());
    }

    public function test_force_allows_reverse_drift(): void
    {
        $this->stubDrift(onDisk: '0.2.4', ledger: '0.3.1');

        $this->artisan('dls:core:reconcile --confirm --force')
            ->expectsOutputToContain('Wrote synthetic history row')
            ->assertSuccessful();

        $row = CoreVersionHistory::first();
        $this->assertSame('0.3.1', $row->old_version);
        $this->assertSame('0.2.4', $row->new_version);
        $this->assertSame('reconcile', $row->installation_method);
    }

    public function test_reports_failure_when_on_disk_is_unknown(): void
    {
        $this->stubDrift(onDisk: null, ledger: '0.3.1');

        $this->artisan('dls:core:reconcile --confirm')
            ->expectsOutputToContain('Cannot detect drift')
            ->expectsOutputToContain('VERSION file: (absent)')
            ->assertFailed();

        $this->assertSame(0, CoreVersionHistory::count());
    }

    public function test_reports_failure_when_ledger_is_unknown(): void
    {
        $this->stubDrift(onDisk: '0.3.1', ledger: null);

        $this->artisan('dls:core:reconcile --confirm')
            ->expectsOutputToContain('Cannot detect drift')
            ->expectsOutputToContain('Ledger:       (no history rows)')
            ->assertFailed();

        $this->assertSame(0, CoreVersionHistory::count());
    }

    public function test_current_version_reflects_the_synthetic_row_after_confirm(): void
    {
        // Before reconcile: ledger says 0.2.4.
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

        $this->assertSame('0.2.4', CoreVersionHistory::currentVersion());

        // Stub the drift so the command sees on-disk=0.3.1, ledger=0.2.4
        // (matching the seeded row's new_version). The command's write
        // path uses the ledger value from the drift result to fill
        // old_version, so we mirror the seeded state to keep the ledger
        // consistent post-write.
        $this->stubDrift(onDisk: '0.3.1', ledger: '0.2.4');

        $this->artisan('dls:core:reconcile --confirm')
            ->expectsOutputToContain('Wrote synthetic history row')
            ->assertSuccessful();

        Cache::forget(CoreVersionHistory::CURRENT_VERSION_CACHE_KEY);
        $this->assertSame('0.3.1', CoreVersionHistory::currentVersion());
    }
}
