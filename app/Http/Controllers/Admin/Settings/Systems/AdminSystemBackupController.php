<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
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
use App\Contracts\Backup\RestoreServiceInterface;
use App\Helpers\AdminModeHelper;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Http\Requests\Admin\Settings\Systems\AdminSystemBackupCreateRequest;
use App\Http\Requests\Admin\Settings\Systems\AdminSystemBackupSettingsRequest;
use App\Models\BackupRecord;
use App\Models\RestoreRecord;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Backup management controller
 */
class AdminSystemBackupController extends AdminLoggedInController
{
    /**
     * Settings key: default backup targets (JSON array)
     */
    private const SETTING_DEFAULT_TARGETS = 'backup.default_targets';

    /**
     * Settings key: default retention days
     */
    private const SETTING_DEFAULT_RETENTION_DAYS = 'backup.default_retention_days';

    /**
     * Settings keys: source-tree exclusion flags ('1'/'0', default excluded)
     */
    private const SETTING_EXCLUDE_NODE_MODULES = 'backup.exclude_node_modules';

    private const SETTING_EXCLUDE_VENDOR = 'backup.exclude_vendor';

    public function __construct(
        protected BackupServiceInterface $backupService,
        protected RestoreServiceInterface $restoreService,
    ) {
        parent::__construct();
    }

    /**
     * Backup list/creation screen
     */
    public function index()
    {
        $records = BackupRecord::query()
            ->whereNotIn('status', [BackupRecord::STATUS_DELETED])
            ->orderByDesc('created_at')
            ->get();

        $this->viewParams['records'] = $records;
        $this->viewParams['availableTargets'] = $this->backupService->getAvailableTargets();
        $this->viewParams['defaultTargets'] = $this->resolveDefaultTargets();
        $this->viewParams['defaultRetentionDays'] = $this->resolveDefaultRetentionDays();
        $this->viewParams['modeData'] = AdminModeHelper::getViewModeData('settings.systems.backup');

        return view('admin::settings.systems.backup.index', $this->viewParams);
    }

    /**
     * Backup detail screen (metadata + editable note)
     */
    public function show(BackupRecord $backup)
    {
        $this->viewParams['record'] = $backup;
        $this->viewParams['fileExists'] = $backup->file_path && file_exists($backup->file_path);
        $this->viewParams['modeData'] = AdminModeHelper::getViewModeData('settings.systems.backup');

        return view('admin::settings.systems.backup.show', $this->viewParams);
    }

    /**
     * Update the free-form note on a backup record
     */
    public function updateNote(Request $request, BackupRecord $backup): RedirectResponse
    {
        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $backup->update(['note' => $validated['note'] ?? null]);

        return redirect()
            ->route('admin.settings.systems.backup.show', $backup)
            ->with('success', __('admin/settings/systems/backup/index.flash.note_updated'));
    }

    /**
     * Restore history screen
     */
    public function restores()
    {
        $records = RestoreRecord::query()
            ->with(['backupRecord', 'preRestoreBackup', 'restoredBy'])
            ->orderByDesc('restored_at')
            ->get();

        $this->viewParams['records'] = $records;
        $this->viewParams['modeData'] = AdminModeHelper::getViewModeData('settings.systems.backup.restores');

        return view('admin::settings.systems.backup.restores', $this->viewParams);
    }

    /**
     * Backup settings screen
     */
    public function settings()
    {
        $this->viewParams['availableTargets'] = $this->backupService->getAvailableTargets();
        $this->viewParams['defaultTargets'] = $this->resolveDefaultTargets();
        $this->viewParams['defaultRetentionDays'] = $this->resolveDefaultRetentionDays();
        $this->viewParams['excludeNodeModules'] = $this->resolveExclusionFlag(self::SETTING_EXCLUDE_NODE_MODULES);
        $this->viewParams['excludeVendor'] = $this->resolveExclusionFlag(self::SETTING_EXCLUDE_VENDOR);
        $this->viewParams['modeData'] = AdminModeHelper::getViewModeData('settings.systems.backup.settings');

        return view('admin::settings.systems.backup.settings', $this->viewParams);
    }

    /**
     * Save backup settings
     */
    public function updateSettings(AdminSystemBackupSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        SiteSetting::setValue(self::SETTING_DEFAULT_TARGETS, json_encode(array_values($validated['default_targets'])));

        $retentionDays = $validated['default_retention_days'] ?? null;
        SiteSetting::setValue(
            self::SETTING_DEFAULT_RETENTION_DAYS,
            $retentionDays === null ? '' : (string) $retentionDays,
        );

        // Unchecked toggles are absent from the request entirely
        SiteSetting::setValue(self::SETTING_EXCLUDE_NODE_MODULES, $request->boolean('exclude_node_modules') ? '1' : '0');
        SiteSetting::setValue(self::SETTING_EXCLUDE_VENDOR, $request->boolean('exclude_vendor') ? '1' : '0');

        return redirect()
            ->route('admin.settings.systems.backup.settings')
            ->with('success', __('admin/settings/systems/backup/settings.flash.update_success'));
    }

    /**
     * Get a source-tree exclusion flag (excluded by default when unset)
     */
    private function resolveExclusionFlag(string $settingKey): bool
    {
        $stored = SiteSetting::getValue($settingKey);

        return ! is_string($stored) || $stored === '' || $stored === '1';
    }

    /**
     * Get saved default targets (API default if not set)
     *
     * @return string[]
     */
    private function resolveDefaultTargets(): array
    {
        $stored = SiteSetting::getValue(self::SETTING_DEFAULT_TARGETS);
        if (! is_string($stored) || $stored === '') {
            return $this->backupService->getDefaultTargets();
        }

        $decoded = json_decode($stored, true);
        if (! is_array($decoded) || empty($decoded)) {
            return $this->backupService->getDefaultTargets();
        }

        // Filter to only available targets
        return array_values(array_intersect($decoded, $this->backupService->getAvailableTargets()));
    }

    /**
     * Get saved default retention days
     */
    private function resolveDefaultRetentionDays(): ?int
    {
        $stored = SiteSetting::getValue(self::SETTING_DEFAULT_RETENTION_DAYS);
        if (! is_string($stored) || $stored === '') {
            return null;
        }

        $value = (int) $stored;

        return $value > 0 ? $value : null;
    }

    /**
     * Execute backup
     */
    public function create(AdminSystemBackupCreateRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $options = [];
        if (! empty($validated['retention_days'])) {
            $options['retention_days'] = (int) $validated['retention_days'];
        }

        $result = $this->backupService->backup($validated['targets'], $options);

        if (! $result->success) {
            return redirect()
                ->route('admin.settings.systems.backup.index')
                ->with('error', __('admin/settings/systems/backup/index.flash.create_failed', ['error' => $result->error]));
        }

        return redirect()
            ->route('admin.settings.systems.backup.index')
            ->with('success', __('admin/settings/systems/backup/index.flash.create_success', [
                'size' => $this->formatBytes($result->fileSize ?? 0),
                'duration' => round($result->duration ?? 0, 2),
            ]));
    }

    /**
     * Delete backup
     */
    public function destroy(BackupRecord $backup): RedirectResponse
    {
        $deleted = $this->backupService->delete($backup);

        if (! $deleted) {
            return redirect()
                ->route('admin.settings.systems.backup.index')
                ->with('error', __('admin/settings/systems/backup/index.flash.delete_failed'));
        }

        return redirect()
            ->route('admin.settings.systems.backup.index')
            ->with('success', __('admin/settings/systems/backup/index.flash.delete_success'));
    }

    /**
     * Restore from backup
     */
    public function restore(BackupRecord $backup): RedirectResponse
    {
        if ($backup->status !== BackupRecord::STATUS_COMPLETED) {
            return redirect()
                ->route('admin.settings.systems.backup.index')
                ->with('error', __('admin/settings/systems/backup/index.flash.restore_unavailable'));
        }

        $result = $this->restoreService->restore($backup);

        if (! $result->success) {
            return redirect()
                ->route('admin.settings.systems.backup.index')
                ->with('error', __('admin/settings/systems/backup/index.flash.restore_failed', ['error' => $result->error]));
        }

        return redirect()
            ->route('admin.settings.systems.backup.restores')
            ->with('success', __('admin/settings/systems/backup/index.flash.restore_success', [
                'duration' => round($result->duration ?? 0, 2),
            ]));
    }

    /**
     * Rollback restore
     */
    public function rollback(RestoreRecord $restore): RedirectResponse
    {
        if (! $restore->canRollback()) {
            return redirect()
                ->route('admin.settings.systems.backup.restores')
                ->with('error', __('admin/settings/systems/backup/restores.flash.rollback_unavailable'));
        }

        $result = $this->restoreService->rollback($restore);

        if (! $result->success) {
            return redirect()
                ->route('admin.settings.systems.backup.restores')
                ->with('error', __('admin/settings/systems/backup/restores.flash.rollback_failed', ['error' => $result->error]));
        }

        return redirect()
            ->route('admin.settings.systems.backup.restores')
            ->with('success', __('admin/settings/systems/backup/restores.flash.rollback_success', [
                'duration' => round($result->duration ?? 0, 2),
            ]));
    }

    /**
     * Download backup file
     */
    public function download(BackupRecord $backup): BinaryFileResponse|RedirectResponse
    {
        if ($backup->status === BackupRecord::STATUS_DELETED || ! $backup->file_path || ! file_exists($backup->file_path)) {
            return redirect()
                ->route('admin.settings.systems.backup.index')
                ->with('error', __('admin/settings/systems/backup/index.flash.download_failed'));
        }

        return response()->download($backup->file_path, $backup->file_name);
    }

    /**
     * Convert byte size to human-readable format
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log($bytes, 1024));
        $i = min($i, count($units) - 1);

        return round($bytes / pow(1024, $i), $i > 0 ? 2 : 0).' '.$units[$i];
    }
}
