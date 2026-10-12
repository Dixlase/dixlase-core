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

namespace App\Services\Extension;

use App\Models\AuditLog;
use App\Models\Plugin;
use App\Models\Theme;
use App\Services\AuditService;
use Illuminate\Support\Facades\Log;

/**
 * Reconciles the version recorded for an extension with the version its
 * manifest declares on disk.
 *
 * `plugins.version` / `themes.version` are written only by the install and
 * update paths (ExtensionOperationService, and CoreUpdater for a bundled
 * theme). An extension whose files arrive any other way -- a git deploy, an
 * operator replacing a directory -- keeps whatever version was recorded when
 * it was first installed, and nothing brings the two back together
 * (dixlase-core#521).
 *
 * The reconciliation runs from the rescan, not from the deploy: the rescan
 * already opens every extension's manifest, it is reachable from the admin
 * panel, and it runs on a schedule (#500), so one place covers every route by
 * which files can arrive. dls:extensions:reconcile-versions is the same thing
 * without the rest of the audit, for a deploy to call once it has placed
 * files.
 *
 * A version that moves without an install is recorded in the audit log as a
 * warning, so it is visible rather than silent.
 *
 * Never throws for the audit write: a failed log entry must not turn a
 * finished reconciliation into a failed one.
 *
 * @internal Core only. Do not reference from plugins/themes
 */
class ExtensionVersionReconciler
{
    public function __construct(
        protected AuditService $audit,
    ) {}

    /**
     * Reconcile one extension.
     *
     * Returns the change when the row moved, or null when the manifest is
     * unreadable or the two already agree. An unreadable manifest leaves the
     * row alone: it means the directory is missing or broken, which is not a
     * reason to forget which version the site believes it has.
     *
     * @return array{kind: string, slug: string, from: ?string, to: string}|null
     */
    public function reconcile(Plugin|Theme $extension, bool $dryRun = false): ?array
    {
        $onDisk = $extension->versionOnDisk();

        if ($onDisk === null) {
            return null;
        }

        $recorded = is_string($extension->version) && $extension->version !== ''
            ? $extension->version
            : null;

        if ($recorded === $onDisk) {
            return null;
        }

        $change = [
            'kind' => $extension instanceof Plugin ? 'plugin' : 'theme',
            'slug' => (string) $extension->slug,
            'from' => $recorded,
            'to' => $onDisk,
        ];

        if ($dryRun) {
            return $change;
        }

        $extension->update(['version' => $onDisk]);
        $this->record($extension, $change);

        return $change;
    }

    /**
     * Reconcile every installed plugin and theme.
     *
     * Covers installed extensions rather than enabled ones: a disabled plugin
     * still has a row the admin panel reads, and dls:extensions:rescan only
     * walks the enabled ones.
     *
     * @return array<int, array{kind: string, slug: string, from: ?string, to: string}>
     */
    public function reconcileAll(bool $dryRun = false): array
    {
        $changes = [];

        foreach (Plugin::query()->installed()->orderBy('slug')->get() as $plugin) {
            $change = $this->reconcile($plugin, $dryRun);
            if ($change !== null) {
                $changes[] = $change;
            }
        }

        foreach (Theme::query()->installed()->orderBy('slug')->get() as $theme) {
            $change = $this->reconcile($theme, $dryRun);
            if ($change !== null) {
                $changes[] = $change;
            }
        }

        return $changes;
    }

    /**
     * Reconcile the plugin with this slug, if there is a row for it.
     *
     * The rescan works in slugs, so this keeps the lookup here instead of in
     * ExtensionRescanService.
     *
     * @return array{kind: string, slug: string, from: ?string, to: string}|null
     */
    public function reconcilePluginBySlug(string $slug): ?array
    {
        $plugin = Plugin::query()->where('slug', $slug)->first();

        return $plugin === null ? null : $this->reconcile($plugin);
    }

    /**
     * Reconcile the theme with this slug, if there is a row for it.
     *
     * @return array{kind: string, slug: string, from: ?string, to: string}|null
     */
    public function reconcileThemeBySlug(string $slug): ?array
    {
        $theme = Theme::query()->where('slug', $slug)->first();

        return $theme === null ? null : $this->reconcile($theme);
    }

    /**
     * @param  array{kind: string, slug: string, from: ?string, to: string}  $change
     */
    protected function record(Plugin|Theme $extension, array $change): void
    {
        try {
            $this->audit->logExtension(
                $extension instanceof Plugin
                    ? AuditLog::ACTION_PLUGIN_VERSION_RECONCILED
                    : AuditLog::ACTION_THEME_VERSION_RECONCILED,
                [
                    // A version moving with no install behind it is worth a
                    // warning: either a deploy placed files, or something
                    // replaced the directory.
                    'severity' => AuditLog::SEVERITY_WARNING,
                    'outcome' => AuditLog::OUTCOME_SUCCESS,
                    'target' => $extension,
                    'target_label' => sprintf(
                        '%s %s v%s → v%s',
                        $change['kind'],
                        $change['slug'],
                        $change['from'] ?? '?',
                        $change['to'],
                    ),
                    'context' => [
                        $change['kind'].'_slug' => $change['slug'],
                        'from' => $change['from'],
                        'to' => $change['to'],
                        'reconciled_from' => 'manifest',
                    ],
                ],
            );
        } catch (\Throwable $e) {
            Log::warning('Failed to record an extension version reconciliation', [
                'extension' => $change['slug'],
                'error' => $e->getMessage(),
            ]);
        }
    }
}
