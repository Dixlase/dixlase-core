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

use App\Models\CoreRelease;
use App\Models\Plugin;
use App\Models\Theme;
use App\Services\Extension\ExtensionSourceManager;
use App\Services\SystemNotificationService;
use Illuminate\Console\Command;

class SourceCheck extends Command
{
    protected $signature = 'dls:source:check
                            {--no-notify : Skip notification email even if new updates are found}';

    protected $description = 'Check all installed extensions for available updates';

    public function handle(ExtensionSourceManager $manager): int
    {
        $this->info('Checking for updates...');

        $result = $manager->checkUpdates();

        $pluginUpdates = $result['plugins'];
        $themeUpdates = $result['themes'];
        $coreUpdate = $result['core'] ?? null;

        if (empty($pluginUpdates) && empty($themeUpdates) && $coreUpdate === null) {
            $this->info('All extensions and the core are up to date.');

            return self::SUCCESS;
        }

        if ($coreUpdate !== null) {
            $this->newLine();
            $this->info('Core update available:');
            $this->table(
                ['Component', 'Current', 'Available'],
                [['core', $coreUpdate['current'], $coreUpdate['available']]]
            );
        }

        if (! empty($pluginUpdates)) {
            $this->newLine();
            $this->info('Plugin updates available:');
            $this->table(
                ['Slug', 'Current', 'Available'],
                array_map(fn (array $u) => [$u['slug'], $u['current'], $u['available']], $pluginUpdates)
            );
        }

        if (! empty($themeUpdates)) {
            $this->newLine();
            $this->info('Theme updates available:');
            $this->table(
                ['Slug', 'Current', 'Available'],
                array_map(fn (array $u) => [$u['slug'], $u['current'], $u['available']], $themeUpdates)
            );
        }

        // Suppress re-notification of the same version: only notify when last_notified_version != available_version
        if (! $this->option('no-notify')) {
            $this->notifyAdmin($pluginUpdates, $themeUpdates, $coreUpdate);
        }

        return self::SUCCESS;
    }

    /**
     * Send email notification to administrator about newly found updates and advance last_notified_version
     *
     * @param  array<int, array{slug: string, current: string, available: string, source_id: int|null}>  $pluginUpdates
     * @param  array<int, array{slug: string, current: string, available: string, source_id: int|null}>  $themeUpdates
     * @param  ?array{current: string, available: string, source_id: ?int, release_url: ?string}  $coreUpdate
     */
    protected function notifyAdmin(array $pluginUpdates, array $themeUpdates, ?array $coreUpdate): void
    {
        $newPluginUpdates = $this->filterUnnotified($pluginUpdates, Plugin::class);
        $newThemeUpdates = $this->filterUnnotified($themeUpdates, Theme::class);
        $newCoreUpdate = $this->filterUnnotifiedCore($coreUpdate);

        if (empty($newPluginUpdates) && empty($newThemeUpdates) && $newCoreUpdate === null) {
            $this->line('No new (unnotified) updates. Skipping notification email.');

            return;
        }

        $totalCount = count($newPluginUpdates) + count($newThemeUpdates) + ($newCoreUpdate !== null ? 1 : 0);
        $subject = __('admin/extensions/notifications.update_available_subject', ['count' => $totalCount]);
        $body = $this->buildNotificationBody($newPluginUpdates, $newThemeUpdates, $newCoreUpdate);

        $sent = app(SystemNotificationService::class)->sendAdminNotification(
            $subject,
            $body,
            ['type' => 'extension_update_available', 'count' => $totalCount]
        );

        if (! $sent) {
            $this->warn('Notification email could not be sent (mail server not configured or notifications disabled).');

            return;
        }

        // Update last_notified_version only for those successfully notified
        foreach ($newPluginUpdates as $update) {
            Plugin::query()->where('slug', $update['slug'])->update(['last_notified_version' => $update['available']]);
        }
        foreach ($newThemeUpdates as $update) {
            Theme::query()->where('slug', $update['slug'])->update(['last_notified_version' => $update['available']]);
        }
        if ($newCoreUpdate !== null) {
            CoreRelease::singleton()->forceFill(['last_notified_version' => $newCoreUpdate['available']])->save();
        }

        $this->info("Notification email sent ({$totalCount} new update(s)).");
    }

    /**
     * Return the core update only if it has not been notified yet.
     *
     * @param  ?array{current: string, available: string, source_id: ?int, release_url: ?string}  $coreUpdate
     * @return ?array{current: string, available: string, source_id: ?int, release_url: ?string}
     */
    protected function filterUnnotifiedCore(?array $coreUpdate): ?array
    {
        if ($coreUpdate === null) {
            return null;
        }

        $state = CoreRelease::singleton();
        if ($state->last_notified_version === $coreUpdate['available']) {
            return null;
        }

        return $coreUpdate;
    }

    /**
     * Extract only those where available_version differs from last_notified_version
     *
     * @param  array<int, array{slug: string, current: string, available: string, source_id: int|null}>  $updates
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelClass
     * @return array<int, array{slug: string, current: string, available: string, source_id: int|null}>
     */
    protected function filterUnnotified(array $updates, string $modelClass): array
    {
        $filtered = [];
        foreach ($updates as $update) {
            $row = $modelClass::query()->where('slug', $update['slug'])->first();
            if (! $row) {
                continue;
            }
            if ($row->last_notified_version === $update['available']) {
                continue;
            }
            $filtered[] = $update;
        }

        return $filtered;
    }

    /**
     * @param  array<int, array{slug: string, current: string, available: string, source_id: int|null}>  $pluginUpdates
     * @param  array<int, array{slug: string, current: string, available: string, source_id: int|null}>  $themeUpdates
     * @param  ?array{current: string, available: string, source_id: ?int, release_url: ?string}  $coreUpdate
     */
    protected function buildNotificationBody(array $pluginUpdates, array $themeUpdates, ?array $coreUpdate): string
    {
        $lines = [];

        if ($coreUpdate !== null) {
            $lines[] = __('admin/extensions/notifications.core_update_heading');
            $lines[] = "- core: v{$coreUpdate['current']} → v{$coreUpdate['available']}";
            $lines[] = '';
        }

        if (! empty($pluginUpdates)) {
            $lines[] = __('admin/extensions/notifications.plugin_updates_heading');
            foreach ($pluginUpdates as $u) {
                $lines[] = "- {$u['slug']}: v{$u['current']} → v{$u['available']}";
            }
            $lines[] = '';
        }

        if (! empty($themeUpdates)) {
            $lines[] = __('admin/extensions/notifications.theme_updates_heading');
            foreach ($themeUpdates as $u) {
                $lines[] = "- {$u['slug']}: v{$u['current']} → v{$u['available']}";
            }
            $lines[] = '';
        }

        $lines[] = __('admin/extensions/notifications.review_in_admin');

        return implode("\n", $lines);
    }
}
