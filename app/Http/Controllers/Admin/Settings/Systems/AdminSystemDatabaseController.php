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

use App\Http\Controllers\Admin\AdminLoggedInController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use App\Http\Requests\Admin\Settings\Systems\AdminSystemDatabaseCleanupRequest;
use App\Services\DatabaseCleanupService;

class AdminSystemDatabaseController extends AdminLoggedInController
{
    protected DatabaseCleanupService $cleanupService;

    public function __construct(DatabaseCleanupService $cleanupService)
    {
        parent::__construct();
        $this->cleanupService = $cleanupService;
    }

    /**
     * データベース管理画面
     */
    public function index()
    {
        $cleanupInfo = $this->cleanupService->getCleanupInfo();
        $pluginCleanupInfo = $this->cleanupService->getPluginCleanupInfo();

        $this->viewParams['cleanupInfo'] = $cleanupInfo;
        $this->viewParams['pluginCleanupInfo'] = $pluginCleanupInfo;
        
        return view('admin::settings.systems.database', $this->viewParams);
    }

    /**
     * 個別データベースクリーンアップ
     */
    public function cleanup(AdminSystemDatabaseCleanupRequest $request)
    {
        $validated = $request->validated();
        $type = $validated['type'];
        $days = (int) $validated['days'];

        try {
            if ($type === 'all') {
                $allDays = (int) $request->input('all_days', 30);
                $result = $this->cleanupService->cleanupAll($allDays, true);
            } else {
                $result = $this->cleanupService->cleanup($type, $days, true);
            }

            if ($result['success']) {
                $message = __('admin/settings/systems/database.cleanup_success', ['count' => $result['count']]);
                return redirect()->route('admin.settings.systems.database')->with('success', $message);
            } else {
                $message = __('admin/settings/systems/database.cleanup_error', ['error' => $result['message']]);
                return redirect()->route('admin.settings.systems.database')->with('error', $message);
            }
        } catch (\Exception $e) {
            $message = __('admin/settings/systems/database.cleanup_error', ['error' => $e->getMessage()]);
            return redirect()->route('admin.settings.systems.database')->with('error', $message);
        }
    }

}
