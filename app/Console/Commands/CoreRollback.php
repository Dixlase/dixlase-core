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

use App\Helpers\ComposerLocalHelper;
use App\Models\AuditLog;
use App\Models\CoreVersionHistory;
use App\Services\Core\ArtisanProcess;
use App\Services\Core\CoreIntegrityBaselineRefresher;
use App\Services\Core\CoreMaintenanceGuard;
use App\Services\Core\CoreSourceSnapshot;
use App\Services\Core\CoreUpdateAudit;
use App\Services\Core\CoreUpdater;
use App\Services\Core\CoreVendorManager;
use App\Services\Core\PublicAssetRelinker;
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
 *      success, so it cannot simply be moved back) — the ZIP is downloaded
 *      and checked for a prebuilt vendor/ before step 1, so a release that
 *      lacks one is refused without changing anything,
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
        $vendorZip = null;
        $schemaTouched = false;
        $vendorSwapped = false;

        // Resolve the subprocess launcher and exercise it once while the tree
        // is still the version this process is running. A rollback replaces
        // app/ with an older release's, and ArtisanProcess is newer than any
        // release it can roll back to: resolving it after the source restore
        // failed with "include(.../ArtisanProcess.php): Failed to open
        // stream", which left a v0.3.54 to v0.3.53 rollback half-applied and
        // the site on 500. Holding the instance keeps the class - and
        // everything run() reaches - in memory for the rest of the command.
        $artisan = app(ArtisanProcess::class);
        $launcherBoots = $artisan->boots();
        if (! $launcherBoots) {
            $this->warn('A new PHP process could not boot the application before the rollback started; the post-restore boot check will be skipped.');
        }

        try {
            // A dependency rollback re-fetches vendor/ from the target
            // release. Check that the release actually ships one before
            // anything changes: a release without the prebuilt-vendor asset
            // resolves to a source-only zipball, and that used to surface only
            // after maintenance, the schema rollback and the source restore,
            // aborting the rollback half-way. The ZIP is reused for the swap.
            if ($dependencyUpdate) {
                $this->line("Checking that v{$from} ships prebuilt dependencies...");
                $vendorZip = $vendorManager->prefetchVerifiedRelease($from);
            }

            // Capture the current (post-update) state first so the rollback
            // is itself recoverable if a later step fails. This is a bare
            // snapshot with no metadata sidecar, so it is never itself
            // picked up as a rollback point.
            // Same rule as the update: regenerate the integrity baseline after
            // the rollback only if the tree matched it before anything changed.
            $integrityMatchedBefore = $this->integrityMatchesBaseline();

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
            // Record this process as the window's owner before `down` so
            // that, if we die mid-rollback, dls:core:heal-maintenance can
            // lift the window; the secret gives an operator a bypass URL
            // in the meantime (see CoreMaintenanceGuard).
            $maintenanceSecret = app(CoreMaintenanceGuard::class)->claim(CoreMaintenanceGuard::OPERATION_ROLLBACK, $from);
            Artisan::call('down', ['--retry' => 60, '--refresh' => 15, '--secret' => $maintenanceSecret]);
            $maintenanceOn = true;
            $this->line('Operator bypass URL while in maintenance: '.CoreMaintenanceGuard::bypassUrl($maintenanceSecret));

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

            // Round 6 fix: reverse the SCHEMA before restoring the source.
            // The `down()` implementation for every migration this update
            // added lives in the NEW (about-to-be-rolled-back) version's
            // migration files. If we restore the old source first,
            // `migrate:rollback` loads its target list from
            // `dls_migrations` but cannot find the files on disk, so it
            // silent-skips each one — the schema stays applied, the row
            // stays in `dls_migrations`, and the command reports
            // "Schema rollback complete" while the update's schema
            // changes remain live.
            //
            // Running `rollbackSchema()` first mirrors the update path
            // (update = apply new source, then run its `up()`; rollback
            // = run the new `down()`, then restore old source) and has
            // the bonus that a `migrate:rollback` failure aborts before
            // any file on disk changes — the operator can retry against
            // a still-consistent state.
            //
            // vendor and cache clears stay AFTER the source restore so
            // they operate on the rolled-back tree.
            $schemaTouched = true;
            $this->rollbackSchema($meta);

            $this->line('Restoring core source from snapshot...');
            $snapshotter->restore($snapshotPath);
            $this->info('Core source restored (no npm run).');

            // Reset opcache after the source swap so any classes
            // referenced by the CLI process for the remainder of this
            // command (cache-clear artisan calls below, the vendor
            // refetch closure, etc.) resolve against the rolled-back
            // tree rather than opcache-cached entries from the just-
            // replaced files. `rollbackSchema()` above ran before the
            // swap and did not depend on this reset.
            if (function_exists('opcache_reset')) {
                @opcache_reset();
                $this->line('Reset opcache after source restore.');
            }

            // The snapshot never carried public/assets/themes/<Theme> or
            // public/assets/plugins/<Plugin>: CoreSourceSnapshot::capture()
            // skips symlinks, so the restored public/ has no link at those
            // paths and every theme/plugin asset 404s (front page unstyled,
            // `appearanceTheme is not defined`). Re-create them. Outside the
            // $dependencyUpdate branch — the source restore happens on every
            // rollback. Non-fatal.
            $this->relinkPublicAssets();

            if ($dependencyUpdate) {
                // Hold the operator at the 503 page too while vendor/ and the
                // extension autoload disagree (see suspendOperatorBypass()).
                $bypass = app(CoreMaintenanceGuard::class)->suspendOperatorBypass();

                try {
                    // vendor.old was discarded when the update succeeded, so we
                    // re-fetch the matching dependencies from the OLD release ZIP
                    // (the same mechanism dls:backup:restore --refetch-vendor uses).
                    $this->line("Re-fetching vendor/ to match v{$from}...");
                    $vendorManager->refetchAndSwap($from, fn (string $l) => $this->line($l), null, $vendorZip);
                    $vendorSwapped = true;
                    $this->info('vendor/ restored to match the rolled-back source.');

                    // The re-fetched vendor/composer/autoload_psr4.php is the old
                    // release's pristine copy and lacks the site-local theme/plugin
                    // PSR-4 the installer persisted; re-persist it or extension
                    // admin/settings pages 500 after rollback (same as the update
                    // path). Non-fatal.
                    $this->line('Re-syncing extension autoload (theme/plugin PSR-4) after vendor swap...');
                    if (! ComposerLocalHelper::syncAutoload()) {
                        $this->warn('Extension autoload re-sync failed; run `composer dump-autoload` if theme/plugin pages error.');
                    }

                    // Same as the update path: the re-fetched vendor/ can lack a
                    // package the stale bootstrap/cache/packages.php still lists.
                    // See ComposerLocalHelper::rebuildPackageManifest().
                    $this->line('Rebuilding the package-discovery manifest after vendor swap...');
                    if (! ComposerLocalHelper::rebuildPackageManifest()) {
                        $this->warn('Package manifest rebuild failed; if the site returns 500, delete bootstrap/cache/packages.php and bootstrap/cache/services.php, then run `php artisan package:discover`.');
                    }
                } finally {
                    app(CoreMaintenanceGuard::class)->restoreOperatorBypass($bypass);
                }
            }

            // The restored source carries the release's empty placeholder for
            // the plugin Tailwind sources; rebuild it from the enabled
            // plugins (see CoreUpdater). Non-fatal.
            $this->line('Regenerating plugin Tailwind sources...');
            try {
                if ($vendorSwapped) {
                    $artisan->run('dls:tailwind:regenerate-plugin-sources');
                } else {
                    Artisan::call('dls:tailwind:regenerate-plugin-sources');
                }
            } catch (\Throwable $sourcesError) {
                $this->warn('Plugin Tailwind sources were not regenerated ('.$sourcesError->getMessage().'); run `php artisan dls:tailwind:regenerate-plugin-sources`.');
            }

            $this->line('Clearing caches...');
            if ($vendorSwapped) {
                // This process booted from the vendor/ that was just
                // replaced; compiling Blade here goes through the providers
                // it registered, which may belong to packages that are gone.
                // Run the clears in a new process (see ArtisanProcess).
                foreach (['config:clear', 'route:clear', 'view:clear', 'view:cache', 'cache:clear'] as $clear) {
                    $artisan->run($clear);
                }
            } else {
                Artisan::call('config:clear');
                Artisan::call('route:clear');
                // view:clear + view:cache via the shared helper. The rebuild
                // step avoids the dev-env "click a menu, land back on the
                // same page" symptom (Vite watches storage/framework/views/
                // during `npm run dev` and cancels any navigation whose
                // Blade view compiles on demand mid-flight).
                \App\Services\View\CompiledViewCacheRebuilder::rebuild();
                Artisan::call('cache:clear');
            }

            // Round 5 Finding D residual: refresh the PHP-FPM SAPI
            // (opcache SHM + realpath cache) before lifting maintenance
            // so the first post-maintenance request does not see stale
            // classmap entries pointing at the just-restored files.
            // Best-effort — never aborts the rollback.
            $this->line('Refreshing PHP-FPM cache...');
            app(\App\Services\Core\PhpFpmReloader::class)->reload();

            // Ask a new process to boot the rolled-back tree before the site
            // is let back in. Every step above can report success and still
            // leave the application unbootable - a stale manifest in
            // bootstrap/cache is enough - and until now the first thing to
            // notice was a visitor. Only trusted when the same check passed
            // before the rollback started, so a launcher that never worked in
            // this environment cannot turn a good rollback into a recovery.
            if ($launcherBoots && ! $artisan->boots()) {
                throw new \RuntimeException('the rolled-back core does not boot in a new process');
            }

            if ($maintenanceOn) {
                $this->line('Lifting maintenance mode...');
                Artisan::call('up');
                $maintenanceOn = false;
                app(CoreMaintenanceGuard::class)->release();
            }

            $appliedBy = $this->option('applied-by');
            CoreVersionHistory::create([
                'old_version' => $current !== '' ? $current : $to,
                'new_version' => $from,
                'installation_method' => CoreVersionHistory::METHOD_ROLLBACK,
                'applied_by_id' => ($appliedBy !== null && $appliedBy !== '') ? (int) $appliedBy : null,
                'applied_at' => now(),
            ]);

            CoreUpdateAudit::record(AuditLog::ACTION_CORE_ROLLED_BACK, true, $current !== '' ? $current : $to, $from, $this->appliedById(), [
                'dependency_update' => $dependencyUpdate,
                'backup_record_id' => $meta['backup_record_id'] ?? null,
            ]);

            app(CoreIntegrityBaselineRefresher::class)->refreshAfter(
                $integrityMatchedBefore,
                $this->appliedById(),
                fn (string $line) => $this->line($line),
            );

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

            // Nothing on disk or in the schema changes before the safety
            // snapshot, so a failure that early (e.g. the prebuilt-vendor
            // check) leaves the core exactly as it was.
            $recovered = $safetySnapshot === null;

            if ($safetySnapshot !== null) {
                $this->line('Attempting to restore the pre-rollback state...');
                try {
                    $snapshotter->restore($safetySnapshot);

                    // The swap retained the post-update vendor/ as vendor.old;
                    // put it back so it matches the restored source.
                    if ($vendorSwapped) {
                        $vendorManager->restorePrevious();
                        $this->line('Restored the pre-rollback vendor/.');
                    }

                    // Same as the success path: the safety snapshot has no
                    // asset symlinks either.
                    $this->relinkPublicAssets();

                    // bootstrap/cache/packages.php and services.php describe
                    // whichever vendor/ was in place when they were written,
                    // and a source restore deliberately keeps the live copies.
                    // After a recovery that put a different vendor/ back they
                    // list providers that are gone: the command reported the
                    // pre-rollback state restored while every request died on
                    // `Class "Laravel\Tinker\TinkerServiceProvider" not found`.
                    $this->refreshPackageManifest($artisan);

                    $recovered = $this->reapplySchema($schemaTouched);

                    if ($recovered && $launcherBoots && ! $artisan->boots()) {
                        $recovered = false;
                        $this->error('The restored core still does not boot in a new process.');
                        $this->line("Manual recovery required from: {$safetySnapshot}");
                    }

                    if ($recovered) {
                        $this->info('Pre-rollback state restored — the core is back where it was before this command ran.');
                    }
                } catch (\Throwable $recoverError) {
                    $this->error("Recovery also failed: {$recoverError->getMessage()}");
                    $this->line("Manual recovery required from: {$safetySnapshot}");
                }
            }

            // The web UI runs this command detached and can only report what
            // it finds recorded here — without it a failed rollback returned
            // to the page with no message at all. Written before the finally
            // block clears the in-progress flag, like the success record.
            try {
                SystemUpdateFlash::record([
                    'status' => 'error',
                    'kind' => 'core',
                    'operation' => 'rollback',
                    'from' => $current !== '' ? $current : $to,
                    'to' => $from,
                    'error' => $e->getMessage(),
                    'recovered' => $recovered,
                ]);
            } catch (\Throwable) {
                // Reporting must not mask the rollback failure itself.
            }

            CoreUpdateAudit::record(AuditLog::ACTION_CORE_ROLLBACK_FAILED, false, $current !== '' ? $current : $to, $from, $this->appliedById(), [
                'error' => mb_substr($e->getMessage(), 0, 1000),
                'recovered' => $recovered,
            ]);

            if ($maintenanceOn) {
                try {
                    Artisan::call('up');
                    // Only drop the owner record once `up` succeeded: if it
                    // threw, the record is what lets the scheduled self-heal
                    // lift the window after this process exits.
                    app(CoreMaintenanceGuard::class)->release();
                } catch (\Throwable) {
                    $this->line('Run `php artisan up` manually to restore access (or wait: dls:core:heal-maintenance lifts it within a minute).');
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
     * Whether the core tree matches its integrity baseline before the
     * rollback; a hashing failure counts as "did not match".
     */
    private function integrityMatchesBaseline(): ?bool
    {
        try {
            return app(CoreIntegrityBaselineRefresher::class)->treeMatchesBaseline();
        } catch (\Throwable $e) {
            $this->warn('Could not compare core files with the integrity baseline: '.$e->getMessage());

            return false;
        }
    }

    private function appliedById(): ?int
    {
        $appliedBy = $this->option('applied-by');

        return ($appliedBy !== null && $appliedBy !== '') ? (int) $appliedBy : null;
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
     * Rebuild the package-discovery manifests against the tree on disk now.
     *
     * bootstrap/cache/packages.php and services.php are written from whatever
     * vendor/ was installed when they were last generated, and a source
     * restore keeps the live copies on purpose (bootstrap/cache is a
     * protected path). So a recovery that puts a different vendor/ back is
     * left with manifests describing the rejected one, and every request
     * fatals on a provider whose package is no longer there.
     *
     * The files are deleted first: Laravel rebuilds a missing manifest on the
     * next boot, so even a failing package:discover leaves a working site
     * rather than a poisoned one. The rebuild runs in a new process, which
     * boots from the restored vendor/ instead of the one this process loaded.
     *
     * ComposerLocalHelper::rebuildPackageManifest() warns that package:discover
     * must not run in a new process, because that process would boot from the
     * stale manifest and die on a provider that is gone before the command
     * ran. Deleting the files first is what makes a new process safe here:
     * with no manifest on disk the framework builds one from the restored
     * vendor/composer/installed.json as it boots.
     */
    private function refreshPackageManifest(ArtisanProcess $artisan): void
    {
        foreach (['packages.php', 'services.php'] as $file) {
            $path = base_path('bootstrap/cache/'.$file);
            if (is_file($path)) {
                @unlink($path);
            }
        }

        try {
            $artisan->run('package:discover');
            $this->line('Rebuilt the package-discovery manifest for the restored tree.');
        } catch (\Throwable $e) {
            // The manifests are gone, so the framework regenerates them on
            // the next request; say so rather than implying the site is down.
            $this->warn("Could not rebuild the package manifest ({$e->getMessage()}); it will be regenerated on the next request.");
        }
    }

    /**
     * Re-run the migrations a failed rollback already reversed.
     *
     * rollbackSchema() runs before the source restore, so a failure after it
     * left the restored post-update source with its migrations pending.
     * Called once the safety snapshot is back, so the migration files are on
     * disk again. Returns false when the schema could not be re-applied.
     */
    private function reapplySchema(bool $schemaTouched): bool
    {
        if (! $schemaTouched) {
            return true;
        }

        $this->line('Re-applying the migrations reversed before the failure...');

        try {
            Artisan::call('migrate', [
                '--path' => 'database/migrations',
                '--force' => true,
            ]);
            $this->line('Schema re-applied.');

            return true;
        } catch (\Throwable $e) {
            $this->error("Re-applying the schema failed: {$e->getMessage()}");
            $this->line('Run `php artisan migrate --force` once the site is reachable.');

            return false;
        }
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

    /**
     * Recreate the public asset symlinks the restored snapshot cannot carry.
     *
     * CoreSourceSnapshot::capture() skips symlinks by design (they point at
     * protected paths outside the snapshot's scope), so a restored public/
     * has no public/assets/themes/<Theme> or public/assets/plugins/<Plugin>
     * link and every extension asset 404s. Non-fatal: a rollback that
     * restored the source has done the important part, and the operator can
     * re-run the symlink commands by hand.
     */
    protected function relinkPublicAssets(): void
    {
        $this->line('Relinking public asset symlinks (storage, themes, plugins)...');

        try {
            $relinked = (new PublicAssetRelinker())->relink();
            $this->line("Relinked public assets (themes: {$relinked['themes']}, plugins: {$relinked['plugins']}).");
        } catch (\Throwable $e) {
            $this->warn('Public asset relink failed ('.$e->getMessage().'); if the front page renders unstyled, run `php artisan dls:theme:symlink create --all` and `php artisan dls:plugin:symlink create --all`.');
        }
    }
}
