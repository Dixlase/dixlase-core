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

namespace App\Console\Traits;

use App\Enums\PluginEnableAction;
use App\Exceptions\ExtensionUpdateBlockedException;
use App\Repositories\SecuritySettingRepository;
use App\Services\Extension\ExtensionRescanService;
use App\Services\Plugin\PluginHealthScorer;
use App\Services\Theme\ThemeHealthScorer;

/**
 * Runs an audit (health / permission / CSP scan) right after a successful
 * plugin or theme update when the `extension_auto_scan_after_update` security
 * setting is on (default true), so the extension's health, permissions, CSP
 * status, and file-change signal reflect the newly installed code instead of
 * leaving a stale "rescan recommended" warning.
 *
 * Best-effort: the update has already committed by the time this runs, so a
 * scan failure must never fail the update. It is caught and logged, and the
 * "rescan recommended" surface remains as the fallback signal.
 *
 * @internal Core only. Do not reference from plugins/themes
 */
trait AutoScansExtensionAfterUpdate
{
    /**
     * @param  'plugin'|'theme'  $kind
     */
    protected function autoScanAfterUpdate(string $kind, string $slug): void
    {
        try {
            $enabled = filter_var(
                app(SecuritySettingRepository::class)->get('extension_auto_scan_after_update', true),
                FILTER_VALIDATE_BOOLEAN,
            );
            if (! $enabled) {
                $this->line('Post-update auto-scan is disabled (extension_auto_scan_after_update); skipping.');

                return;
            }

            $this->info('Running post-update scan...');
            // Full rescan (permissions + CSP + signature + health + files_hash),
            // the same one the admin rescan button runs — so the new version's
            // audit is complete and the "rescan recommended" warning clears.
            if ($kind === 'theme') {
                app(ExtensionRescanService::class)->rescanTheme($slug);
            } else {
                app(ExtensionRescanService::class)->rescanPlugin($slug);
            }
            $this->info('Post-update scan complete.');
        } catch (\Throwable $e) {
            $this->warn('Post-update scan failed (non-fatal): '.$e->getMessage());
        }
    }

    /**
     * Scan the new version as soon as it is on disk and refuse it when its
     * health check resolves to Blocked -- the same rule that refuses to
     * enable a Blocked extension.
     *
     * Called right after the new files replace the old ones and before any
     * migration, seeder or npm build runs, so a refused version never
     * touches the database. Throwing hands control to the update command's
     * failure path, which restores the previous version. Fails closed: a
     * version that cannot be scanned is refused too.
     *
     * Unlike autoScanAfterUpdate() this ignores the
     * `extension_auto_scan_after_update` setting: that setting decides
     * whether the audit is refreshed for display, not whether the gate runs.
     *
     * @param  'plugin'|'theme'  $kind
     *
     * @throws ExtensionUpdateBlockedException
     */
    protected function refuseBlockedUpdate(string $kind, string $slug): void
    {
        $this->info('Scanning the new version before applying it...');

        try {
            if ($kind === 'theme') {
                app(ExtensionRescanService::class)->rescanTheme($slug);
                $scorer = app(ThemeHealthScorer::class);
            } else {
                app(ExtensionRescanService::class)->rescanPlugin($slug);
                $scorer = app(PluginHealthScorer::class);
            }
            $action = $scorer->determineEnableAction($scorer->calculate($slug));
        } catch (\Throwable $e) {
            throw new ExtensionUpdateBlockedException(
                "The new version could not be scanned, so it was not applied: {$e->getMessage()}",
                previous: $e,
            );
        }

        if ($action === PluginEnableAction::Blocked) {
            throw new ExtensionUpdateBlockedException(
                'The new version scans as Blocked under the current extension security settings, so it was not applied.'
            );
        }
    }

    /**
     * Refuse an update whose extracted manifest declares a different version
     * than the release that was requested.
     *
     * @throws ExtensionUpdateBlockedException
     */
    protected function refuseVersionMismatch(string $manifestPath, string $expectedVersion): void
    {
        $data = is_file($manifestPath) ? json_decode((string) file_get_contents($manifestPath), true) : null;
        $actual = is_array($data) && is_string($data['version'] ?? null) ? ltrim($data['version'], 'vV') : null;
        $expected = ltrim($expectedVersion, 'vV');

        if ($actual !== $expected) {
            throw new ExtensionUpdateBlockedException(sprintf(
                'The downloaded archive declares version %s, not the requested %s, so it was not applied.',
                $actual ?? '(none)',
                $expected,
            ));
        }
    }

    /**
     * After a refused update has been rolled back, scan again so the stored
     * audit describes the restored version rather than the refused one.
     * Best-effort, like autoScanAfterUpdate().
     *
     * @param  'plugin'|'theme'  $kind
     */
    protected function rescanAfterRefusedUpdate(string $kind, string $slug): void
    {
        try {
            if ($kind === 'theme') {
                app(ExtensionRescanService::class)->rescanTheme($slug);
            } else {
                app(ExtensionRescanService::class)->rescanPlugin($slug);
            }
        } catch (\Throwable $e) {
            $this->warn('Rescan of the restored version failed (non-fatal): '.$e->getMessage());
        }
    }
}
