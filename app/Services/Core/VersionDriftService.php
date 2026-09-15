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

use App\Models\CoreVersionHistory;

/**
 * Detects drift between the on-disk running code's version (VERSION file
 * committed at the repo root) and the ledger the update UI trusts
 * (`CoreVersionHistory::currentVersion()`).
 *
 * A checkout advanced via `git` rather than through `dls:core:update`
 * (typical for developer environments and any operator who prefers git-
 * based deploys) leaves the ledger stale — the UI then offers "updates"
 * that are actually downgrades against the real code. Finding #1's guard
 * refuses the destructive apply, but does nothing to warn the operator
 * BEFORE they click Apply. This service is the earlier-warning half:
 * inject it into any page that shows an "available update" and render a
 * drift banner when the two disagree, plus a pointer to
 * `dls:core:reconcile` (see App\Console\Commands\CoreReconcile) which
 * writes a synthetic ledger row so subsequent updates line up.
 *
 * The service is pure computation — no writes, no cache invalidation —
 * so it is cheap to call on every request that renders the Updates
 * page.
 */
class VersionDriftService
{
    /**
     * Read the real on-disk / ledger / manifest values and classify the
     * drift between them. This is the production entrypoint — controllers
     * and commands call this with no arguments.
     *
     * @return array{
     *   on_disk: ?string,
     *   ledger: ?string,
     *   known: bool,
     *   drifted: bool,
     *   kind: 'ahead'|'behind'|'same'|'unknown',
     *   manifest: ?string,
     *   manifest_drifted: bool,
     * }
     */
    public function detect(): array
    {
        return $this->classify(
            CoreUpdater::readVersionFromDisk(),
            CoreVersionHistory::currentVersion(),
            self::readManifestVersionFromDisk(),
        );
    }

    /**
     * Read the "version" declared by dixlase.json at the repo root.
     *
     * dixlase.json is the second on-disk version authority (#286) and must
     * always match the VERSION file. Returns null when the file is missing,
     * unreadable, not valid JSON, or has no non-empty string "version", so
     * the manifest check stays silent instead of raising a warning the
     * operator cannot act on.
     */
    public static function readManifestVersionFromDisk(?string $basePath = null): ?string
    {
        $basePath ??= base_path();
        $path = $basePath.DIRECTORY_SEPARATOR.'dixlase.json';

        if (! is_file($path)) {
            return null;
        }

        $raw = @file_get_contents($path);
        if ($raw === false) {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded) || ! is_string($decoded['version'] ?? null)) {
            return null;
        }

        $version = trim($decoded['version']);

        return $version !== '' ? $version : null;
    }

    /**
     * Pure classification of the version values, exposed as its own
     * method so tests can exercise every branch (including explicit
     * nulls on either side) without needing to stub the file / DB reads.
     * Consumers who already know the values — e.g. a caller who has
     * cached them for the request — can call this directly and skip the
     * repeated reads. `$manifest` is optional so existing two-argument
     * callers keep working; they simply get no manifest verdict.
     *
     * @return array{
     *   on_disk: ?string,
     *   ledger: ?string,
     *   known: bool,
     *   drifted: bool,
     *   kind: 'ahead'|'behind'|'same'|'unknown',
     *   manifest: ?string,
     *   manifest_drifted: bool,
     * }
     */
    public function classify(?string $onDisk, ?string $ledger, ?string $manifest = null): array
    {
        // The manifest check is deliberately kept apart from `drifted`.
        // `drifted` means "VERSION disagrees with the ledger": it drives the
        // Updates-page banner and is exactly what `dls:core:reconcile`
        // repairs by writing a ledger row. A dixlase.json that disagrees
        // with VERSION is a different fault — a partially applied update or
        // a hand edit — that no ledger row can fix, so it gets its own flag.
        // Only meaningful when both files are readable.
        $manifestDrifted = $manifest !== null && $onDisk !== null && $manifest !== $onDisk;

        // Drift is only meaningful when BOTH values are known. If either
        // side is null (very early install with no VERSION file, or a
        // brand-new install with no history rows yet), report
        // known=false / drifted=false so callers do not raise a warning
        // that the operator has no way to act on.
        if ($onDisk === null || $ledger === null) {
            return [
                'on_disk' => $onDisk,
                'ledger' => $ledger,
                'known' => false,
                'drifted' => false,
                'kind' => 'unknown',
                'manifest' => $manifest,
                'manifest_drifted' => $manifestDrifted,
            ];
        }

        $cmp = version_compare($onDisk, $ledger);
        $kind = match (true) {
            $cmp > 0 => 'ahead',   // on-disk is newer than the ledger (the observed 2026-07-27 bug shape)
            $cmp < 0 => 'behind',  // on-disk is OLDER than the ledger (extremely unusual; ledger claims a version the code never carried)
            default => 'same',
        };

        return [
            'on_disk' => $onDisk,
            'ledger' => $ledger,
            'known' => true,
            'drifted' => $kind !== 'same',
            'kind' => $kind,
            'manifest' => $manifest,
            'manifest_drifted' => $manifestDrifted,
        ];
    }
}
