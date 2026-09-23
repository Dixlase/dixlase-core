<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * @internal Core only. Do not reference from plugins/themes
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

namespace App\Console\Commands;

use App\Models\CoreRelease;
use App\Models\CoreVersionHistory;
use App\Services\Core\VersionDriftService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Reconcile the `core_version_history` ledger with the on-disk running
 * code, by writing a synthetic history row when the two disagree.
 *
 * When a checkout is advanced via `git` (or any path other than
 * `dls:core:update`), no history row is recorded, so
 * `CoreVersionHistory::currentVersion()` returns whatever the previous
 * update landed at. The Updates UI then offers destructive downgrades
 * (see Finding #1's guard) and — even after PR-A blocks them — surfaces
 * phantom "updates available" that the operator has to reason about.
 *
 * This command inserts one row that fast-forwards the ledger to match
 * the VERSION file, tagged clearly as `installation_method = reconcile`
 * so the ledger's audit trail still tells the true story of how the
 * install got here. It is idempotent: a run where the ledger already
 * matches the on-disk version reports "nothing to do" and exits without
 * writing.
 *
 * It also clears the core release's failure marker
 * (`update_failed_at` / `update_failure_reason`), which is what the
 * Updates screen renders as the red "the update did not complete" notice.
 * Only a successful update used to clear it, so an operator who recovered
 * the documented way — an interrupted update leaves the tree applied but
 * the ledger behind, and `dls:core:reconcile --confirm` is the recovery —
 * was left staring at a warning about a state they had just fixed. The
 * marker is cleared on a no-drift run too, so a leftover notice can be
 * dismissed without inventing an update to run.
 *
 * Defaults to a dry-run preview; pass --confirm to apply. Never touches
 * data tables or the schema; only the ledger row and that marker are
 * written.
 */
class CoreReconcile extends Command
{
    protected $signature = 'dls:core:reconcile
        {--confirm : Apply the reconcile (default is a dry-run preview)}
        {--force : Reconcile even when the on-disk VERSION is older than the ledger (the reverse-drift case, e.g. an accidental git downgrade). Off by default because the more common cause is a stale ledger and blindly writing an older version could bury a real update that had already landed}';

    protected $description = 'Write a synthetic core_version_history row so the ledger matches the on-disk VERSION (used when a checkout was advanced via git rather than dls:core:update)';

    public function handle(VersionDriftService $driftService): int
    {
        $drift = $driftService->detect();

        if (! $drift['known']) {
            $this->warn('Cannot detect drift — one of on-disk / ledger is unknown:');
            $this->line('  VERSION file: '.($drift['on_disk'] ?? '(absent)'));
            $this->line('  Ledger:       '.($drift['ledger'] ?? '(no history rows)'));
            $this->line('Reconcile needs both sides. If the VERSION file is missing, add one at the repo root; if the ledger is empty, run a normal install (or `dls:core:update`) first.');

            return self::FAILURE;
        }

        if (! $drift['drifted']) {
            $this->info("✓ Ledger already matches on-disk (v{$drift['on_disk']}).");

            if (! $this->hasFailureMarker()) {
                $this->line('Nothing to do.');

                return self::SUCCESS;
            }

            $this->reportFailureMarker();

            if (! $this->option('confirm')) {
                $this->warn('Dry-run only. Re-run with --confirm to clear that marker (the ledger itself needs no change).');

                return self::SUCCESS;
            }

            $this->clearFailureMarker();

            return self::SUCCESS;
        }

        $onDisk = $drift['on_disk'];
        $ledger = $drift['ledger'];
        $kind = $drift['kind'];

        if ($kind === 'behind' && ! $this->option('force')) {
            $this->error("Ledger (v{$ledger}) is NEWER than on-disk (v{$onDisk}) — this is not the usual stale-ledger case.");
            $this->line('The more common cause of drift is a stale ledger (on-disk ahead of ledger). Ledger newer than on-disk usually means one of:');
            $this->line('  • The on-disk code was downgraded via git and the ledger correctly remembers the previous version.');
            $this->line('  • The VERSION file was hand-edited to an older value.');
            $this->line('Blindly writing an older synthetic row would bury the ledger evidence of that. Pass --force if you are sure.');

            return self::FAILURE;
        }

        $this->line('Detected drift:');
        $this->line("  On-disk (VERSION file): v{$onDisk}");
        $this->line("  Ledger (currentVersion): v{$ledger}");
        $this->line("  Kind: {$kind}  (on-disk is ".($kind === 'ahead' ? 'newer' : 'older').' than the ledger)');
        $this->newLine();

        if ($this->hasFailureMarker()) {
            $this->reportFailureMarker();
        }

        if (! $this->option('confirm')) {
            $this->warn("Dry-run only. Re-run with --confirm to insert a synthetic history row (installation_method='reconcile', old_version=v{$ledger}, new_version=v{$onDisk})"
                .($this->hasFailureMarker() ? ' and clear the failure marker above.' : '.'));

            return self::SUCCESS;
        }

        $row = CoreVersionHistory::create([
            'old_version' => $ledger,
            'new_version' => $onDisk,
            'files_changed_count' => 0,
            'lines_added' => 0,
            'lines_removed' => 0,
            'signing_key_changed' => false,
            'author_id_changed' => false,
            'installation_method' => 'reconcile',
            'installed_from_url' => null,
            'downloaded_sha256' => null,
            'applied_by_id' => null,
            'applied_at' => now(),
        ]);

        // currentVersion() memoizes via Cache::rememberForever, so an
        // insert alone does not surface — invalidate here so the very
        // next call picks up the new row.
        Cache::forget(CoreVersionHistory::CURRENT_VERSION_CACHE_KEY);

        $this->info("✓ Wrote synthetic history row #{$row->id}: v{$ledger} -> v{$onDisk} (installation_method=reconcile).");
        $this->line('Ledger now matches on-disk. Subsequent `dls:core:update` runs will compare targets against v'.$onDisk.'.');

        if ($this->hasFailureMarker()) {
            $this->clearFailureMarker();
        }

        return self::SUCCESS;
    }

    /**
     * Whether the core release singleton carries a failure marker — the
     * pair the Updates screen renders as the red "the update did not
     * complete" notice.
     */
    protected function hasFailureMarker(): bool
    {
        return CoreRelease::singleton()->update_failed_at !== null;
    }

    /**
     * Print what the marker says, so the operator sees what is about to
     * be cleared (or, on a dry run, what would be) rather than having a
     * warning disappear without a record of it.
     */
    protected function reportFailureMarker(): void
    {
        $state = CoreRelease::singleton();

        $this->newLine();
        $this->line('A previous update or rollback left a failure marker on the core release:');
        $this->line('  Recorded at: '.$state->update_failed_at?->format('Y-m-d H:i:s'));
        $this->line('  Reason:      '.($state->update_failure_reason ?? '(none recorded)'));
        $this->line('This is what the System Updates screen shows in red. Until now only a successful update cleared it.');
    }

    protected function clearFailureMarker(): void
    {
        CoreRelease::singleton()->forceFill([
            'update_failed_at' => null,
            'update_failure_reason' => null,
        ])->save();

        $this->info('✓ Cleared the failure marker; the System Updates screen no longer reports the interrupted run.');
    }
}
