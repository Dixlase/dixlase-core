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

namespace App\Http\Controllers\Admin\Settings\Systems;

use App\Contracts\Backup\BackupServiceInterface;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Models\Plugin;
use App\Models\Theme;
use App\Services\Extension\ExtensionSourceManager;
use App\Services\Update\SystemUpdateFlash;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\Process\PhpExecutableFinder;

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

    /**
     * After this many seconds with no mtime update on the in-progress
     * flag file, assume the dls:core:update subprocess crashed without
     * cleaning up. The placeholder page is dismissed and the admin UI
     * returns to the normal index. 15 minutes is long enough to cover
     * the slowest observed update (npm install + vite build over a slow
     * link) but short enough that a true crash does not leave the UI
     * unusable.
     */
    protected const IN_PROGRESS_STALE_THRESHOLD_SECONDS = 900; // 15 minutes

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
        // Short-circuit while a web-triggered core update is in flight:
        // the live tree under resources/ may be mid-replacement and the
        // normal index view cannot be rendered safely. Serve a minimal
        // hardcoded HTML placeholder with an auto-refresh until the
        // subprocess clears the flag.
        if (($inProgress = $this->readCoreUpdateInProgressFlag()) !== null) {
            return $this->coreUpdateInProgressResponse($inProgress);
        }

        // Same treatment while a web-triggered plugin/theme update runs
        // in its detached subprocess: poll until ExtensionsUpdate clears
        // the flag.
        if (($extInProgress = $this->readExtensionUpdateInProgressFlag()) !== null) {
            return $this->extensionUpdateInProgressResponse($extInProgress);
        }

        // A web-triggered update just finished: its detached subprocess
        // recorded the outcome once the in-progress flag cleared. Surface a
        // one-shot "update complete" flash on this render, then it is
        // consumed and gone.
        if (($updateResult = SystemUpdateFlash::consume()) !== null) {
            session()->now('success', $this->buildUpdateCompleteFlash($updateResult));
        }

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
            ->get(['id', 'slug', 'name', 'version', 'available_version', 'directory', 'update_failed_at', 'update_failure_reason', 'release_url', 'release_notes'])
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
                'releaseUrl' => $p->release_url,
                'releaseNotes' => $p->release_notes,
            ])
            ->values()
            ->all();

        $themes = Theme::query()
            ->whereNotNull('available_version')
            ->orderBy('slug')
            ->get(['id', 'slug', 'name', 'version', 'available_version', 'directory', 'update_failed_at', 'update_failure_reason', 'release_url', 'release_notes'])
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
                'releaseUrl' => $t->release_url,
                'releaseNotes' => $t->release_notes,
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
        // Drives the visibility of the "ターミナルから実行する場合" CLI
        // alternative block in the core section. Operators on the
        // simple-mode admin do not need the docker exec command — they
        // either use the in-page button or they are not the audience
        // for raw artisan output to begin with.
        $this->viewParams['isSimpleMode'] = \App\Helpers\AdminModeHelper::isSimpleMode();

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
     * Core update is excluded from this handler: web-triggered core
     * upgrades go through applyCore(), which spawns dls:core:update as a
     * detached subprocess so file replacement does not tear down the
     * PHP-FPM worker that initiated the request.
     */
    public function apply(Request $request, BackupServiceInterface $backupService)
    {
        $request->validate([
            'plugins' => 'array',
            'plugins.*' => 'integer',
            'themes' => 'array',
            'themes.*' => 'integer',
            'backup_first' => 'nullable|in:0,1',
        ]);

        $pluginIds = $request->input('plugins', []);
        $themeIds = $request->input('themes', []);

        if (empty($pluginIds) && empty($themeIds)) {
            return redirect()->route('admin.settings.systems.updates.index')
                ->with('info', __('admin/settings/systems/updates.messages.no_selection'));
        }

        // If the operator ticked "先にバックアップを取る" on the confirm
        // modal, snapshot the DB plus the source tree(s) about to be
        // overwritten before any extract step runs. Abort the whole
        // apply if the backup fails — the operator asked for safety,
        // running the update without the requested backup defeats that.
        if ($request->input('backup_first') === '1') {
            $targets = [BackupServiceInterface::TARGET_DATABASE];
            if (! empty($pluginIds)) {
                $targets[] = BackupServiceInterface::TARGET_PLUGINS_ALL;
            }
            if (! empty($themeIds)) {
                $targets[] = BackupServiceInterface::TARGET_THEMES_ALL;
            }

            // Auto-fill the backup note with the extensions being
            // updated so the record is self-describing in the list.
            $names = Plugin::query()->whereIn('id', $pluginIds)->pluck('name')
                ->merge(Theme::query()->whereIn('id', $themeIds)->pluck('name'))
                ->all();

            $backupResult = $backupService->backup($targets, [
                'note' => __('admin/settings/systems/backup/index.auto_note.pre_extension_update', [
                    'names' => implode(', ', $names),
                ]),
            ]);
            if (! $backupResult->success) {
                return redirect()->route('admin.settings.systems.updates.index')
                    ->with('error', __('admin/settings/systems/updates.messages.backup_failed', [
                        'error' => $backupResult->error ?? 'unknown error',
                    ]));
            }
        }

        // Resolve the slugs that actually have an update to apply.
        $pluginSlugs = Plugin::query()->whereIn('id', $pluginIds)->get()
            ->filter(fn (Plugin $p) => $p->hasUpdateAvailable())->pluck('slug')->all();
        $themeSlugs = Theme::query()->whereIn('id', $themeIds)->get()
            ->filter(fn (Theme $t) => $t->hasUpdateAvailable())->pluck('slug')->all();

        if (empty($pluginSlugs) && empty($themeSlugs)) {
            return redirect()->route('admin.settings.systems.updates.index')
                ->with('info', __('admin/settings/systems/updates.messages.no_selection'));
        }

        // A theme update runs an npm front-end build, which can take
        // minutes — far longer than the web server timeout. Run the
        // batch as a detached subprocess (like dls:core:update) so the
        // request returns immediately and the updates page polls for
        // completion, instead of blocking until a 504.
        $phpBinary = (new PhpExecutableFinder())->find(false);
        if (! $phpBinary) {
            return redirect()->route('admin.settings.systems.updates.index')
                ->with('error', __('admin/settings/systems/updates.core.php_cli_not_found'));
        }

        // Raise the in-progress flag before spawning so the very next
        // request lands on the polling placeholder. ExtensionsUpdate
        // clears it in its finally block, on success or failure.
        $this->writeExtensionUpdateInProgressFlag([
            'started_at' => now()->timestamp,
            'plugins' => $pluginSlugs,
            'themes' => $themeSlugs,
        ]);

        $args = '';
        foreach ($pluginSlugs as $slug) {
            $args .= ' --plugin='.escapeshellarg($slug);
        }
        foreach ($themeSlugs as $slug) {
            $args .= ' --theme='.escapeshellarg($slug);
        }

        $command = sprintf(
            'nohup %s %s dls:extensions:update%s --no-interaction > %s 2>&1 &',
            escapeshellarg($phpBinary),
            escapeshellarg(base_path('artisan')),
            $args,
            escapeshellarg(storage_path('logs/extension-update.log'))
        );
        exec($command);

        return redirect()->route('admin.settings.systems.updates.index')
            ->with('success', __('admin/settings/systems/updates.messages.update_started'));
    }

    /**
     * Trigger a core upgrade from the admin UI.
     *
     * Core update replaces files under app/, config/, routes/, lang/,
     * etc. on disk. Running dls:core:update inline in the request would
     * tear down the PHP-FPM worker (and the response it is trying to
     * write) the moment the live tree is overwritten. We instead spawn
     * the command as a detached subprocess so the request returns
     * immediately and the long-running update continues in the
     * background, reparented to PID 1 by SIGHUP detachment.
     *
     * The CLI alternative remains visible in the UI (collapsed under
     * "Or run from a terminal") so operators on hosts where PHP exec()
     * is disabled, or who prefer manual control, still have a path.
     */
    public function applyCore(Request $request, BackupServiceInterface $backupService)
    {
        $request->validate([
            'backup_first' => 'nullable|in:0,1',
        ]);

        $state = \App\Models\CoreRelease::singleton();
        $current = (string) (\App\Models\CoreVersionHistory::currentVersion() ?? config('app.version', '0.0.0'));
        $hasUpdate = $state->available_version !== null
            && version_compare($state->available_version, $current, '>');

        if (! $hasUpdate) {
            return redirect()->route('admin.settings.systems.updates.index')
                ->with('warning', __('admin/settings/systems/updates.core.no_update_to_apply'));
        }

        if (! function_exists('exec')) {
            return redirect()->route('admin.settings.systems.updates.index')
                ->with('error', __('admin/settings/systems/updates.core.exec_disabled'));
        }

        // Pre-update backup (opt-in via the confirm modal). A core
        // update rewrites the source tree AND runs migrations, so the
        // only way to return to the pre-update state is to restore
        // both halves together — restoring source over a migrated
        // schema (or vice versa) leaves the install inconsistent.
        // Capture TARGET_CORE_SOURCE + TARGET_DATABASE + TARGET_THEMES_ALL
        // as one record so a single restore rolls the whole update
        // back, matching the plugin/theme apply path. Themes are
        // included unconditionally (rather than only when the release
        // ZIP declares a bundled theme in .dixlase-release.json)
        // because the availability signal is inside the release ZIP
        // that has not been downloaded yet at this point — including
        // themes is cheap (theme dirs are small next to core+DB) and
        // guarantees that a rollback after a bundled-theme update
        // restores the pre-update theme code even if the operator
        // upgraded from a check that predated the bundling change.
        // (CoreUpdater::update() also takes its own DB-only snapshot
        // inside the detached subprocess as a last-resort guard right
        // before `migrate`; that one is best-effort and separate from
        // this operator-requested, source-inclusive backup.)
        // Id of the source-inclusive pre-update backup, when the operator
        // opted in. Passed to the detached updater so it reuses this single
        // record as the DB restore point instead of taking a second,
        // DB-only snapshot (which surfaced as a confusing duplicate entry).
        $preBackupId = null;
        if ($request->input('backup_first') === '1') {
            $backupResult = $backupService->backup(
                [
                    BackupServiceInterface::TARGET_CORE_SOURCE,
                    BackupServiceInterface::TARGET_DATABASE,
                    BackupServiceInterface::TARGET_THEMES_ALL,
                ],
                ['note' => __('admin/settings/systems/backup/index.auto_note.pre_core_update', [
                    'current' => $current,
                    'available' => $state->available_version,
                ])],
            );
            if (! $backupResult->success) {
                return redirect()->route('admin.settings.systems.updates.index')
                    ->with('error', __('admin/settings/systems/updates.messages.backup_failed', [
                        'error' => $backupResult->error ?? 'unknown error',
                    ]));
            }
            $preBackupId = $backupResult->backupRecordId;
        }

        // Under PHP-FPM the PHP_BINARY constant points at the FPM binary
        // (e.g. /usr/local/sbin/php-fpm), not the CLI php. Spawning the
        // artisan command with FPM as the interpreter just prints its
        // usage banner and exits, leaving the update silently un-run.
        // PhpExecutableFinder checks PHP_SAPI and walks the standard
        // PATH fallbacks, so it returns the CLI php even from an FPM
        // request.
        $phpBinary = (new PhpExecutableFinder())->find(false);
        if (! $phpBinary) {
            return redirect()->route('admin.settings.systems.updates.index')
                ->with('error', __('admin/settings/systems/updates.core.php_cli_not_found'));
        }

        // Clear any prior failure marker so the UI does not show a stale
        // error banner while the new run is in flight.
        $state->forceFill([
            'update_failed_at' => null,
            'update_failure_reason' => null,
        ])->save();

        // Attribute the version history row to the admin who clicked
        // the button. CLI invocations of dls:core:update without
        // --applied-by leave the column null, as before.
        $appliedById = \App\Helpers\AdminHelper::getMember()?->id;
        $appliedByArg = $appliedById !== null
            ? ' --applied-by='.escapeshellarg((string) $appliedById)
            : '';

        // Hand the pre-update backup id to the updater so it reuses that one
        // record instead of taking its own DB-only snapshot.
        $dbBackupArg = $preBackupId !== null
            ? ' --db-backup-id='.escapeshellarg((string) $preBackupId)
            : '';

        // Raise the in-progress flag *before* spawning. The next admin
        // request lands on the placeholder instead of trying to render
        // the index view while resources/ is being replaced. The
        // subprocess clears the flag in CoreUpdater::update()'s finally
        // block, regardless of success or failure.
        $this->writeCoreUpdateInProgressFlag([
            'started_at' => now()->timestamp,
            'target_version' => $state->available_version,
            'started_by_id' => $appliedById,
        ]);

        $command = sprintf(
            'nohup %s %s dls:core:update --force --no-interaction%s%s > %s 2>&1 &',
            escapeshellarg($phpBinary),
            escapeshellarg(base_path('artisan')),
            $appliedByArg,
            $dbBackupArg,
            escapeshellarg(storage_path('logs/core-update.log'))
        );
        exec($command);

        return redirect()->route('admin.settings.systems.updates.index')
            ->with('success', __('admin/settings/systems/updates.core.update_started', [
                'version' => $state->available_version,
            ]));
    }

    /**
     * Roll the core back to the state captured before its last update, from
     * the admin UI. Counterpart to applyCore(): a rollback also replaces the
     * live source tree under app/, resources/, etc., so running it inline
     * would tear down the PHP-FPM worker mid-response. We spawn
     * dls:core:rollback as a detached subprocess and land the next request on
     * the in-progress placeholder, exactly like applyCore(). Only reachable
     * when a rollback point exists (the button is hidden otherwise).
     */
    public function rollbackCore(Request $request, \App\Services\Core\CoreSourceSnapshot $snapshotter)
    {
        if ($snapshotter->latestSnapshotWithMetadata() === null) {
            return redirect()->route('admin.settings.systems.updates.index')
                ->with('warning', __('admin/settings/systems/updates.core.rollback.none_to_apply'));
        }

        if (! function_exists('exec')) {
            return redirect()->route('admin.settings.systems.updates.index')
                ->with('error', __('admin/settings/systems/updates.core.exec_disabled'));
        }

        $phpBinary = (new PhpExecutableFinder())->find(false);
        if (! $phpBinary) {
            return redirect()->route('admin.settings.systems.updates.index')
                ->with('error', __('admin/settings/systems/updates.core.php_cli_not_found'));
        }

        // Clear any stale update-failure marker so a prior failed update's
        // banner does not linger while the rollback runs.
        \App\Models\CoreRelease::singleton()->forceFill([
            'update_failed_at' => null,
            'update_failure_reason' => null,
        ])->save();

        // Attribute the rollback history row to the admin who clicked.
        $appliedById = \App\Helpers\AdminHelper::getMember()?->id;
        $appliedByArg = $appliedById !== null
            ? ' --applied-by='.escapeshellarg((string) $appliedById)
            : '';

        // Raise the in-progress flag before spawning so the next request
        // lands on the polling placeholder (the rollback replaces resources/
        // mid-flight). dls:core:rollback clears it in its finally block.
        $this->writeCoreUpdateInProgressFlag([
            'started_at' => now()->timestamp,
            'operation' => 'rollback',
            'started_by_id' => $appliedById,
        ]);

        $command = sprintf(
            'nohup %s %s dls:core:rollback --force --no-interaction%s > %s 2>&1 &',
            escapeshellarg($phpBinary),
            escapeshellarg(base_path('artisan')),
            $appliedByArg,
            escapeshellarg(storage_path('logs/core-update.log'))
        );
        exec($command);

        return redirect()->route('admin.settings.systems.updates.index')
            ->with('success', __('admin/settings/systems/updates.core.rollback.started'));
    }

    /**
     * If a web-triggered core update is in progress, return its metadata
     * (`started_at`, `target_version`, `started_by_id`) for the
     * placeholder page. Returns null when no flag is present, or when
     * the flag is older than IN_PROGRESS_STALE_THRESHOLD_SECONDS — in
     * which case the subprocess almost certainly crashed without
     * cleaning up, and we delete the stale flag so the admin UI does
     * not stay stuck on the placeholder forever.
     *
     * @return array{started_at: int, target_version: ?string, started_by_id: ?int}|null
     */
    protected function readCoreUpdateInProgressFlag(): ?array
    {
        $path = \App\Services\Core\CoreUpdater::inProgressFlagPath();
        if (! is_file($path)) {
            return null;
        }

        if (time() - filemtime($path) > self::IN_PROGRESS_STALE_THRESHOLD_SECONDS) {
            @unlink($path);

            return null;
        }

        $payload = json_decode((string) @file_get_contents($path), true);

        return is_array($payload) ? $payload : null;
    }

    protected function writeCoreUpdateInProgressFlag(array $info): void
    {
        $path = \App\Services\Core\CoreUpdater::inProgressFlagPath();
        @mkdir(dirname($path), 0775, true);
        @file_put_contents($path, json_encode($info, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Counterpart of readCoreUpdateInProgressFlag() for the detached
     * plugin/theme batch update. Same stale-flag self-healing.
     *
     * @return array{started_at: int, plugins?: string[], themes?: string[]}|null
     */
    protected function readExtensionUpdateInProgressFlag(): ?array
    {
        $path = \App\Console\Commands\ExtensionsUpdate::inProgressFlagPath();
        if (! is_file($path)) {
            return null;
        }

        if (time() - filemtime($path) > self::IN_PROGRESS_STALE_THRESHOLD_SECONDS) {
            @unlink($path);

            return null;
        }

        $payload = json_decode((string) @file_get_contents($path), true);

        return is_array($payload) ? $payload : null;
    }

    protected function writeExtensionUpdateInProgressFlag(array $info): void
    {
        $path = \App\Console\Commands\ExtensionsUpdate::inProgressFlagPath();
        @mkdir(dirname($path), 0775, true);
        @file_put_contents($path, json_encode($info, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Render a minimal hardcoded HTML placeholder while a core update is
     * running. We deliberately do not go through Blade or the admin
     * layout here: the live tree under resources/views/ may be in the
     * middle of being replaced by the update process, and a view-not-
     * found exception during the swap window would surface a 500 to
     * the operator. A self-contained response sidesteps that entirely.
     */
    protected function coreUpdateInProgressResponse(array $info): \Illuminate\Http\Response
    {
        $startedAt = (int) ($info['started_at'] ?? time());
        $targetVersion = (string) ($info['target_version'] ?? '');
        $elapsedSec = max(0, time() - $startedAt);
        $elapsedMin = (int) floor($elapsedSec / 60);
        $elapsedRem = $elapsedSec % 60;

        $title = e(__('admin/settings/systems/updates.core.in_progress_title'));
        $message = e(__('admin/settings/systems/updates.core.in_progress_message', [
            'version' => $targetVersion !== '' ? $targetVersion : '—',
        ]));
        $elapsedLabel = e(__('admin/settings/systems/updates.core.in_progress_elapsed', [
            'min' => $elapsedMin,
            'sec' => $elapsedRem,
        ]));
        $refreshNote = e(__('admin/settings/systems/updates.core.in_progress_refresh_note'));

        return $this->inProgressHtmlResponse($title, $message, $elapsedLabel, $refreshNote);
    }

    /**
     * Polling placeholder for a detached plugin/theme batch update.
     * Mirrors the core placeholder; the admin index is safe to render
     * during an extension update, but a self-contained auto-refreshing
     * page keeps the two flows consistent and avoids a half-loaded list.
     */
    protected function extensionUpdateInProgressResponse(array $info): \Illuminate\Http\Response
    {
        $startedAt = (int) ($info['started_at'] ?? time());
        $elapsedSec = max(0, time() - $startedAt);
        $elapsedMin = (int) floor($elapsedSec / 60);
        $elapsedRem = $elapsedSec % 60;

        $count = count($info['plugins'] ?? []) + count($info['themes'] ?? []);

        $title = e(__('admin/settings/systems/updates.extension_in_progress.title'));
        $message = e(__('admin/settings/systems/updates.extension_in_progress.message', [
            'count' => $count,
        ]));
        $elapsedLabel = e(__('admin/settings/systems/updates.core.in_progress_elapsed', [
            'min' => $elapsedMin,
            'sec' => $elapsedRem,
        ]));
        $refreshNote = e(__('admin/settings/systems/updates.core.in_progress_refresh_note'));

        return $this->inProgressHtmlResponse($title, $message, $elapsedLabel, $refreshNote);
    }

    /**
     * Self-contained auto-refreshing placeholder page shared by the core
     * and extension update flows. Deliberately not rendered through Blade
     * or the admin layout: during a core update the live tree under
     * resources/views/ may be mid-replacement, and a view-not-found
     * exception in that window would surface a 500 to the operator.
     */
    protected function inProgressHtmlResponse(string $title, string $message, string $elapsedLabel, string $refreshNote): \Illuminate\Http\Response
    {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="refresh" content="10">
<title>{$title}</title>
<style>
*{box-sizing:border-box}
html,body{margin:0;padding:0;height:100%}
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background:#0f172a;color:#f1f5f9;display:flex;align-items:center;justify-content:center;padding:1.5rem}
.card{max-width:480px;width:100%;padding:2rem;background:#1e293b;border:1px solid #334155;border-radius:0.75rem;text-align:center;box-shadow:0 10px 25px rgba(0,0,0,0.3)}
.spinner{display:inline-block;width:40px;height:40px;border:3px solid #475569;border-top-color:#60a5fa;border-radius:50%;animation:spin 1s linear infinite;margin-bottom:1.25rem}
@keyframes spin{to{transform:rotate(360deg)}}
h1{font-size:1.125rem;font-weight:600;margin:0 0 0.75rem;color:#f8fafc}
p{margin:0.5rem 0;color:#cbd5e1;font-size:0.875rem;line-height:1.5}
.elapsed{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:0.8125rem;color:#94a3b8;margin-top:1rem}
.refresh{font-size:0.75rem;color:#64748b;margin-top:1.5rem}
</style>
</head>
<body>
<div class="card" role="status" aria-live="polite">
<div class="spinner" aria-hidden="true"></div>
<h1>{$title}</h1>
<p>{$message}</p>
<p class="elapsed">{$elapsedLabel}</p>
<p class="refresh">{$refreshNote}</p>
</div>
</body>
</html>
HTML;

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
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
     * @return array{available: bool, current_version: ?string, available_version: ?string, available_version_published_at: ?\Illuminate\Support\Carbon, release_url: ?string, release_notes: ?string, preselected: bool}
     */
    /**
     * Build the "update complete" flash text from a consumed
     * {@see SystemUpdateFlash} result (core or extension).
     *
     * @param  array<string, mixed>  $result
     */
    protected function buildUpdateCompleteFlash(array $result): string
    {
        if (($result['kind'] ?? '') === 'core') {
            return __('admin/settings/systems/updates.messages.core_update_complete', [
                'from' => (string) ($result['from'] ?? ''),
                'to' => (string) ($result['to'] ?? ''),
            ]);
        }

        $plugins = is_array($result['updated_plugins'] ?? null) ? $result['updated_plugins'] : [];
        $themes = is_array($result['updated_themes'] ?? null) ? $result['updated_themes'] : [];

        // e.g. ja: プラグイン「A」「B」・テーマ「X」 / en: plugins A, B and themes X
        $segments = [];
        if ($plugins !== []) {
            $segments[] = __('admin/settings/systems/updates.messages.update_complete_plugins', [
                'names' => $this->quoteExtensionNames($plugins),
            ]);
        }
        if ($themes !== []) {
            $segments[] = __('admin/settings/systems/updates.messages.update_complete_themes', [
                'names' => $this->quoteExtensionNames($themes),
            ]);
        }

        return __('admin/settings/systems/updates.messages.update_complete_frame', [
            'subject' => implode(__('admin/settings/systems/updates.messages.update_complete_join'), $segments),
        ]);
    }

    /**
     * Join extension display names for the completion flash: each name wrapped
     * in 「」 with no separator for Japanese, comma-separated otherwise.
     *
     * @param  list<string>  $names
     */
    protected function quoteExtensionNames(array $names): string
    {
        if (app()->getLocale() === 'ja') {
            return implode('', array_map(static fn (string $n): string => '「'.$n.'」', $names));
        }

        return implode(', ', $names);
    }

    protected function buildCoreSection(array $target): array
    {
        $state = \App\Models\CoreRelease::singleton();
        $current = (string) (\App\Models\CoreVersionHistory::currentVersion() ?? config('app.version', '0.0.0'));
        $available = $state->available_version !== null
            && version_compare($state->available_version, $current, '>');

        // A core rollback point exists when the last update left a source
        // snapshot with rollback metadata. Its 'from' version is what a
        // rollback would restore to; 'to' is the update it undoes.
        $snapshotter = app(\App\Services\Core\CoreSourceSnapshot::class);
        $rollbackPoint = $snapshotter->latestSnapshotWithMetadata();
        $rollbackMeta = $rollbackPoint !== null ? $snapshotter->readMetadata($rollbackPoint) : null;

        return [
            'available' => $available,
            'current_version' => $current,
            'available_version' => $available ? $state->available_version : null,
            'available_version_published_at' => $available ? $state->available_version_published_at : null,
            'release_url' => $available ? $state->release_url : null,
            'release_notes' => $available ? $state->release_notes : null,
            'preselected' => $available && $target['type'] === 'core',
            'update_failed_at' => $state->update_failed_at,
            'update_failed_at_formatted' => $state->update_failed_at?->format('Y/m/d H:i'),
            'update_failure_reason' => $state->update_failure_reason,
            'can_rollback' => $rollbackPoint !== null,
            'rollback_to_version' => is_array($rollbackMeta) ? ($rollbackMeta['from'] ?? null) : null,
            'rollback_from_version' => is_array($rollbackMeta) ? ($rollbackMeta['to'] ?? null) : null,
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
