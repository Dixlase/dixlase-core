<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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
use App\Models\CoreRelease;
use App\Models\CoreVersionHistory;
use App\Services\Extension\ExtensionSourceManager;
use Closure;
use Illuminate\Support\Facades\Artisan;
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
    ) {}

    /**
     * @param  ?Closure(string): void  $log  Optional sink for progress lines
     * @return array{from: ?string, to: string, snapshot: string, history_id: int, backup_record_id: ?int}
     */
    public function update(?string $version = null, ?int $appliedById = null, ?Closure $log = null): array
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

        $log('Capturing source snapshot...');
        $snapshotPath = $this->snapshotter->capture();
        $log("Snapshot captured at {$snapshotPath}");

        $stagingPath = storage_path('app/private/core-update/staging/'.now()->format('YmdHis_').uniqid());
        $backupRecordId = null;

        // Tracks whether this run swapped vendor/ (a "dependency update")
        // and whether the site was put into maintenance mode, so the catch
        // block can undo both precisely. A dependency update is detected by
        // comparing the staged composer.lock against the live one.
        $dependencyUpdate = false;
        $vendorSwapped = false;
        $maintenanceOn = false;

        try {
            $log("Downloading core v{$version}...");
            $zipPath = $this->sourceManager->downloadCore($version);
            $log("Downloaded to {$zipPath}");

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
            $dependencyUpdate = $this->dependencyLockChanged($payloadRoot);
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

            // Capture a database backup before mutating the live tree. If
            // post-extraction migrations fail or the new code fails to boot,
            // the operator can restore via dls:backup:restore and a manual
            // file rollback from the snapshot.
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

            // For a dependency update the live tree is briefly inconsistent
            // (new source on disk while vendor/ is mid-swap), and any HTTP
            // request landing in that window would fatal because the
            // autoloader can't find classes. public/index.php checks for
            // storage/framework/maintenance.php *before* booting the
            // framework, so `down` keeps serving a static 503 even while
            // vendor/ is incomplete. Non-dependency updates keep the old
            // behaviour (no downtime) — the running workers tolerate a
            // source-only swap because vendor/ stays intact.
            if ($dependencyUpdate) {
                $log('Entering maintenance mode...');
                // --refresh makes the 503 page reload every 15s so the
                // operator's browser returns to the site automatically once
                // the swap finishes and maintenance is lifted.
                Artisan::call('down', ['--retry' => 60, '--refresh' => 15]);
                $maintenanceOn = true;
            }

            $log('Applying source over live tree...');
            $this->applyToLiveTree($payloadRoot);
            $log('Applied source.');

            if ($dependencyUpdate) {
                $log('Swapping vendor/ (prebuilt dependencies from the release)...');
                $this->applyVendor($payloadRoot);
                $vendorSwapped = true;
                $log('vendor/ swapped (previous vendor/ retained at vendor.old for rollback).');
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
            Artisan::call('view:clear');
            Artisan::call('cache:clear');
            $log('Caches cleared.');

            // The new code is in place and migrations passed; lift the
            // maintenance window before the (non-critical) bookkeeping
            // below so the site comes back as soon as it is safe.
            if ($maintenanceOn) {
                $log('Lifting maintenance mode...');
                Artisan::call('up');
                $maintenanceOn = false;
            }
            if ($vendorSwapped) {
                $this->cleanupOldVendor();
                $log('Discarded vendor.old (update succeeded).');
            }

            $log('Recording version history...');
            $history = CoreVersionHistory::create([
                'old_version' => $current,
                'new_version' => $version,
                'installation_method' => CoreVersionHistory::METHOD_UPDATE,
                'applied_by_id' => $appliedById,
                'applied_at' => now(),
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

            $log("Update complete: v{$current} -> v{$version}");

            // Best-effort cleanup of staging + snapshot. Snapshot is kept
            // for one cycle in case a later issue surfaces, and tidied on
            // the next successful upgrade.
            $this->cleanupStaging($stagingPath);

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
                    $this->restoreOldVendor();
                    $log('vendor/ rolled back from vendor.old.');
                } catch (\Throwable $vendorError) {
                    $log("VENDOR ROLLBACK FAILED: {$vendorError->getMessage()}");
                    $log('Manual recovery required: restore vendor/ from the previous release ZIP.');
                }
            }

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
    }

    /**
     * Decide whether the staged release changes PHP dependencies, by
     * comparing its composer.lock against the installed one. A missing
     * staged lock means the release does not pin dependencies, so there is
     * nothing to swap (treated as "unchanged"). A missing live lock with a
     * present staged lock counts as a change (first time the lock appears).
     */
    protected function dependencyLockChanged(string $payloadRoot, ?string $base = null): bool
    {
        $stagedLock = $payloadRoot.'/composer.lock';
        if (! is_file($stagedLock)) {
            return false;
        }

        $liveLock = ($base ?? base_path()).'/composer.lock';
        if (! is_file($liveLock)) {
            return true;
        }

        return ! hash_equals(
            (string) hash_file('sha256', $liveLock),
            (string) hash_file('sha256', $stagedLock),
        );
    }

    /**
     * Swap the live vendor/ for the staged one, retaining the previous
     * vendor/ at vendor.old so a failed update can be rolled back locally
     * (no Composer, no network). Called only inside the maintenance window
     * of a dependency update.
     *
     * The staged tree shares the live filesystem (both under the project
     * root), so the move is an atomic rename; if a deployment puts storage
     * on a different mount the rename fails and we fall back to a copy.
     * vendor.old is discarded on success and restored on failure.
     */
    protected function applyVendor(string $payloadRoot, ?string $base = null): void
    {
        $base ??= base_path();
        $stagedVendor = $payloadRoot.'/vendor';
        $liveVendor = $base.'/vendor';
        $oldVendor = $base.'/vendor.old';

        if (! is_dir($stagedVendor)) {
            throw new RuntimeException("Staged vendor/ not found at {$stagedVendor}.");
        }

        // Clear any leftover vendor.old from a prior interrupted run.
        if (is_dir($oldVendor)) {
            File::deleteDirectory($oldVendor);
        }

        // Move the current vendor/ aside (atomic within the project root).
        if (is_dir($liveVendor)) {
            if (! @rename($liveVendor, $oldVendor)) {
                throw new RuntimeException('Failed to move current vendor/ aside before swap.');
            }
        }

        // Promote the staged vendor/ into place. Prefer an atomic rename;
        // fall back to a copy across filesystem boundaries.
        if (! @rename($stagedVendor, $liveVendor)) {
            File::ensureDirectoryExists($liveVendor);
            File::copyDirectory($stagedVendor, $liveVendor);
        }
    }

    /**
     * Restore vendor/ from the retained vendor.old (failure rollback).
     */
    protected function restoreOldVendor(?string $base = null): void
    {
        $base ??= base_path();
        $liveVendor = $base.'/vendor';
        $oldVendor = $base.'/vendor.old';

        if (! is_dir($oldVendor)) {
            throw new RuntimeException('No vendor.old to roll back from.');
        }

        if (is_dir($liveVendor)) {
            File::deleteDirectory($liveVendor);
        }
        if (! @rename($oldVendor, $liveVendor)) {
            File::ensureDirectoryExists($liveVendor);
            File::copyDirectory($oldVendor, $liveVendor);
            File::deleteDirectory($oldVendor);
        }
    }

    /**
     * Discard the retained vendor.old after a successful update.
     */
    protected function cleanupOldVendor(?string $base = null): void
    {
        $oldVendor = ($base ?? base_path()).'/vendor.old';
        if (is_dir($oldVendor)) {
            File::deleteDirectory($oldVendor);
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
}
