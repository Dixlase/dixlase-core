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
use App\Models\BackupRecord;
use App\Models\RestoreRecord;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * バックアップ管理コントローラー
 */
class AdminSystemBackupController extends AdminLoggedInController
{
    public function __construct(
        protected BackupServiceInterface $backupService,
        protected RestoreServiceInterface $restoreService,
    ) {
        parent::__construct();
    }

    /**
     * バックアップ一覧/作成画面
     */
    public function index()
    {
        $records = BackupRecord::query()
            ->whereNotIn('status', [BackupRecord::STATUS_DELETED])
            ->orderByDesc('created_at')
            ->get();

        $this->viewParams['records'] = $records;
        $this->viewParams['availableTargets'] = $this->backupService->getAvailableTargets();
        $this->viewParams['defaultTargets'] = $this->backupService->getDefaultTargets();
        $this->viewParams['modeData'] = AdminModeHelper::getViewModeData('settings.systems.backup');

        return view('admin::settings.systems.backup.index', $this->viewParams);
    }

    /**
     * 復元履歴画面
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
     * バックアップ設定画面（D3 で実装）
     */
    public function settings()
    {
        $this->viewParams['modeData'] = AdminModeHelper::getViewModeData('settings.systems.backup.settings');

        return view('admin::settings.systems.backup.settings', $this->viewParams);
    }

    /**
     * バックアップ実行
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
     * バックアップ削除
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
     * バックアップから復元
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
     * 復元のロールバック
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
     * バックアップファイルのダウンロード
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
     * バイトサイズを人間可読形式に変換
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
