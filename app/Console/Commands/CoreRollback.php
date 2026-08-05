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

use App\Models\CoreVersionHistory;
use App\Services\Core\CoreSourceSnapshot;
use App\Services\Core\CoreUpdater;
use App\Services\Core\CoreVendorManager;
use App\Services\Update\SystemUpdateFlash;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Roll the Dixlase Core back to the state captured before its last update.
 *
 * The CLI counterpart of dls:{plugin,theme}:rollback, for core. dls:core:update
 * retains a source snapshot plus a `.meta.json` sidecar (see
 * {@see \App\Services\Core\CoreSourceSnapshot} and CoreUpdater::update) that
 * records the from/to versions, the DB backup record, and the pre-update
 * migration batch. This command finds the most recent such rollback point and:
 *
 *   1. captures a bare pre-rollback safety snapshot (so the rollback is itself
 *      recoverable),
 *   2. restores the core source tree from the snapshot (no npm run),
 *   3. for a dependency update, re-fetches the matching vendor/ from the old
 *      release ZIP under maintenance mode (vendor.old is discarded on update
 *      success, so it cannot simply be moved back),
 *   4. reverses ONLY this update's schema via migrate:rollback --step,
 *      preserving data created since the update (a full DB restore from the
 *      retained backup is the escape hatch for data migrations),
 *   5. records a METHOD_ROLLBACK history row, and
 *   6. consumes the rollback point so a subsequent rollback steps back to the
 *      previous update.
 *
 * Designed for CLI execution only — running it from a web request would
 * replace the running code mid-flight.
 */
class CoreRollback extends Command
{
    protected $signature = 'dls:core:rollback
        {--force : Skip the confirmation prompt}
        {--applied-by= : Member id to record on the rollback history row (defaults to null for direct CLI runs)}';

    protected $description = 'Roll the Dixlase Core back to the state captured before its last update';

    public function handle(CoreSourceSnapshot $snapshotter, CoreVendorManager $vendorManager): int
    {
        $snapshotPath = $snapshotter->latestSnapshotWithMetadata();
        if ($snapshotPath === null) {
            $this->error('No core update snapshot with rollback metadata was found. Nothing to roll back to.');
            $this->line('Only updates applied by a build that records rollback metadata can be reverted with this command.');
            $this->line('To recover an older update manually, restore its source snapshot and run dls:backup:restore.');

            return self::FAILURE;
        }

        $meta = $snapshotter->readMetadata($snapshotPath);
        if ($meta === null) {
            $this->error('Rollback metadata could not be read. Aborting to avoid an ambiguous rollback.');

            return self::FAILURE;
        }

        $from = (string) ($meta['from'] ?? '');   // version to roll back TO
        $to = (string) ($meta['to'] ?? '');       // version this update installed
        $current = (string) (CoreVersionHistory::currentVersion() ?? config('app.version', '0.0.0'));

        if ($from === '') {
            $this->error('Rollback metadata is missing the target version. Aborting.');

            return self::FAILURE;
        }

        // Finding B guard (issue #171): refuse when the recorded
        // from-version is `0.0.0` — the placeholder the pre-Finding-D
        // install path used to leave in metadata when
        // `CoreVersionHistory::currentVersion()` returned NULL. A
        // rollback with from='0.0.0' would either:
        //   - re-fetch vendor from GitHub release `v0.0.0` (which does
        //     not exist) → 404 → rollback fails mid-way with `vendor/`
        //     left in an interim broken state (dependencyUpdate=true),
        //   - or write `new_version='0.0.0'` into the history row →
        //     `currentVersion()` reads 0.0.0 forever after (all paths).
        // Refuse before touching anything and point the operator at the
        // supported recovery path.
        if ($from === '0.0.0') {
            $this->error("Rollback target v{$from} is not a resolvable release tag.");
            $this->line('The recorded from-version is 0.0.0 — usually left over from a fresh install that predated the VERSION file / a baseline history row.');
            $this->line('Rollback needs to re-fetch vendor/ from the from-version\'s release ZIP (`v0.0.0` does not exist on GitHub), and would also write `0.0.0` back into the ledger.');
            $this->newLine();
            $this->line('Fix:');
            $this->line('  1. Run `php artisan dls:core:reconcile --confirm` so the ledger records the on-disk VERSION.');
            $this->line('  2. Apply the next update; that update\'s snapshot will carry a real from-version, and subsequent rollbacks will work normally.');
            $this->line('Alternatively, restore manually from a full backup captured before the last update.');

            return self::FAILURE;
        }

        $this->warn('Rollback is a DESTRUCTIVE operation that replaces the current core source tree.');
        $this->line("Core now:      v{$current}");
        $this->line("Roll back to:  v{$from}  (undoing the update v{$from} -> v{$to})");
        $this->line('Snapshot:      '.basename($snapshotPath));

        if ($to !== '' && $current !== '' && version_compare($current, $to, '!=')) {
            $this->warn("Current version v{$current} does not match this update's target v{$to}; a newer change may have landed since. Rolling back the most recent recorded update anyway.");
        }

        if (! $this->option('force') && ! $this->confirm('Continue with rollback?', false)) {
            $this->info('Rollback cancelled.');

            return self::SUCCESS;
        }

        $dependencyUpdate = (bool) ($meta['dependency_update'] ?? false);
        $maintenanceOn = false;
        $safetySnapshot = null;

        try {
            // Capture the current (post-update) state first so the rollback
            // is itself recoverable if a later step fails. This is a bare
            // snapshot with no metadata sidecar, so it is never itself
            // picked up as a rollback point.
            $this->line('Capturing pre-rollback safety snapshot...');
            $safetySnapshot = $snapshotter->capture();
            $this->line("Safety snapshot at {$safetySnapshot}");

            // Source restore mid-rsync — even without a vendor refetch —
            // leaves any HTTP request landing in the window at risk of a
            // fatal require: bootstrap/app.php reads config/trustedproxy.php
            // *before* the framework boots, so a one-second gap is enough
            // to fatal. On slow bind-mounts the gap stretches to minutes
            // (Round 4 Finding D observed both AssetHelper and
            // trustedproxy fatals during this window). Bracket the whole
            // apply + swap + migrate + cache clear unconditionally so the
            // safety does not depend on whether the original update was a
            // dependency update or on the operator's disk speed.
            $this->line('Entering maintenance mode...');
            Artisan::call('down', ['--retry' => 60, '--refresh' => 15]);
            $maintenanceOn = true;

            // Round 5 residual: prime the FPM SAPI so workers see the
            // maintenance sentinel before any source file moves. On the
            // rollback path this window was worse than on the update
            // path — the sandbox measured ~5 s of 200 responses after
            // `down` before the entry stabilised, and requests slipped
            // through into a mid-restore tree and fataled
            // (`include(app/Enums/MenuVisibility.php)`,
            // `include(GuardAwareDatabaseSessionHandler.php)`). Kicking
            // PhpFpmReloader here closes the entry window without
            // waiting for stat caches to age out on their own. See PR-Q
            // for the primary fix (atomic swap in
            // CoreSourceSnapshot::replaceLiveDirectory) — this hook is
            // belt-and-braces.
            $this->line('Priming FPM cache after maintenance sentinel write...');
            app(\App\Services\Core\PhpFpmReloader::class)->reload();

            $this->line('Restoring core source from snapshot...');
            $snapshotter->restore($snapshotPath);
            $this->info('Core source restored (no npm run).');

            // Reset opcache after the source swap so the migrate step
            // below and any callers reaching into the freshly-restored
            // files see the rolled-back copies rather than opcache-cached
            // entries from the just-replaced tree.
            if (function_exists('opcache_reset')) {
                @opcache_reset();
                $this->line('Reset opcache after source restore.');
            }

            if ($dependencyUpdate) {
                // vendor.old was discarded when the update succeeded, so we
                // re-fetch the matching dependencies from the OLD release ZIP
                // (the same mechanism dls:backup:restore --refetch-vendor uses).
                $this->line("Re-fetching vendor/ to match v{$from}...");
                $vendorManager->refetchAndSwap($from, fn (string $l) => $this->line($l));
                $this->info('vendor/ restored to match the rolled-back source.');
            }

            $this->rollbackSchema($meta);

            $this->line('Clearing caches...');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            Artisan::call('cache:clear');

            // Round 5 Finding D residual: refresh the PHP-FPM SAPI
            // (opcache SHM + realpath cache) before lifting maintenance
            // so the first post-maintenance request does not see stale
            // classmap entries pointing at the just-restored files.
            // Best-effort — never aborts the rollback.
            $this->line('Refreshing PHP-FPM cache...');
            app(\App\Services\Core\PhpFpmReloader::class)->reload();

            if ($maintenanceOn) {
                $this->line('Lifting maintenance mode...');
                Artisan::call('up');
                $maintenanceOn = false;
            }

            $appliedBy = $this->option('applied-by');
            CoreVersionHistory::create([
                'old_version' => $current !== '' ? $current : $to,
                'new_version' => $from,
                'installation_method' => CoreVersionHistory::METHOD_ROLLBACK,
                'applied_by_id' => ($appliedBy !== null && $appliedBy !== '') ? (int) $appliedBy : null,
                'applied_at' => now(),
            ]);

            // Consume the rollback point so a subsequent dls:core:rollback
            // steps back to the PREVIOUS update rather than repeating this
            // one. The DB backup is retained as a safety net.
            $snapshotter->discard($snapshotPath);

            // Record completion for the System Updates page's one-shot
            // "rollback complete" flash. Mirrors CoreUpdater: the web UI runs
            // this rollback detached and cannot flash directly, so this is
            // written before the finally block clears the in-progress flag —
            // index() surfaces it as soon as the flag is gone. 'operation' =>
            // 'rollback' selects the rollback message over the update one.
            SystemUpdateFlash::record([
                'status' => 'success',
                'kind' => 'core',
                'operation' => 'rollback',
                'from' => $current !== '' ? $current : $to,
                'to' => $from,
            ]);

            $this->info("Core rolled back to v{$from}.");
            $this->line("Pre-rollback state kept at: {$safetySnapshot}");
            if (! empty($meta['backup_record_id'])) {
                $this->line('If a data migration also needs reversing, restore the full DB backup:');
                $this->line("  php artisan dls:backup:restore {$meta['backup_record_id']} --targets=database");
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Rollback failed: {$e->getMessage()}");

            if ($safetySnapshot !== null) {
                $this->line('Attempting to restore the pre-rollback state...');
                try {
                    $snapshotter->restore($safetySnapshot);
                    $this->info('Pre-rollback state restored — the core is back where it was before this command ran.');
                } catch (\Throwable $recoverError) {
                    $this->error("Recovery also failed: {$recoverError->getMessage()}");
                    $this->line("Manual recovery required from: {$safetySnapshot}");
                }
            }

            if ($maintenanceOn) {
                try {
                    Artisan::call('up');
                } catch (\Throwable) {
                    $this->line('Run `php artisan up` manually to restore access.');
                }
            }

            return self::FAILURE;
        } finally {
            // Clear the in-progress flag the admin UI raises before spawning
            // a detached rollback, so the next page render leaves the polling
            // placeholder. Reuses the core-update flag path (a rollback also
            // replaces resources/ mid-flight). CLI runs never set it, so this
            // is a no-op for them — mirrors CoreUpdater::update()'s finally.
            @unlink(CoreUpdater::inProgressFlagPath());
        }
    }

    /**
     * Reverse only the migrations this update added — those in a batch newer
     * than the pre-update batch recorded in the snapshot metadata. Data
     * created since the update is preserved; the retained full DB backup is
     * the escape hatch when a data migration must be undone too. No-ops when
     * the update added no migrations or the metadata predates batch tracking.
     *
     * @param  array<string, mixed>  $meta
     */
    private function rollbackSchema(array $meta): void
    {
        if (! array_key_exists('max_batch', $meta) || $meta['max_batch'] === null) {
            $this->warn('Snapshot has no migration-batch metadata; skipping schema rollback. Verify the schema, and use dls:backup:restore if a data migration must be reversed.');

            return;
        }

        $backupBatch = (int) $meta['max_batch'];
        $migrationsSince = $this->migrationsAppliedSince($backupBatch);

        if ($migrationsSince === 0) {
            $this->line('No schema rollback needed — the update added no new migrations.');

            return;
        }

        $this->line("Reversing {$migrationsSince} migration(s) applied by the update...");
        Artisan::call('migrate:rollback', [
            '--path' => 'database/migrations',
            '--step' => $migrationsSince,
            '--force' => true,
        ]);
        $this->line('Schema rollback complete.');
    }

    /**
     * Count of core migrations applied after the given batch — i.e. the exact
     * number of migrations dls:core:update added, so migrate:rollback --step
     * reverses those and no earlier ones. Core migrations live in the
     * `migrations` table (plugin/theme migrations use their own tables), so
     * this is purely core. Zero when the table is missing.
     */
    private function migrationsAppliedSince(int $batch): int
    {
        try {
            return (int) DB::table('migrations')->where('batch', '>', $batch)->count();
        } catch (\Throwable) {
            return 0;
        }
    }
}
