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
use App\Models\AuditLog;
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
     * The subprocess launcher, resolved before the tree is touched.
     *
     * Held for the length of update() so the post-swap steps never have to
     * read the class - or anything run() reaches - off a tree that has since
     * been replaced. See ArtisanProcess::boots().
     */
    private ?ArtisanProcess $artisan = null;

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

        // Preflight (backlog core-update-rollback-hardening #1): refuse to
        // start an update that cannot finish — missing extension, unwritable
        // tree, not enough disk — while nothing has been changed yet. This
        // runs before the snapshot, i.e. outside the try below, so the
        // failure is recorded for the admin panel and the web launcher's
        // in-progress flag is cleared here rather than by the finally.
        // Resolve the subprocess launcher and exercise it once before
        // anything on disk changes: this loads the class, Symfony's Process
        // and everything else run() needs into memory while the files still
        // belong to the version this process booted from. The rollback path
        // hit the reverse of this and died on a class its restored source no
        // longer had.
        $this->artisan = app(ArtisanProcess::class);
        $launcherBoots = $this->artisan->boots();
        if (! $launcherBoots) {
            $log('WARNING: a new PHP process could not boot the application before the update started; the post-apply boot check will be skipped.');
        }

        $log('Running preflight checks...');
        $preflight = app(CorePreflightChecker::class)->run();
        foreach ($preflight->lines() as $line) {
            $log($line);
        }
        if ($preflight->failed()) {
            $reason = 'Preflight failed, nothing was changed: '.$preflight->failureSummary();
            $coreState->forceFill([
                'update_failed_at' => now(),
                'update_failure_reason' => $this->truncateReason($reason),
            ])->save();
            @unlink(self::inProgressFlagPath());
            $this->auditPreflightRefusal($current, $version, $appliedById, $reason);

            throw new RuntimeException($reason);
        }

        // Whether the tree still matches its integrity baseline, taken before
        // anything changes: only then is the baseline regenerated after a
        // successful update (see CoreIntegrityBaselineRefresher).
        $integrityMatchedBefore = $this->integrityMatchesBaseline($log);

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

        // Set just before this run's migrate step, so the catch block knows
        // whether migrations this run applied need reversing.
        $migrationsStarted = false;

        // Slugs of bundled themes this update bootstrapped (copied into
        // place because they were not installed before). An installed
        // theme is never touched by a core update — see
        // applyBundledThemes() — so on rollback the only theme work is
        // removing these fresh copies again, which restores the exact
        // pre-update tree. Empty means "no theme was touched."
        $bootstrappedThemeSlugs = [];

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

            // Preflight, stage 2: the archive's real unpacked size is only
            // known now. Inside the try, so the catch restores and records.
            $archiveCheck = app(CorePreflightChecker::class)->checkDownloadedArchive($zipPath, $stagingPath);
            foreach ($archiveCheck->lines() as $line) {
                $log($line);
            }
            if ($archiveCheck->failed()) {
                throw new RuntimeException('Preflight failed before extraction: '.$archiveCheck->failureSummary());
            }

            $log('Extracting to staging directory...');
            $this->extractToStaging($zipPath, $stagingPath);
            $log("Extracted to {$stagingPath}");

            $log('Validating extracted payload...');
            $payloadRoot = $this->validateStagedPayload($stagingPath);
            $log("Validated payload at {$payloadRoot}");

            // Preflight, stage 3: the new release's own PHP requirement is
            // only readable from its dixlase.json. Checked before maintenance
            // mode and before any live file is touched.
            $releaseCheck = app(CorePreflightChecker::class)->checkReleaseRequirements($payloadRoot);
            foreach ($releaseCheck->lines() as $line) {
                $log($line);
            }
            if ($releaseCheck->failed()) {
                throw new RuntimeException('Preflight failed, the new release cannot run here: '.$releaseCheck->failureSummary());
            }

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
            // Record this process as the window's owner before `down` so
            // that, if we are killed mid-swap, dls:core:heal-maintenance
            // (scheduler / any artisan boot) can lift the window; the
            // secret gives an operator a bypass URL in the meantime. See
            // CoreMaintenanceGuard for why the record is written first.
            $maintenanceSecret = app(CoreMaintenanceGuard::class)->claim(CoreMaintenanceGuard::OPERATION_UPDATE, $version);
            // --refresh makes the 503 page reload every 15s so the
            // operator's browser returns to the site automatically once
            // the swap finishes and maintenance is lifted.
            Artisan::call('down', ['--retry' => 60, '--refresh' => 15, '--secret' => $maintenanceSecret]);
            $maintenanceOn = true;
            $log('Operator bypass URL while in maintenance: '.CoreMaintenanceGuard::bypassUrl($maintenanceSecret));

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
                // Hold the operator at the 503 page too while vendor/ and the
                // extension autoload disagree (see suspendOperatorBypass()).
                $bypass = app(CoreMaintenanceGuard::class)->suspendOperatorBypass();

                try {
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

                    // The swap also leaves bootstrap/cache/packages.php describing
                    // the previous vendor/; a provider that is no longer there
                    // 500s the whole site on the next boot. See
                    // ComposerLocalHelper::rebuildPackageManifest().
                    $log('Rebuilding the package-discovery manifest after vendor swap...');
                    if (! ComposerLocalHelper::rebuildPackageManifest()) {
                        $log('WARNING: package manifest rebuild failed; if the site returns 500, delete bootstrap/cache/packages.php and bootstrap/cache/services.php, then run `php artisan package:discover`.');
                    }
                } finally {
                    app(CoreMaintenanceGuard::class)->restoreOperatorBypass($bypass);
                }
            }

            // A release ZIP MAY carry a manifest declaring bundled theme
            // directories. The manifest is optional; a release without
            // it is the normal "core only, themes preserved" path and
            // this branch is a no-op. When present, a bundled theme is
            // BOOTSTRAP-ONLY: the updater copies exactly the declared
            // themes/<slug>/ into place when — and only when — that
            // theme is not yet installed. A theme that already exists
            // on disk is left completely untouched (no files, no DB
            // `Theme.version`); core and theme versions are
            // independent and the operator moves a theme through
            // dls:theme:update / dls:theme:rollback. See ReleaseManifest
            // for the shape + validation rules the manifest goes through.
            $manifest = ReleaseManifest::readFromPayload($payloadRoot);
            if ($manifest !== null && $manifest->hasBundledThemes()) {
                $bootstrappedThemeSlugs = $this->applyBundledThemes(
                    $payloadRoot,
                    $manifest->bundledThemes,
                    $log,
                );
            }

            // public/assets/themes/<Theme> and public/assets/plugins/<Plugin>
            // are symlinks into the extension trees; the source swap above
            // replaced public/ wholesale and a release ZIP can even carry the
            // dereferenced assets as a real directory at those paths. Re-point
            // the links at the extension trees, after the bundled-theme step so
            // a newly bundled theme is covered too. Deliberately outside the
            // $dependencyUpdate branch: the source swap happens on every
            // update. Non-fatal — an unlinked asset tree renders the front page
            // unstyled but does not stop the update.
            $log('Relinking public asset symlinks (storage, themes, plugins)...');
            try {
                $relinked = (new PublicAssetRelinker())->relink();
                $log("Relinked public assets (themes: {$relinked['themes']}, plugins: {$relinked['plugins']}).");
            } catch (\Throwable $relinkError) {
                $log('WARNING: public asset relink failed ('.$relinkError->getMessage().'); if the front page renders unstyled, run `php artisan dls:theme:symlink create --all` and `php artisan dls:plugin:symlink create --all`.');
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
            //
            // After a vendor swap this and the steps below run in a new
            // process (see runArtisan()).
            $migrationsStarted = true;
            $this->runArtisan('migrate', [
                '--path' => 'database/migrations',
                '--force' => true,
                '--no-interaction' => true,
            ], $vendorSwapped);
            $log('Migrations complete.');

            // Run the core UpdateSeeder if the class exists — the
            // opt-in convention for update-time seed logic. Kept
            // separate from DatabaseSeeder (which the web installer
            // runs on fresh install) so an install-time seeder that
            // inserts demo data does not accidentally re-run on
            // every core update and duplicate rows. Any operation
            // in UpdateSeeder must be safe to re-run on every core
            // upgrade — typically insertOrIgnore / updateOrCreate
            // against settings tables to backfill newly-declared
            // defaults. Silent no-op when the class is absent, which
            // is the normal state on main: core ships no UpdateSeeder
            // of its own. The sandbox verification copy lives on the
            // `test/core-update-sandbox-fixtures` branch, because a
            // seeder that writes a marker row would otherwise run on
            // every user's core update.
            if (class_exists(\Database\Seeders\UpdateSeeder::class)) {
                $log('Running core UpdateSeeder...');
                $this->runArtisan('db:seed', [
                    '--class' => \Database\Seeders\UpdateSeeder::class,
                    '--force' => true,
                    '--no-interaction' => true,
                ], $vendorSwapped);
                $log('Core UpdateSeeder complete.');
            }

            // The source apply replaced the plugin Tailwind sources file with
            // the release's empty placeholder. Rebuild it from the enabled
            // plugins, or the next theme build drops every class only a
            // plugin's content uses. Non-fatal.
            $log('Regenerating plugin Tailwind sources...');
            try {
                $this->runArtisan('dls:tailwind:regenerate-plugin-sources', [], $vendorSwapped);
            } catch (\Throwable $sourcesError) {
                $log('WARNING: plugin Tailwind sources were not regenerated ('.$sourcesError->getMessage().'); run `php artisan dls:tailwind:regenerate-plugin-sources`.');
            }

            $log('Clearing caches...');
            $this->runArtisan('config:clear', [], $vendorSwapped);
            $this->runArtisan('route:clear', [], $vendorSwapped);
            // view:clear + view:cache via the shared helper. The rebuild
            // step avoids the dev-env "click a menu, land back on the
            // same page" symptom (Vite watches storage/framework/views/
            // during `npm run dev` and cancels any navigation whose
            // Blade view compiles on demand mid-flight). After a vendor
            // swap it runs in a new process: compiling Blade in this one
            // goes through the Blade extensions of the providers this
            // process booted with, and a package the release dropped
            // (livewire/livewire) then fails on a file that is gone.
            if ($vendorSwapped) {
                $this->runArtisan('view:clear', [], true);
                $this->runArtisan('view:cache', [], true);
            } else {
                \App\Services\View\CompiledViewCacheRebuilder::rebuild();
            }
            $this->runArtisan('cache:clear', [], $vendorSwapped);
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

            // Ask a new process to boot the updated tree before the site is
            // let back in. Each step above can succeed and still leave the
            // application unbootable - a bootstrap/cache manifest describing
            // the previous vendor/ is enough - and the first thing to notice
            // used to be a visitor. Only trusted when the same check passed
            // before the update started, so a launcher that never worked here
            // cannot turn a good update into a rollback.
            if ($launcherBoots && ! $this->artisan->boots()) {
                throw new RuntimeException('the updated core does not boot in a new process');
            }

            // The new code is in place and migrations passed; lift the
            // maintenance window before the (non-critical) bookkeeping
            // below so the site comes back as soon as it is safe.
            if ($maintenanceOn) {
                $log('Lifting maintenance mode...');
                Artisan::call('up');
                $maintenanceOn = false;
                app(CoreMaintenanceGuard::class)->release();
            }
            if ($vendorSwapped) {
                $this->vendorManager->discardPrevious();
                $log('Discarded vendor.old (update succeeded).');
            }

            // The update has committed: any theme bootstrapped above is
            // now a regular installed theme and must survive a failure
            // in the bookkeeping below, so stop tracking it for rollback.
            $bootstrappedThemeSlugs = [];

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

            CoreUpdateAudit::record(AuditLog::ACTION_CORE_UPDATED, true, $current, $version, $appliedById, [
                'history_id' => $history->id,
                'backup_record_id' => $backupRecordId,
                'dependency_update' => $dependencyUpdate,
                'downloaded_sha256' => $downloadedSha256,
            ]);

            // The release's files are now the genuine ones: without this the
            // daily integrity scan reports every file the update changed.
            app(CoreIntegrityBaselineRefresher::class)->refreshAfter($integrityMatchedBefore, $appliedById, $log);

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

            // Reverse the migrations this run applied BEFORE the source is
            // restored: their down() methods live in the new release's files,
            // which the restore removes. Without this the database kept the
            // new schema, the next update recorded that schema as its
            // starting point, and dls:core:rollback then had nothing to
            // reverse — old code on a new schema.
            if ($migrationsStarted) {
                $this->rollBackMigrationsSince($preMigrateBatch, $vendorSwapped, $backupRecordId, $log);
            }

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

                // bootstrap/cache/packages.php and services.php still
                // describe the vendor/ this update installed, and a source
                // restore keeps the live copies on purpose. Left alone they
                // list providers the restored vendor/ does not have, and the
                // site answers 500 while this path reports a clean rollback.
                $this->refreshPackageManifest($log);
            }

            // Same pattern as the vendor rollback above, for any bundled
            // theme this update bootstrapped. Those directories did not
            // exist before the update (an installed theme is never
            // touched), so removing them restores the pre-update tree
            // exactly. Nothing to do for the DB: a bootstrapped theme
            // had no DB row to move — see applyBundledThemes().
            foreach ($bootstrappedThemeSlugs as $slug) {
                try {
                    $livePath = base_path("themes/{$slug}");
                    if (is_dir($livePath)) {
                        File::deleteDirectory($livePath);
                    }
                    $log("Removed bootstrapped themes/{$slug} (it was not installed before this update).");
                } catch (\Throwable $themeError) {
                    $log("THEME ROLLBACK FAILED for '{$slug}': {$themeError->getMessage()}");
                    $log("Manual recovery required: remove themes/{$slug} (it was not installed before this update).");
                }
            }

            // Say whether the recovery actually produced a site that comes
            // up. Restoring the files is not the same as restoring service,
            // and an operator reading "rolled back" has no other signal.
            if ($launcherBoots && ! ($this->artisan?->boots() ?? true)) {
                $log("WARNING: the restored core does not boot in a new process. Manual recovery required from the snapshot at: {$snapshotPath}");
            }

            // Lift maintenance mode last, once the tree is consistent again.
            if ($maintenanceOn) {
                try {
                    Artisan::call('up');
                    $maintenanceOn = false;
                    // Only drop the owner record once `up` succeeded: if it
                    // threw, the record is what lets the scheduled self-heal
                    // lift the window after this process exits.
                    app(CoreMaintenanceGuard::class)->release();
                    $log('Maintenance mode lifted after rollback.');
                } catch (\Throwable $upError) {
                    $log("Failed to lift maintenance mode: {$upError->getMessage()}");
                    $log('Run `php artisan up` manually to restore access (or wait: dls:core:heal-maintenance lifts it within a minute).');
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
            CoreUpdateAudit::record(AuditLog::ACTION_CORE_UPDATE_FAILED, false, $current, $version, $appliedById, [
                'error' => $this->truncateReason($e->getMessage()),
                'backup_record_id' => $backupRecordId,
                'migrations_started' => $migrationsStarted,
                'dependency_update' => $dependencyUpdate,
            ]);

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
     * treeMatchesBaseline(), logged. A failure to hash the tree counts as
     * "did not match", so the baseline is left alone rather than rewritten.
     */
    private function integrityMatchesBaseline(Closure $log): ?bool
    {
        try {
            $matched = app(CoreIntegrityBaselineRefresher::class)->treeMatchesBaseline();
        } catch (\Throwable $e) {
            $log('WARNING: could not compare core files with the integrity baseline: '.$e->getMessage());

            return false;
        }

        $log(match ($matched) {
            true => 'Core files match the integrity baseline.',
            false => 'WARNING: core files do not match the integrity baseline; it will not be regenerated after the update.',
            null => 'No integrity baseline yet; one will be written after the update.',
        });

        return $matched;
    }

    /**
     * Audit a refused update. Nothing changed, but who tried to update the
     * core, and why it was refused, belongs in the audit log too.
     */
    private function auditPreflightRefusal(string $current, string $version, ?int $appliedById, string $reason): void
    {
        CoreUpdateAudit::record(AuditLog::ACTION_CORE_UPDATE_FAILED, false, $current, $version, $appliedById, [
            'stage' => 'preflight',
            'error' => $this->truncateReason($reason),
        ]);
    }

    /**
     * Run an Artisan command, in a new process when vendor/ was swapped.
     *
     * This process booted from the previous vendor/: its class loader and
     * the service providers it registered still describe packages that may
     * be gone. A new process boots from the new vendor/ and the package
     * manifest rebuilt after the swap (see ArtisanProcess). Without a
     * vendor swap the command runs in-process as before.
     *
     * @param  array<string, bool|int|string>  $options
     */
    private function runArtisan(string $command, array $options, bool $freshProcess): void
    {
        if ($freshProcess) {
            ($this->artisan ??= app(ArtisanProcess::class))->run($command, $options);

            return;
        }

        Artisan::call($command, $options);
    }

    /**
     * Rebuild the package-discovery manifests against the tree on disk now.
     *
     * bootstrap/cache/packages.php and services.php are generated from
     * whichever vendor/ was installed when they were last written, and a
     * source restore keeps the live copies on purpose (bootstrap/cache is a
     * protected path). A failed update that puts the previous vendor/ back is
     * therefore left with manifests listing packages that vendor/ does not
     * have, and every request fatals on a missing provider.
     *
     * The files are deleted first, so even a failing package:discover leaves
     * a site the framework can rebuild on the next boot rather than a
     * poisoned one. The rebuild runs in a new process, which boots from the
     * restored vendor/ rather than the one this process loaded.
     *
     * @param  Closure(string): void  $log
     *
     * ComposerLocalHelper::rebuildPackageManifest() warns that package:discover
     * must not run in a new process, because that process would boot from the
     * stale manifest and die on a provider that is gone before the command
     * ran. Deleting the files first is what makes a new process safe here:
     * with no manifest on disk the framework builds one from the restored
     * vendor/composer/installed.json as it boots.
     */
    private function refreshPackageManifest(Closure $log): void
    {
        foreach (['packages.php', 'services.php'] as $file) {
            $path = base_path('bootstrap/cache/'.$file);
            if (is_file($path)) {
                @unlink($path);
            }
        }

        try {
            ($this->artisan ??= app(ArtisanProcess::class))->run('package:discover');
            $log('Rebuilt the package-discovery manifest for the restored tree.');
        } catch (\Throwable $e) {
            $log("Could not rebuild the package manifest ({$e->getMessage()}); it will be regenerated on the next request.");
        }
    }

    /**
     * Reverse the core migrations applied after $preMigrateBatch.
     *
     * Best-effort: a failure is logged with the way back (restore the
     * pre-update database backup) and never masks the original error.
     */
    private function rollBackMigrationsSince(int $preMigrateBatch, bool $freshProcess, ?int $backupRecordId, Closure $log): void
    {
        try {
            $applied = DB::table('migrations')->where('batch', '>', $preMigrateBatch)->count();
            if ($applied === 0) {
                return;
            }

            $log("Reversing {$applied} migration(s) applied by this update...");
            $this->runArtisan('migrate:rollback', [
                '--path' => 'database/migrations',
                '--step' => $applied,
                '--force' => true,
                '--no-interaction' => true,
            ], $freshProcess);
            $log('Migrations reversed.');
        } catch (\Throwable $rollbackError) {
            $log("MIGRATION ROLLBACK FAILED: {$rollbackError->getMessage()}");
            $log($backupRecordId !== null
                ? "The database may be on the new schema. Restore it with: php artisan dls:backup:restore {$backupRecordId}"
                : 'The database may be on the new schema. Restore it from a backup taken before the update.');
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
     * Bootstrap the theme directories declared in a release manifest.
     *
     * Contract (bootstrap-only): a declared theme is copied from the
     * staged payload into `themes/<slug>` ONLY when that directory does
     * not exist yet — i.e. a fresh install-from-release. A theme that is
     * already installed is never modified by a core update: no file is
     * overwritten and the DB `Theme.version` row is not changed, even
     * when the bundled copy is newer. Core and theme versions are
     * independent; the operator moves an installed theme through
     * dls:theme:update / dls:theme:rollback on their own schedule. This
     * keeps a `[bundle-theme]` release from overwriting or downgrading a
     * theme (and its customizations) the operator advanced separately.
     *
     * Each entry names exactly `themes/<slug>` — already validated by
     * ReleaseManifest — so the updater never touches operator-installed
     * themes, plugins/ or custom/. No version comparison is made on
     * purpose: "newer bundled theme" is the theme's own update path, not
     * a forced overwrite.
     *
     * The returned slugs are the themes this call actually copied into
     * place. The caller keeps them so the catch block can remove those
     * directories again on rollback (they did not exist before).
     *
     * @param  list<array{slug: string, version: string, path: string}>  $themes
     * @param  Closure(string): void  $log
     * @return list<string> slugs of the themes bootstrapped by this call
     */
    protected function applyBundledThemes(string $payloadRoot, array $themes, Closure $log): array
    {
        $bootstrapped = [];

        foreach ($themes as $entry) {
            $slug = $entry['slug'];
            $stagedPath = $payloadRoot.'/'.$entry['path'];
            $livePath = base_path($entry['path']);

            if (! is_dir($stagedPath)) {
                throw new RuntimeException("Release declared bundled theme '{$slug}' but the staged payload contains no {$stagedPath}.");
            }

            // Bootstrap-only: an installed theme is left exactly as it
            // is. The operator updates it independently via
            // dls:theme:update.
            if (is_dir($livePath)) {
                $log("Theme '{$slug}' is already installed; leaving it untouched (core updates never change an installed theme — use dls:theme:update).");

                continue;
            }

            $log("Bootstrapping bundled theme '{$slug}' (v{$entry['version']}) — not previously installed...");
            File::ensureDirectoryExists($livePath);
            File::copyDirectory($stagedPath, $livePath);
            $bootstrapped[] = $slug;

            // Line the DB row up with the code just landed. The
            // affected-rows count is normally zero here — a theme with
            // no directory has not been activated, and the row is
            // created on first activation — but a stale row from a
            // previously removed directory would otherwise advertise
            // the wrong version.
            Theme::where('directory', $slug)->update(['version' => $entry['version']]);

            $log("Bootstrapped themes/{$slug} at v{$entry['version']}.");
        }

        return $bootstrapped;
    }
}
