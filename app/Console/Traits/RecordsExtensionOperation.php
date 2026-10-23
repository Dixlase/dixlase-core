<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
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

use App\Models\Member;
use App\Models\Plugin;
use App\Models\Theme;
use App\Services\Extension\ExtensionRescanService;
use App\Services\ExtensionOperationService;
use App\Services\Plugin\PluginPermissionService;
use App\Services\Theme\ThemePermissionService;
use Illuminate\Support\Facades\Log;

/**
 * Records a lifecycle command's operation in the audit log (dixlase-core#630).
 *
 * The record lives in the command rather than in the admin controller so
 * that every entry point -- the admin panel, the command line, another
 * command -- records exactly once, the way update and rollback already do
 * (ExtensionUpdateRecorder, #454). The admin panel names its member with
 * --applied-by; a command-line run names none, and the row's actor_source
 * says `cli`.
 *
 * A command using this trait declares
 * `{--applied-by= : Member id recorded as the actor (the admin screen passes it; CLI runs record none)}`.
 *
 * @internal Core only. Do not reference from plugins/themes
 */
trait RecordsExtensionOperation
{
    /**
     * What --force overrode in this run, keyed by the check that refused
     * (`license`, `activation_gate`). Recorded with the operation so a
     * forced install leaves its reason in the audit log.
     *
     * @var array<string, string>
     */
    protected array $forcedPast = [];

    /**
     * Record a successful operation. Never throws: the operation has
     * already happened, so a failure to record it is logged, not raised.
     *
     * @param  'plugin'|'theme'  $type
     * @param  string  $operation  ExtensionOperationService::OPERATION_*
     * @param  array{name: string, slug: string|null, version?: string|null, health_status?: string}  $data
     */
    protected function recordExtensionOperation(string $type, string $operation, Plugin|Theme|null $extension, array $data): void
    {
        try {
            app(ExtensionOperationService::class)->recordOperation(
                $type,
                $operation,
                $data + ['health_status' => 'low'],
                $this->appliedBy(),
                $extension,
                $this->forcedPast === [] ? [] : ['forced' => $this->forcedPast],
            );
        } catch (\Throwable $e) {
            Log::warning('Failed to record extension operation', [
                'type' => $type,
                'operation' => $operation,
                'slug' => $data['slug'] ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * The extension's health as its latest scan rates it, for the
     * operation row's severity and the unhealthy-extension notice.
     *
     * @param  'plugin'|'theme'  $type
     * @param  bool  $rescan  Scan first. An install does: nothing has scanned the new code yet
     */
    protected function extensionHealthStatus(string $type, string $slug, bool $rescan = false): string
    {
        try {
            if ($rescan) {
                $type === ExtensionOperationService::TYPE_PLUGIN
                    ? app(ExtensionRescanService::class)->rescanPlugin($slug)
                    : app(ExtensionRescanService::class)->rescanTheme($slug);
            }

            $summary = $type === ExtensionOperationService::TYPE_PLUGIN
                ? app(PluginPermissionService::class)->getSummary($slug)
                : app(ThemePermissionService::class)->getSummary($slug);

            return (string) ($summary['risk_level'] ?? 'unknown');
        } catch (\Throwable $e) {
            return 'unknown';
        }
    }

    /**
     * The member named by --applied-by, or null on a command-line run.
     */
    protected function appliedBy(): ?Member
    {
        $id = $this->hasOption('applied-by') ? $this->option('applied-by') : null;

        return is_numeric($id) ? Member::find((int) $id) : null;
    }
}
