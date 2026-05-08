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
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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
 *   6. Run migrations + clear caches
 *   7. Record a new core_version_history row + update core_releases
 *   8. On any failure, restore from snapshot and re-throw
 *
 * This is intentionally CLI-driven — running it from a web request would
 * replace the running code mid-flight. UI integration (maintenance mode +
 * queue) is handled in B-2.
 */
class CoreUpdater
{
    public function __construct(
        protected ExtensionSourceManager $sourceManager,
        protected CoreSourceSnapshot $snapshotter,
    ) {}

    /**
     * @param  ?Closure(string): void  $log  Optional sink for progress lines
     * @return array{from: ?string, to: string, snapshot: string, history_id: int}
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

            $log('Applying source over live tree...');
            $this->applyToLiveTree($payloadRoot);
            $log('Applied source.');

            $log('Running migrations...');
            Artisan::call('migrate', ['--force' => true, '--no-interaction' => true]);
            $log('Migrations complete.');

            $log('Clearing caches...');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            Artisan::call('cache:clear');
            $log('Caches cleared.');

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

            // Surface the failure on the singleton so the next render shows it.
            $coreState->forceFill([
                'update_failed_at' => now(),
                'update_failure_reason' => $this->truncateReason($e->getMessage()),
            ])->save();

            $this->cleanupStaging($stagingPath);

            throw $e;
        }
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
            if (is_dir($liveDir)) {
                File::deleteDirectory($liveDir);
            }
            File::ensureDirectoryExists(dirname($liveDir));
            File::copyDirectory($stagedDir, $liveDir);
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
