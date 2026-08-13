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

namespace App\Services\Core;

use App\Contracts\Backup\BackupServiceInterface;
use App\Helpers\ComposerLocalHelper;
use App\Models\CoreRelease;
use App\Models\CoreVersionHistory;
use App\Models\Theme;
use App\Services\Extension\ExtensionSourceManager;
use App\Services\Update\SystemUpdateFlash;
use Closure;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use ZipArchive;

/**
 * Orchestrates a Dixlase Core upgrade end-to-end.
 *
 * Steps:
 *   1. Resolve target version (from `core_releases.available_version`)
 *   2. Capture a source snapshot (so we can roll back source files)
 *   3. Download the release ZIP from the recorded source
 *   4. Extract into a staging directory and validate it looks like a core
 *   5. Apply staging over the live tree (replaces source dirs only)
 *   6. For a dependency update (composer.lock changed), enter maintenance
 *      mode and swap in the release's prebuilt vendor/ (production has no
 *      guarantee of Composer/Node, so dependencies ship ready-to-run)
 *   7. Run migrations + clear caches
 *   8. Record a new core_version_history row + update core_releases
 *   9. On any failure, restore source from snapshot, vendor/ from
 *      vendor.old, lift maintenance, and re-throw
 *
 * This is intentionally CLI-driven — running it from a web request would
 * replace the running code mid-flight.
 */
class CoreUpdater
{
    public function __construct(
        protected ExtensionSourceManager $sourceManager,
        protected CoreSourceSnapshot $snapshotter,
        protected BackupServiceInterface $backupService,
        protected CoreVendorManager $vendorManager,
        protected PhpFpmReloader $fpmReloader,
    ) {}

    /**
     * @param  ?Closure(string): void  $log  Optional sink for progress lines
     * @param  bool  $allowDowngrade  Override the on-disk version guard (Finding #1). Reserved for
     *                                deliberate rollback scenarios; the default `false` refuses any
     *                                target older than the code actually on disk.
     * @return array{from: ?string, to: string, snapshot: string, history_id: int, backup_record_id: ?int}
     */
    public function update(?string $version = null, ?int $appliedById = null, ?int $existingDbBackupId = null, ?Closure $log = null, bool $allowDowngrade = false): array
    {
        $log ??= fn (string $line) => null;

        $coreState = CoreRelease::singleton();
        $version ??= $coreState->available_version;

        if ($version === null) {
            throw new RuntimeException('No core update is currently available.');
        }

        $current = (string) (CoreVersionHistory::currentVersion() ?? config('app.version', '0.0.0'));

        if (version_compare($version, $current, '<=')) {
            throw new RuntimeException("Target v{$version} is not newer than current v{$current}.");
        }

        // On-disk version guard (Finding #1 from the 2026-07-27 sandbox
        // incident). The DB-side pointer above (CoreVersionHistory) can go
        // stale when a checkout is advanced via `git` rather than through
        // dls:core:update — the DB then reads e.g. `0.2.4` while the actual
        // running code is `0.3.1+`. The DB check therefore lets a
        // destructive downgrade pass ("v0.2.5 > v0.2.4, proceed") and the
        // updater deletes classes it is still executing against, aborting
        // mid-apply. Cross-check the target against a VERSION file
        // committed at the repo root; when the file is present and the
        // target would move backwards, refuse unless the caller explicitly
        // opts in via $allowDowngrade. When the file is absent (very early
        // installs before this landed, or a stripped release), the guard
        // no-ops so backward compatibility is preserved.
        $onDisk = static::readVersionFromDisk();
        if ($onDisk !== null && version_compare($version, $onDisk, '<')) {
            if (! $allowDowngrade) {
                throw new RuntimeException(
                    "Refusing to apply v{$version}: on-disk code is v{$onDisk} which is newer. "
                    .'This would be a downgrade; pass --allow-downgrade to override.'
                );
            }
            $log("WARNING: applying downgrade v{$onDisk} -> v{$version} (--allow-downgrade set).");
        }

        $log('Capturing source snapshot...');
        $snapshotPath = $this->snapshotter->capture();
        $log("Snapshot captured at {$snapshotPath}");

        // Hold the source snapshot path in a variable the bundled-theme
        // loops below never reassign (they reuse $snapshotPath as their
        // foreach value), so the rollback metadata is written against the
        // real source snapshot regardless of whether the release ships
        // bundled themes.
        $sourceSnapshotPath = $snapshotPath;

        // Highest core migration batch BEFORE this update's migrate step, so
        // dls:core:rollback can step back exactly the migrations this update
        // adds and no more. Captured up front (it only changes at migrate).
        $preMigrateBatch = $this->currentMigrationBatch();

        $stagingPath = storage_path('app/private/core-update/staging/'.now()->format('YmdHis_').uniqid());
        $backupRecordId = null;

        // Tracks whether this run swapped vendor/ (a "dependency update")
        // and whether the site was put into maintenance mode, so the catch
        // block can undo both precisely. A dependency update is detected by
        // comparing the staged composer.lock against the live one.
        $dependencyUpdate = false;
        $vendorSwapped = false;
        $maintenanceOn = false;

        // Slug => absolute path of the retained pre-apply copy of each
        // theme directory. Populated when a release ships bundled themes
        // (the manifest declares them, see ReleaseManifest); an empty
        // array on rollback means "no theme was touched, nothing to
        // restore." On success the catch block discards each retained
        // copy just like vendor.old.
        $themeSnapshotPaths = [];

        try {
            $log("Downloading core v{$version}...");
            $zipPath = $this->sourceManager->downloadCore($version);
            $log("Downloaded to {$zipPath}");

            // Foundation for future update-integrity verification: record
            // the SHA-256 of what was actually pulled off the network,
            // BEFORE extract, so a later release that adds signature
            // verification can retro-audit past updates against the
            // authority's published hash. Storage happens in the history
            // row further down; log it here so operators can copy it
            // out of the `dls:core:update` transcript for immediate
            // manual verification against a trusted source.
            $downloadedSha256 = hash_file('sha256', $zipPath) ?: null;
            if ($downloadedSha256 !== null) {
                $log("Downloaded SHA-256: {$downloadedSha256}");
            }

            $log('Extracting to staging directory...');
            $this->extractToStaging($zipPath, $stagingPath);
            $log("Extracted to {$stagingPath}");

            $log('Validating extracted payload...');
            $payloadRoot = $this->validateStagedPayload($stagingPath);
            $log("Validated payload at {$payloadRoot}");

            // A "dependency update" is one whose composer.lock differs from
            // the installed one — i.e. the release ships a different set of
            // PHP packages (a Laravel major bump, a new dependency, a
            // security patch). Production installs are not guaranteed to
            // have Composer or Node available, so we never run `composer
            // install`; instead the release ZIP ships a ready-to-run
            // vendor/ that we swap in wholesale. When the lock is unchanged
            // we skip the (large, slow) vendor swap entirely and behave
            // exactly as before.
            $dependencyUpdate = $this->vendorManager->lockChanged($payloadRoot);
            if ($dependencyUpdate) {
                if (! is_dir($payloadRoot.'/vendor')) {
                    throw new RuntimeException(
                        'composer.lock changed but the release ZIP ships no vendor/ directory; '.
                        'refusing to apply a dependency update without prebuilt dependencies.'
                    );
                }
                $log('Dependency change detected (composer.lock differs) — vendor/ will be swapped under maintenance mode.');
            } else {
                $log('No dependency change (composer.lock unchanged) — vendor/ left untouched.');
            }

            // Ensure there is a database restore point before mutating the
            // live tree, so a failed migration / boot can be rolled back via
            // dls:backup:restore plus the source snapshot above.
            //
            // When the web UI's applyCore() already captured a
            // source-inclusive [core_source, database] backup synchronously,
            // it passes that record's id here. That single record is both
            // the operator's rollback point AND a database restore point, so
            // we reuse it instead of taking a second, DB-only snapshot —
            // which used to surface as a confusing duplicate entry in the
            // backup list. Direct CLI runs (no id) still get the internal
            // safety-net snapshot below.
            if ($existingDbBackupId !== null) {
                $backupRecordId = $existingDbBackupId;
                $log("Reusing pre-update backup #{$backupRecordId} as the database restore point (skipping internal DB snapshot).");
            } else {
                $log('Capturing database backup before applying core...');
                $backupResult = $this->backupService->backup(
                    [BackupServiceInterface::TARGET_DATABASE],
                    ['note' => __('admin/settings/systems/backup/index.auto_note.core_update_db', [
                        'current' => $current,
                        'version' => $version,
                    ])],
                );
                if ($backupResult->success) {
                    $backupRecordId = $backupResult->backupRecordId;
                    $log("Database backup captured (record id: {$backupRecordId}, file: {$backupResult->filePath})");
                } else {
                    $log('WARNING: database backup failed: '.($backupResult->error ?? 'unknown error'));
                    $log('Continuing without backup — manual rollback will not be possible if migrations fail.');
                }
            }

            // Source apply mid-rsync (even without a vendor swap) leaves
            // any HTTP request landing in the window at risk of a fatal
            // require: classes and config files disappear for a beat as
            // rsync replaces them. On a fast local disk this window is
            // milliseconds and workers absorb it silently, but on a slow
            // bind-mount (Docker on macOS, some NFS setups) it stretches
            // to minutes — the sandbox saw `require(AssetHelper.php)`
            // and `require(config/trustedproxy.php)` fatals during
            // exactly this window (Round 4 Finding D). public/index.php
            // checks for storage/framework/maintenance.php *before*
            // booting the framework, so `down` returns a static 503 for
            // every incoming request while apply + swap + migrate + the
            // subsequent cache clears finish. The bracket now runs
            // unconditionally so the safety it provides does not depend
            // on the operator's disk speed or on whether composer.lock
            // actually changed.
            $log('Entering maintenance mode...');
            // --refresh makes the 503 page reload every 15s so the
            // operator's browser returns to the site automatically once
            // the swap finishes and maintenance is lifted.
            Artisan::call('down', ['--retry' => 60, '--refresh' => 15]);
            $maintenanceOn = true;

            // Round 5 residual: force the FPM SAPI to see the maintenance
            // sentinel BEFORE any source file moves. `Artisan::call('down')`
            // wrote the sentinel from the CLI SAPI, but FPM workers might
            // still be serving requests with their pre-down class map /
            // stat cache; the PR-R stat-cache bypass in public/index.php
            // helps but does not cover the composer-autoload layer that
            // runs before Laravel boots. Kicking PhpFpmReloader here
            // closes the entry-side window that the sandbox dryrun-13/14
            // verification measured (~4-9 s of 200 responses under
            // maint=ON with mid-swap fatals). Best-effort — same as the
            // exit-side reload below.
            $log('Priming FPM cache after maintenance sentinel write...');
            $this->fpmReloader->reload();

            $log('Applying source over live tree...');
            $this->applyToLiveTree($payloadRoot);
            $log('Applied source.');

            // PHP-FPM / opcache may hold stale entries for the just-swapped
            // files (particularly on shared realpath caches or when
            // opcache.validate_timestamps is off). Reset in-process so the
            // migrate step below re-parses the fresh source; a full FPM
            // reload is still the operator's responsibility on
            // validate_timestamps=0 hosts (CoreUpdate command prints that
            // hint separately).
            if (function_exists('opcache_reset')) {
                @opcache_reset();
                $log('Reset opcache after source apply.');
            }

            if ($dependencyUpdate) {
                $log('Swapping vendor/ (prebuilt dependencies from the release)...');
                $this->vendorManager->swap($payloadRoot);
                $vendorSwapped = true;
                $log('vendor/ swapped (previous vendor/ retained at vendor.old for rollback).');

                // The swapped-in vendor/composer/autoload_psr4.php is the
                // release's pristine copy; it does NOT carry the site-local
                // theme/plugin PSR-4 (Themes\<Dir>\App\, Plugins\<Dir>\App\)
                // the installer persisted there. Re-persist them now, or every
                // extension admin/settings page 500s with a ReflectionException
                // after the update. syncAutoload() rewrites composer.local.json
                // and runs composer dump-autoload; non-fatal (returns false and
                // logs if Composer is unavailable).
                $log('Re-syncing extension autoload (theme/plugin PSR-4) after vendor swap...');
                if (! ComposerLocalHelper::syncAutoload()) {
                    $log('WARNING: extension autoload re-sync failed; run `composer dump-autoload` if theme/plugin pages error.');
                }
            }

            // A release ZIP MAY carry a manifest declaring theme
            // directories the operator should update alongside core. The
            // manifest is optional; a release without it is the normal
            // "core only, themes preserved" path and this branch is a
            // no-op. When present, the updater applies exactly the
            // declared themes/<slug>/ directories over the live tree
            // (never all of themes/ — operator-installed themes stay
            // put) and refreshes the DB `Theme.version` for each so
            // code, vendor autoload registration and DB metadata all
            // line up on the release's version. See ReleaseManifest for
            // the shape + validation rules the manifest goes through.
            $manifest = ReleaseManifest::readFromPayload($payloadRoot);
            if ($manifest !== null && $manifest->hasBundledThemes()) {
                $themeSnapshotPaths = $this->applyBundledThemes(
                    $payloadRoot,
                    $manifest->bundledThemes,
                    $log,
                );
            }

            $log('Running migrations...');
            // Scope migrate to the core's own migration path. Plugin and
            // theme ServiceProviders register their own database/migrations
            // directories on Laravel's default migrator via
            // loadMigrationsFrom() (see PluginLoaderTrait::loadPluginMigrations
            // and each plugin's own ServiceProvider) — but those rows are
            // tracked in `dls_plugin_migrations` and `dls_theme_migrations`
            // by PluginMigrationRepository / ThemeMigrationRepository,
            // not in `dls_migrations`. Without an explicit --path the
            // default migrator walks every registered path, sees the
            // plugin / theme migration files, fails to find a matching
            // row in `dls_migrations`, and tries to re-create tables that
            // the plugin / theme installer already created — surfacing as
            // SQLSTATE[42S01] "Base table or view already exists" that
            // aborts the whole core update partway through the apply
            // phase. The plugin and theme migrators own their own
            // application lifecycle (dls:plugin:install runs them via
            // PluginMigrator), so the core's migrate has no business
            // touching them anyway.
            Artisan::call('migrate', [
                '--path' => 'database/migrations',
                '--force' => true,
                '--no-interaction' => true,
            ]);
            $log('Migrations complete.');

            $log('Clearing caches...');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            // view:clear + view:cache via the shared helper. The rebuild
            // step avoids the dev-env "click a menu, land back on the
            // same page" symptom (Vite watches storage/framework/views/
            // during `npm run dev` and cancels any navigation whose
            // Blade view compiles on demand mid-flight).
            \App\Services\View\CompiledViewCacheRebuilder::rebuild();
            Artisan::call('cache:clear');
            $log('Caches cleared.');

            // Round 5 Finding D residual: opcache_reset() and
            // clearstatcache(true) called from CLI above only affect
            // the CLI SAPI. Ask PhpFpmReloader to refresh the FPM SAPI
            // (opcache SHM + realpath cache) BEFORE lifting maintenance
            // so the first post-maintenance request lands in workers
            // that already see the new source tree, not stale entries
            // from the pre-swap classmap. Best-effort — never aborts
            // the update.
            $log('Refreshing PHP-FPM cache...');
            $this->fpmReloader->reload();

            // The new code is in place and migrations passed; lift the
            // maintenance window before the (non-critical) bookkeeping
            // below so the site comes back as soon as it is safe.
            if ($maintenanceOn) {
                $log('Lifting maintenance mode...');
                Artisan::call('up');
                $maintenanceOn = false;
            }
            if ($vendorSwapped) {
                $this->vendorManager->discardPrevious();
                $log('Discarded vendor.old (update succeeded).');
            }

            // Mirror vendor.old cleanup: each bundled theme's pre-apply
            // copy was retained under storage/ so a failure between the
            // apply and this point could roll it back; the update has
            // completed successfully, so we can drop them now.
            foreach ($themeSnapshotPaths as $slug => $snapshotPath) {
                if (is_dir($snapshotPath)) {
                    File::deleteDirectory($snapshotPath);
                }
                $log("Discarded pre-apply snapshot of themes/{$slug} (update succeeded).");
            }
            $this->pruneEmptyThemeSnapshotParent($themeSnapshotPaths);
            $themeSnapshotPaths = [];

            $log('Recording version history...');
            $history = CoreVersionHistory::create([
                'old_version' => $current,
                'new_version' => $version,
                'installation_method' => CoreVersionHistory::METHOD_UPDATE,
                'applied_by_id' => $appliedById,
                'applied_at' => now(),
                // Foundation-only for future ZIP signature verification.
                // See the SHA-256 log line right after downloadCore()
                // above for the operator-facing surface.
                'downloaded_sha256' => $downloadedSha256,
            ]);

            // Clear the available_version on the singleton so the UI no
            // longer advertises the same upgrade.
            $coreState->forceFill([
                'available_version' => null,
                'available_version_published_at' => null,
                'release_url' => null,
                'last_notified_version' => $version,
                'update_failed_at' => null,
                'update_failure_reason' => null,
                'last_version_check' => now(),
            ])->save();

            // Persist the rollback point: link this source snapshot to the
            // versions, the DB restore record, and the pre-update migration
            // batch, so dls:core:rollback can reverse exactly this update
            // (source + optional vendor + only this update's schema) without
            // re-deriving anything by heuristic. Mirrors the plugin/theme
            // .meta.json convention (see TakesExtensionBackup).
            $this->snapshotter->writeMetadata($sourceSnapshotPath, [
                'from' => $current,
                'to' => $version,
                'history_id' => $history->id,
                'backup_record_id' => $backupRecordId,
                'max_batch' => $preMigrateBatch,
                'dependency_update' => $dependencyUpdate,
                'applied_at' => now()->toIso8601String(),
            ]);

            $log("Update complete: v{$current} -> v{$version}");

            // Best-effort cleanup of staging. The snapshot (and its rollback
            // metadata sidecar) is retained as the dls:core:rollback point.
            $this->cleanupStaging($stagingPath);

            // Bound the retained rollback history so snapshots (each a full
            // core source copy) don't grow without limit. The newest — this
            // update's rollback point — is always kept.
            $this->snapshotter->pruneSnapshots((int) config('core_update.snapshot_retention', 5));

            // Drop any stale `from:0.0.0` placeholder snapshots left over
            // from a pre-baseline update — they cannot be rolled back to
            // (the release does not exist) and would become the offered
            // rollback point once this update's own snapshot is consumed.
            // See Round 4 Finding B / CoreSourceSnapshot::pruneUnresolvableSnapshots.
            $this->snapshotter->pruneUnresolvableSnapshots();

            // Record completion for the System Updates page's one-shot
            // "update complete" flash. The web UI runs this update detached
            // and cannot flash directly; this is written before the finally
            // block clears the in-progress flag, so index() finds it as soon
            // as the flag is gone.
            SystemUpdateFlash::record([
                'status' => 'success',
                'kind' => 'core',
                'from' => $current,
                'to' => $version,
            ]);

            return [
                'from' => $current,
                'to' => $version,
                'snapshot' => $snapshotPath,
                'history_id' => $history->id,
                'backup_record_id' => $backupRecordId,
            ];
        } catch (\Throwable $e) {
            $log("Update failed: {$e->getMessage()} — rolling back source from snapshot...");

            try {
                $this->snapshotter->restore($snapshotPath);
                $log("Source rolled back from snapshot {$snapshotPath}");
            } catch (\Throwable $restoreError) {
                $log("ROLLBACK FAILED: {$restoreError->getMessage()}");
                $log("Manual recovery required. Snapshot retained at: {$snapshotPath}");
            }

            // If we swapped vendor/, the snapshot above only restored
            // source (vendor/ is excluded from snapshots by design). Move
            // the retained vendor.old back so the live tree matches the
            // rolled-back source. This needs no network and no Composer.
            if ($vendorSwapped) {
                try {
                    $this->vendorManager->restorePrevious();
                    $log('vendor/ rolled back from vendor.old.');
                } catch (\Throwable $vendorError) {
                    $log("VENDOR ROLLBACK FAILED: {$vendorError->getMessage()}");
                    $log('Manual recovery required: restore vendor/ from the previous release ZIP.');
                }
            }

            // Same pattern as the vendor rollback above, for any theme
            // directory we replaced under the release manifest's
            // instructions. Each entry in $themeSnapshotPaths is the
            // pre-apply copy of themes/<slug>; the DB `Theme.version`
            // row is refreshed to the pre-apply metadata so the admin
            // panel stops showing the aborted upgrade as installed.
            foreach ($themeSnapshotPaths as $slug => $snapshotPath) {
                try {
                    $livePath = base_path("themes/{$slug}");
                    if (is_dir($livePath)) {
                        File::deleteDirectory($livePath);
                    }
                    File::ensureDirectoryExists($livePath);
                    // Move the retained copy back; fall back to copy if
                    // the two happen to live on different filesystems.
                    if (! @rename($snapshotPath, $livePath)) {
                        File::copyDirectory($snapshotPath, $livePath);
                        File::deleteDirectory($snapshotPath);
                    }
                    $this->restoreThemeDbVersion($slug, $livePath, $log);
                    $log("Rolled back themes/{$slug} from pre-apply snapshot.");
                } catch (\Throwable $themeError) {
                    $log("THEME ROLLBACK FAILED for '{$slug}': {$themeError->getMessage()}");
                    $log("Manual recovery required: restore themes/{$slug} from {$snapshotPath}");
                }
            }
            $this->pruneEmptyThemeSnapshotParent($themeSnapshotPaths);

            // Lift maintenance mode last, once the tree is consistent again.
            if ($maintenanceOn) {
                try {
                    Artisan::call('up');
                    $maintenanceOn = false;
                    $log('Maintenance mode lifted after rollback.');
                } catch (\Throwable $upError) {
                    $log("Failed to lift maintenance mode: {$upError->getMessage()}");
                    $log('Run `php artisan up` manually to restore access.');
                }
            }

            if ($backupRecordId !== null) {
                $log("Database backup retained for manual restore (record id: {$backupRecordId}).");
                $log("To restore: php artisan dls:backup:restore {$backupRecordId}");
            }

            // Surface the failure on the singleton so the next render shows it.
            $coreState->forceFill([
                'update_failed_at' => now(),
                'update_failure_reason' => $this->truncateReason($e->getMessage()),
            ])->save();

            $this->cleanupStaging($stagingPath);

            throw $e;
        } finally {
            // Lift the in-progress flag that the admin UI's applyCore()
            // may have raised, so the next page render shows the
            // post-update state (success or failure) instead of the
            // placeholder. CLI invocations never set the flag, so this
            // is a no-op for them.
            @unlink(self::inProgressFlagPath());
        }
    }

    /**
     * Highest applied migration batch in the core `migrations` table, or 0
     * when the table is missing or empty. Recorded in the snapshot metadata
     * before the update's migrate step so dls:core:rollback can compute the
     * exact number of migrations to reverse — the same schema-only,
     * data-preserving approach the plugin/theme rollbacks use.
     */
    private function currentMigrationBatch(): int
    {
        try {
            return (int) (DB::table('migrations')->max('batch') ?? 0);
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Path to the on-disk flag the admin UI writes while a web-triggered
     * core update is in progress.
     *
     * Lives under storage/ — a protected path that the update process
     * never touches — so the flag survives the cache:clear that runs
     * mid-update. A Cache::put() flag would be wiped midway through and
     * unblock the admin UI back into the index view at the worst
     * possible moment, when resources/ is in the middle of being
     * replaced.
     */
    public static function inProgressFlagPath(): string
    {
        return storage_path('app/private/core-update/.in-progress');
    }

    /**
     * Read the on-disk running version from the committed VERSION file
     * at the repo root. Returns null when the file is absent (very early
     * installs, a stripped release ZIP) — callers should treat null as
     * "unknown; skip any check that depended on it" for backward
     * compatibility.
     *
     * Static so tests can stub the base path without instantiating the
     * whole updater graph; production callers get the real base_path().
     *
     * The file is a single line containing a semver-ish version
     * (e.g. `0.3.1`, `0.3.1-dryrun-6`) with optional trailing whitespace
     * / newline. Whitespace is stripped; an empty file is treated as
     * absent (returns null) rather than an empty version string that
     * would collate lower than every real version.
     */
    public static function readVersionFromDisk(?string $basePath = null): ?string
    {
        $basePath ??= base_path();
        $path = $basePath.DIRECTORY_SEPARATOR.'VERSION';

        if (! is_file($path)) {
            return null;
        }

        $raw = @file_get_contents($path);
        if ($raw === false) {
            return null;
        }

        $trimmed = trim($raw);

        return $trimmed !== '' ? $trimmed : null;
    }

    /**
     * Unzip the downloaded archive into the staging directory.
     */
    protected function extractToStaging(string $zipPath, string $stagingPath): void
    {
        File::ensureDirectoryExists($stagingPath);

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException("Failed to open downloaded core ZIP: {$zipPath}");
        }

        if (! $zip->extractTo($stagingPath)) {
            $zip->close();
            throw new RuntimeException("Failed to extract core ZIP to staging: {$stagingPath}");
        }
        $zip->close();
    }

    /**
     * GitHub zipball entries are nested one directory deep
     * (e.g. `Dixlase-dixlase-core-abcdef0/`). Locate the directory that
     * contains an `app/` subdir and treat it as the payload root.
     */
    protected function validateStagedPayload(string $stagingPath): string
    {
        $candidates = glob($stagingPath.'/*', GLOB_ONLYDIR) ?: [];
        $payloadRoot = $stagingPath;

        if (count($candidates) === 1 && is_dir($candidates[0].'/app')) {
            $payloadRoot = $candidates[0];
        }

        if (! is_dir($payloadRoot.'/app') || ! is_dir($payloadRoot.'/config')) {
            throw new RuntimeException(
                'Extracted payload does not look like a Dixlase Core release '
                ."(missing app/ or config/): {$payloadRoot}"
            );
        }

        return $payloadRoot;
    }

    /**
     * Replace each whitelisted source directory / file in the live tree
     * with the staged copy. Anything not whitelisted is left untouched
     * (.env, storage, vendor, plugins/themes/custom, etc.).
     *
     * Directory swaps route through CoreSourceSnapshot::replaceLiveDirectory()
     * so every symlink in the live tree (public/storage,
     * public/assets/themes/<slug>) survives the swap — the source ZIP
     * never contains them and a naive delete+copy would silently strip
     * them, breaking storage URLs and theme assets until the operator
     * manually re-ran storage:link and dls:theme:symlink.
     */
    protected function applyToLiveTree(string $payloadRoot): void
    {
        $base = base_path();

        foreach (CoreSourceSnapshot::SOURCE_DIRECTORIES as $relative) {
            $stagedDir = $payloadRoot.'/'.$relative;
            if (! is_dir($stagedDir)) {
                continue;
            }
            $liveDir = $base.'/'.$relative;
            File::ensureDirectoryExists(dirname($liveDir));
            $this->snapshotter->replaceLiveDirectory($stagedDir, $liveDir);
        }

        foreach (CoreSourceSnapshot::SOURCE_FILES as $relative) {
            $stagedFile = $payloadRoot.'/'.$relative;
            if (! is_file($stagedFile)) {
                continue;
            }
            $liveFile = $base.'/'.$relative;
            File::ensureDirectoryExists(dirname($liveFile));
            File::copy($stagedFile, $liveFile);
        }

        // ZipArchive extraction and File::copy drop the executable bit, so
        // ensure the applied `artisan` stays runnable after an update.
        $liveArtisan = $base.'/artisan';
        if (is_file($liveArtisan)) {
            @chmod($liveArtisan, 0755);
        }
    }

    /**
     * Remove staging dir, ignoring errors.
     */
    protected function cleanupStaging(string $stagingPath): void
    {
        if (is_dir($stagingPath)) {
            File::deleteDirectory($stagingPath);
        }
    }

    /**
     * Cap stored failure reasons so a stack-trace string does not bloat the
     * row. Display surfaces will truncate further as needed.
     */
    protected function truncateReason(string $message): string
    {
        $message = trim($message);
        if (mb_strlen($message) <= 1000) {
            return $message;
        }

        return mb_substr($message, 0, 997).'...';
    }

    /**
     * Apply the theme directories declared in a release manifest over
     * the live tree. Each entry names exactly `themes/<slug>` — this
     * has already been validated by ReleaseManifest — so the updater
     * never touches operator-installed themes, plugins/ or custom/.
     *
     * For each declared theme the existing live directory is moved
     * aside to a retained snapshot under storage/, then the staged
     * copy is copied into place. The caller keeps the returned
     * slug => snapshot-path map so the catch block can restore the
     * old copies on rollback, and the success branch can discard them
     * once the update has committed. The DB `Theme.version` row is
     * refreshed for each applied theme so the admin panel's theme
     * list reflects the release's version — code, vendor autoload
     * registration and DB metadata all land on the same version in
     * the same operation.
     *
     * @param  list<array{slug: string, version: string, path: string}>  $themes
     * @param  Closure(string): void  $log
     * @return array<string, string> slug => absolute path of retained pre-apply copy
     */
    protected function applyBundledThemes(string $payloadRoot, array $themes, Closure $log): array
    {
        $snapshots = [];
        $snapshotRoot = storage_path('app/private/core-update/theme-snapshots/'.now()->format('YmdHis_').uniqid());
        File::ensureDirectoryExists($snapshotRoot);

        foreach ($themes as $entry) {
            $slug = $entry['slug'];
            $stagedPath = $payloadRoot.'/'.$entry['path'];
            $livePath = base_path($entry['path']);

            if (! is_dir($stagedPath)) {
                throw new RuntimeException("Release declared bundled theme '{$slug}' but the staged payload contains no {$stagedPath}.");
            }

            $log("Applying bundled theme '{$slug}' (target v{$entry['version']})...");

            // Snapshot the live copy so the catch block can restore it
            // if any later step (this loop or migrations below) fails.
            // A theme that isn't installed yet has nothing to snapshot,
            // and the rollback path handles a missing entry gracefully.
            if (is_dir($livePath)) {
                $snapshotPath = $snapshotRoot.'/'.$slug;
                if (! @rename($livePath, $snapshotPath)) {
                    File::ensureDirectoryExists($snapshotPath);
                    File::copyDirectory($livePath, $snapshotPath);
                    File::deleteDirectory($livePath);
                }
                $snapshots[$slug] = $snapshotPath;
            }

            File::ensureDirectoryExists($livePath);
            File::copyDirectory($stagedPath, $livePath);

            // Refresh the DB row so the admin panel and the standalone
            // theme-update flow see the release's version as installed.
            // The affected-rows count may be zero when the operator has
            // not activated the theme; that's fine — the code is still
            // on disk, and the DB row is created on first activation.
            Theme::where('directory', $slug)->update(['version' => $entry['version']]);

            $log("Applied themes/{$slug} at v{$entry['version']}.");
        }

        return $snapshots;
    }

    /**
     * Reset the DB `Theme.version` back to whatever the just-restored
     * theme code itself declares in its `theme.json`. Called from the
     * rollback path so the admin panel doesn't keep advertising the
     * aborted upgrade as installed. Silently no-ops when the theme
     * either isn't installed (no matching DB row) or its theme.json
     * is unreadable — the code has already been rolled back, so this
     * is best-effort metadata cleanup.
     */
    private function restoreThemeDbVersion(string $slug, string $livePath, Closure $log): void
    {
        $themeJson = $livePath.'/theme.json';
        if (! is_file($themeJson)) {
            return;
        }

        $decoded = json_decode((string) @file_get_contents($themeJson), true);
        if (! is_array($decoded) || ! isset($decoded['version']) || ! is_string($decoded['version'])) {
            return;
        }

        Theme::where('directory', $slug)->update(['version' => $decoded['version']]);
        $log("Restored DB metadata for themes/{$slug} to v{$decoded['version']} (from theme.json).");
    }

    /**
     * Remove the empty per-update-run wrapper directory that
     * applyBundledThemes() created under storage/ to group its
     * per-slug snapshots. Both the success discard path and the
     * rollback restore path leave the individual <slug>/ children
     * gone; this drops the now-empty parent so an update never
     * leaves behind an accumulating trail of orphan timestamp
     * directories under storage/app/private/core-update/theme-snapshots/.
     *
     * Best-effort — if another process happened to write into the
     * wrapper mid-flight, or if rmdir just fails, we do nothing.
     * The remainder is harmless.
     *
     * @param  array<string, string>  $themeSnapshotPaths  slug => full snapshot path (as returned by applyBundledThemes)
     */
    private function pruneEmptyThemeSnapshotParent(array $themeSnapshotPaths): void
    {
        if ($themeSnapshotPaths === []) {
            return;
        }

        // Every entry in the map lives under the same wrapper — the
        // one applyBundledThemes() created for this run — so any
        // path's dirname is the wrapper.
        $wrapper = dirname((string) reset($themeSnapshotPaths));
        if (! is_dir($wrapper)) {
            return;
        }

        // FilesystemIterator skips . and .., so iterator_count == 0
        // means "no children of any kind." Ignore a hostile write
        // that races in — we only remove when unambiguously empty.
        try {
            $iterator = new \FilesystemIterator($wrapper);
        } catch (\UnexpectedValueException) {
            return;
        }
        if (iterator_count($iterator) === 0) {
            @rmdir($wrapper);
        }
    }
}
