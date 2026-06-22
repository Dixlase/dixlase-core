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

use App\Services\Extension\ExtensionSourceManager;
use Closure;
use Illuminate\Support\Facades\File;
use RuntimeException;
use ZipArchive;

/**
 * Owns every vendor/ mutation a core operation performs.
 *
 * Production installs are not guaranteed to have Composer or Node, so
 * dependencies are never built on the box — every Dixlase release ZIP ships
 * a ready-to-run vendor/. This service swaps that vendor/ into place
 * wholesale and keeps the previous one at vendor.old for a local,
 * network-free rollback. Both the forward updater (CoreUpdater) and the
 * rollback path (CoreRestoreService) drive vendor/ through here so the
 * swap / rollback semantics live in exactly one place.
 *
 * The swap relies on an atomic rename when the staged tree shares the live
 * filesystem (the normal case — staging lives under the project root); it
 * falls back to a copy across filesystem boundaries.
 */
class CoreVendorManager
{
    public function __construct(
        protected ExtensionSourceManager $sourceManager,
    ) {}

    /**
     * Decide whether a staged release changes PHP dependencies, by
     * comparing its composer.lock against the installed one. A missing
     * staged lock means the release does not pin dependencies, so there is
     * nothing to swap (treated as "unchanged"). A missing live lock with a
     * present staged lock counts as a change (first time the lock appears).
     */
    public function lockChanged(string $payloadRoot, ?string $base = null): bool
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
     * Compare two composer.lock files for equality (treating a missing
     * file as "no lock"). Used to detect that a restore crossed a
     * dependency boundary, where one side is read straight out of a backup
     * archive rather than from a staged release tree.
     */
    public function locksMatch(?string $lockA, ?string $lockB): bool
    {
        $hashA = ($lockA !== null && is_file($lockA)) ? hash_file('sha256', $lockA) : null;
        $hashB = ($lockB !== null && is_file($lockB)) ? hash_file('sha256', $lockB) : null;

        return $hashA === $hashB;
    }

    /**
     * Swap the live vendor/ for the staged one (at $payloadRoot/vendor),
     * retaining the previous vendor/ at vendor.old so a failed operation
     * can be rolled back locally (no Composer, no network). Callers must
     * run this inside a maintenance window: vendor/ is briefly absent /
     * incomplete and any request booting the framework would fatal.
     *
     * vendor.old is discarded on success (discardPrevious) and restored on
     * failure (restorePrevious).
     */
    public function swap(string $payloadRoot, ?string $base = null): void
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
    public function restorePrevious(?string $base = null): void
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
     * Discard the retained vendor.old after a successful operation.
     */
    public function discardPrevious(?string $base = null): void
    {
        $oldVendor = ($base ?? base_path()).'/vendor.old';
        if (is_dir($oldVendor)) {
            File::deleteDirectory($oldVendor);
        }
    }

    /**
     * Download a specific core release, extract it, and swap in *its*
     * vendor/. Used by the rollback path: after a backup restore winds the
     * source tree back to an older version, the live vendor/ still matches
     * the newer release, so we re-fetch the matching dependencies from the
     * old release ZIP (the pre-update backup deliberately excludes vendor/
     * because it is huge). Local source and built assets come from the
     * backup; only the heavy vendor/ is re-fetched.
     *
     * @param  ?Closure(string): void  $log
     */
    public function refetchAndSwap(string $version, ?Closure $log = null, ?string $base = null): void
    {
        $log ??= fn (string $line) => null;
        $stagingPath = storage_path('app/private/core-vendor-refetch/'.uniqid('v_', true));

        try {
            $log("Downloading core v{$version} for its vendor/...");
            $zipPath = $this->sourceManager->downloadCore($version);

            $log('Extracting release to locate vendor/...');
            $this->extract($zipPath, $stagingPath);
            $payloadRoot = $this->locateVendorPayload($stagingPath);

            $log('Swapping vendor/ from the re-fetched release...');
            $this->swap($payloadRoot, $base);
            $log('vendor/ re-fetched and swapped.');
        } finally {
            if (is_dir($stagingPath)) {
                File::deleteDirectory($stagingPath);
            }
        }
    }

    /**
     * Extract a release ZIP into a staging directory.
     */
    protected function extract(string $zipPath, string $stagingPath): void
    {
        File::ensureDirectoryExists($stagingPath);

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException("Failed to open release ZIP: {$zipPath}");
        }
        if (! $zip->extractTo($stagingPath)) {
            $zip->close();
            throw new RuntimeException("Failed to extract release ZIP to: {$stagingPath}");
        }
        $zip->close();
    }

    /**
     * Resolve the directory holding vendor/ inside an extracted release.
     * Release ZIPs wrap everything in a top-level dixlase-vX.Y.Z/ dir, so
     * the payload root is usually the single child directory; fall back to
     * the staging root if vendor/ sits directly under it.
     */
    protected function locateVendorPayload(string $stagingPath): string
    {
        if (is_dir($stagingPath.'/vendor')) {
            return $stagingPath;
        }

        foreach (glob($stagingPath.'/*', GLOB_ONLYDIR) ?: [] as $candidate) {
            if (is_dir($candidate.'/vendor')) {
                return $candidate;
            }
        }

        throw new RuntimeException("Extracted release contains no vendor/ directory under {$stagingPath}.");
    }
}
