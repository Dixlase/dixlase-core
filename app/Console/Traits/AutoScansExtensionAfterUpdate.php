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

use App\Repositories\SecuritySettingRepository;
use App\Services\Extension\ExtensionRescanService;

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
}
