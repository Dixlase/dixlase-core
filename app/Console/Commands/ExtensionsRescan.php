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

namespace App\Console\Commands;

use App\Models\Plugin;
use App\Models\PluginAudit;
use App\Models\Theme;
use App\Models\ThemeAudit;
use App\Services\Extension\ExtensionDisplayName;
use App\Services\Extension\ExtensionRescanService;
use App\Services\Security\ScheduledSecurityCheckMonitor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Rescan every enabled plugin and the enabled theme(s).
 *
 * Runs the same full audit as the admin "Rescan" button
 * (ExtensionRescanService) and hands each result to
 * ScheduledSecurityCheckMonitor, which raises an admin banner when a status
 * got worse or a signature stopped verifying, and clears it once resolved.
 * Scheduled daily in routes/console.php.
 */
class ExtensionsRescan extends Command
{
    protected $signature = 'dls:extensions:rescan';

    protected $description = 'Rescan enabled plugins and themes and raise an admin alert when their status gets worse';

    public function handle(ExtensionRescanService $rescan, ScheduledSecurityCheckMonitor $monitor): int
    {
        $scannedKeys = [];
        $failed = 0;

        foreach ($this->enabledPluginSlugs() as $slug) {
            $scannedKeys[] = ScheduledSecurityCheckMonitor::extensionKey('plugin', $slug);
            $failed += $this->rescanOne('plugin', $slug, $rescan, $monitor) ? 0 : 1;
        }

        foreach ($this->enabledThemeSlugs() as $slug) {
            $scannedKeys[] = ScheduledSecurityCheckMonitor::extensionKey('theme', $slug);
            $failed += $this->rescanOne('theme', $slug, $rescan, $monitor) ? 0 : 1;
        }

        $monitor->pruneExtensions($scannedKeys);

        $this->info(sprintf(
            '[%s] Rescanned %d extension(s), %d failed, %d alert(s) open.',
            now()->toIso8601String(),
            count($scannedKeys),
            $failed,
            count($monitor->extensionAlerts()),
        ));

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @param  'plugin'|'theme'  $type
     */
    protected function rescanOne(string $type, string $slug, ExtensionRescanService $rescan, ScheduledSecurityCheckMonitor $monitor): bool
    {
        try {
            $before = $this->auditSnapshot($type, $slug);

            if ($type === 'plugin') {
                $rescan->rescanPlugin($slug);
            } else {
                $rescan->rescanTheme($slug);
            }

            $monitor->recordExtensionRescan(
                $type,
                $slug,
                ExtensionDisplayName::for($type, $slug),
                $before,
                $this->auditSnapshot($type, $slug),
            );

            return true;
        } catch (Throwable $e) {
            Log::error('Scheduled extension rescan failed', [
                'type' => $type,
                'slug' => $slug,
                'error' => $e->getMessage(),
            ]);
            $this->warn("{$type} {$slug}: ".$e->getMessage());

            return false;
        }
    }

    /**
     * @return array{health_status: string|null, signature_status: string|null}|null
     */
    protected function auditSnapshot(string $type, string $slug): ?array
    {
        $audit = $type === 'plugin' ? PluginAudit::getBySlug($slug) : ThemeAudit::getBySlug($slug);

        if ($audit === null) {
            return null;
        }

        return [
            'health_status' => $audit->health_status,
            'signature_status' => $audit->signature_status,
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function enabledPluginSlugs(): array
    {
        return Plugin::query()->enabled()->installed()->orderBy('slug')->pluck('slug')->filter()->values()->all();
    }

    /**
     * The theme(s) currently selected as the front theme, on any site.
     *
     * @return array<int, string>
     */
    protected function enabledThemeSlugs(): array
    {
        try {
            $ids = DB::table('theme_settings')
                ->where('key', 'enabled_theme_id')
                ->pluck('value')
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->all();
        } catch (Throwable) {
            return [];
        }

        if ($ids === []) {
            return [];
        }

        return Theme::query()->installed()->whereIn('id', $ids)->orderBy('slug')->pluck('slug')->filter()->values()->all();
    }
}
