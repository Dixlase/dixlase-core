<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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

namespace App\Http\Controllers\Admin\Settings\Systems;

use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Models\Plugin;
use App\Models\Theme;
use App\Services\Extension\ExtensionSourceManager;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;

/**
 * Unified update management page
 *
 * View and apply available updates for Core / plugins / themes in a single screen
 * - GET  /admin/.../system/updates           Display page (auto-check if stale)
 * - POST /admin/.../system/updates/check     Force recheck → redirect to same page
 * - POST /admin/.../system/updates/apply     Update selected targets sequentially
 */
class AdminSystemUpdatesController extends AdminLoggedInController
{
    /**
     * Fallback threshold (seconds) used by isStale() when the security
     * setting cannot be read. Kept as a class constant — not a config
     * value — because it is purely a defensive default for the case
     * where SecuritySettingsRegistry is unavailable; the operator-facing
     * setting lives at `extension_update_check_interval`.
     */
    protected const FALLBACK_STALE_THRESHOLD_SECONDS = 86400; // 24 hours

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Display update management page
     *
     * - Auto-recheck if STALE_THRESHOLD_SECONDS or more have elapsed since last check
     * - Force recheck with query ?check=1
     * - Pre-select target with query ?target=plugin:slug or theme:slug
     */
    public function index(Request $request, ExtensionSourceManager $manager)
    {
        $forceCheck = $request->boolean('check');
        $target = $this->parseTarget((string) $request->query('target', ''));

        $shouldCheck = $forceCheck || $this->isStale();
        if ($shouldCheck) {
            try {
                $manager->checkUpdates();
            } catch (\Throwable $e) {
                // Check failure is not fatal (existing last_version_check / available_version remain so screen can still be displayed)
                report($e);
            }
        }

        $plugins = Plugin::query()
            ->whereNotNull('available_version')
            ->orderBy('slug')
            ->get(['id', 'slug', 'name', 'version', 'available_version', 'directory', 'update_failed_at', 'update_failure_reason'])
            ->map(fn (Plugin $p) => [
                'id' => $p->id,
                'slug' => $p->slug,
                'name' => $p->name,
                'currentVersion' => $p->version,
                'availableVersion' => $p->available_version,
                'directory' => $p->directory,
                'preselected' => $target['type'] === 'plugin' && $target['slug'] === $p->slug,
                'updateFailedAt' => $p->update_failed_at,
                'updateFailedAtFormatted' => $p->update_failed_at?->format('Y/m/d H:i'),
                'updateFailureReason' => $p->update_failure_reason,
            ])
            ->values()
            ->all();

        $themes = Theme::query()
            ->whereNotNull('available_version')
            ->orderBy('slug')
            ->get(['id', 'slug', 'name', 'version', 'available_version', 'directory', 'update_failed_at', 'update_failure_reason'])
            ->map(fn (Theme $t) => [
                'id' => $t->id,
                'slug' => $t->slug,
                'name' => $t->name,
                'currentVersion' => $t->version,
                'availableVersion' => $t->available_version,
                'directory' => $t->directory,
                'preselected' => $target['type'] === 'theme' && $target['slug'] === $t->slug,
                'updateFailedAt' => $t->update_failed_at,
                'updateFailedAtFormatted' => $t->update_failed_at?->format('Y/m/d H:i'),
                'updateFailureReason' => $t->update_failure_reason,
            ])
            ->values()
            ->all();

        // Core update state from the singleton core_releases row.
        $core = $this->buildCoreSection($target);

        $lastCheckedAt = $this->getLastCheckedAt();

        $this->viewParams['heading'] = __('admin/settings/systems/updates.heading');
        $this->viewParams['plugins'] = $plugins;
        $this->viewParams['themes'] = $themes;
        $this->viewParams['core'] = $core;
        $this->viewParams['lastCheckedAt'] = $lastCheckedAt;
        $this->viewParams['lastCheckedAtFormatted'] = $lastCheckedAt?->format('Y/m/d H:i');
        $this->viewParams['totalCount'] = count($plugins) + count($themes) + ($core['available'] ? 1 : 0);

        return view('admin::settings.systems.updates.index', $this->viewParams);
    }

    /**
     * Force check → redirect to same page
     */
    public function check(ExtensionSourceManager $manager)
    {
        try {
            $manager->checkUpdates();

            return redirect()->route('admin.settings.systems.updates.index')
                ->with('success', __('admin/settings/systems/updates.messages.check_done'));
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('admin.settings.systems.updates.index')
                ->with('error', __('admin/settings/systems/updates.messages.check_failed', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Update selected targets sequentially
     *
     * Form data:
     *   plugins[] = id (selected plugin IDs)
     *   themes[]  = id (selected theme IDs)
     *
     * Core update is excluded from web-driven apply: the page renders a
     * copyable `dls:core:update` CLI command instead, since replacing core
     * code mid-request would tear down the running app.
     */
    public function apply(Request $request)
    {
        $request->validate([
            'plugins' => 'array',
            'plugins.*' => 'integer',
            'themes' => 'array',
            'themes.*' => 'integer',
        ]);

        $pluginIds = $request->input('plugins', []);
        $themeIds = $request->input('themes', []);

        if (empty($pluginIds) && empty($themeIds)) {
            return redirect()->route('admin.settings.systems.updates.index')
                ->with('info', __('admin/settings/systems/updates.messages.no_selection'));
        }

        $succeeded = 0;
        $failed = 0;

        // Plugin update (delegated to existing dls:plugin:update CLI)
        foreach ($pluginIds as $id) {
            $plugin = Plugin::query()->find($id);
            if (! $plugin || ! $plugin->hasUpdateAvailable()) {
                continue;
            }
            $code = Artisan::call('dls:plugin:update', [
                'slug' => $plugin->slug,
                '--force' => true,
            ]);
            $code === 0 ? $succeeded++ : $failed++;
        }

        // Theme update
        foreach ($themeIds as $id) {
            $theme = Theme::query()->find($id);
            if (! $theme || ! $theme->hasUpdateAvailable()) {
                continue;
            }
            $code = Artisan::call('dls:theme:update', [
                'slug' => $theme->slug,
                '--force' => true,
            ]);
            $code === 0 ? $succeeded++ : $failed++;
        }

        $total = $succeeded + $failed;
        $summary = __('admin/settings/systems/updates.messages.apply_summary', [
            'total' => $total,
            'succeeded' => $succeeded,
            'failed' => $failed,
        ]);

        return redirect()->route('admin.settings.systems.updates.index')
            ->with($failed === 0 ? 'success' : 'error', $summary);
    }

    /**
     * Parse query in `?target=plugin:slug` format. The literal value `core`
     * (no slug) selects the core row.
     *
     * @return array{type: ?string, slug: ?string}
     */
    protected function parseTarget(string $target): array
    {
        if ($target === '') {
            return ['type' => null, 'slug' => null];
        }

        if ($target === 'core') {
            return ['type' => 'core', 'slug' => null];
        }

        if (! str_contains($target, ':')) {
            return ['type' => null, 'slug' => null];
        }
        [$type, $slug] = explode(':', $target, 2);
        if (! in_array($type, ['plugin', 'theme'], true) || $slug === '') {
            return ['type' => null, 'slug' => null];
        }

        return ['type' => $type, 'slug' => $slug];
    }

    /**
     * Build the view payload for the Core section, sourced from the singleton
     * `core_releases` row.
     *
     * @param  array{type: ?string, slug: ?string}  $target
     * @return array{available: bool, current_version: ?string, available_version: ?string, available_version_published_at: ?\Illuminate\Support\Carbon, release_url: ?string, preselected: bool}
     */
    protected function buildCoreSection(array $target): array
    {
        $state = \App\Models\CoreRelease::singleton();
        $current = (string) (\App\Models\CoreVersionHistory::currentVersion() ?? config('app.version', '0.0.0'));
        $available = $state->available_version !== null
            && version_compare($state->available_version, $current, '>');

        return [
            'available' => $available,
            'current_version' => $current,
            'available_version' => $available ? $state->available_version : null,
            'available_version_published_at' => $available ? $state->available_version_published_at : null,
            'release_url' => $available ? $state->release_url : null,
            'preselected' => $available && $target['type'] === 'core',
        ];
    }

    /**
     * Oldest last check time among all installed extensions and the core
     */
    protected function getLastCheckedAt(): ?Carbon
    {
        $candidates = array_filter([
            Plugin::query()->min('last_version_check'),
            Theme::query()->min('last_version_check'),
            \App\Models\CoreRelease::singleton()->last_version_check,
        ]);

        if (empty($candidates)) {
            return null;
        }

        return Carbon::parse(min($candidates));
    }

    /**
     * Determine whether to trigger auto-check on page load.
     *
     * The threshold honours the operator-configured
     * `extension_update_check_interval` security setting, matching the
     * cron-side `dls:source:check` schedule in routes/console.php. This
     * keeps the two trigger paths consistent — previously the cron path
     * respected the setting while this page-load path used a hardcoded
     * 6h, so a "manual only" configuration still got automatic checks
     * here and a "12h" configuration got both 6h and 12h ticks at
     * once.
     *
     * Returns false when the setting is 0 (manual only): the operator
     * has explicitly opted out of automatic checks and we must not
     * override that on page load.
     */
    protected function isStale(): bool
    {
        $interval = $this->resolveCheckInterval();

        // 0 = manual-only. The operator opted out of automatic checks.
        if ($interval <= 0) {
            return false;
        }

        $last = $this->getLastCheckedAt();
        if ($last === null) {
            return true;
        }

        return $last->lt(now()->subSeconds($interval));
    }

    /**
     * Read the operator-configured update-check interval in seconds.
     *
     * Mirrors the lookup in routes/console.php so both auto-check
     * triggers see the same value. Falls back to
     * FALLBACK_STALE_THRESHOLD_SECONDS when the settings registry
     * throws (e.g. table missing during early install).
     */
    protected function resolveCheckInterval(): int
    {
        try {
            $value = \App\Services\SecuritySettingsRegistry::get('extension_update_check_interval');
            if ($value !== null && $value !== '') {
                return (int) $value;
            }
        } catch (\Throwable) {
            // Fall through to defaults.
        }

        return (int) config('extension-sources.check_interval', self::FALLBACK_STALE_THRESHOLD_SECONDS);
    }
}
