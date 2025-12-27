<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
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

namespace App\Http\Controllers\Admin\Settings\Base;

use App\Helpers\ConfigHelper;
use App\Helpers\EnvHelper;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Contracts\Repositories\BaseSettingRepositoryInterface;
use Illuminate\Http\Request;

class AdminBaseMaintenanceController extends AdminLoggedInController
{
    protected BaseSettingRepositoryInterface $baseSettingRepository;

    public function __construct(BaseSettingRepositoryInterface $baseSettingRepository)
    {
        parent::__construct();
        $this->baseSettingRepository = $baseSettingRepository;
    }

    /**
     * メンテナンス設定ページ
     */
    public function index()
    {
        $this->addBreadcrumb(null, __('admin/nav.settings.text'));
        $this->addBreadcrumb('admin.settings.base.index', __('admin/nav.settings.base.text'));
        $this->addBreadcrumb(null, __('admin/nav.settings.base.maintenance'));
        $this->setDescription(__('admin/settings/base/maintenance.description'));
        $this->setBreadcrumbs();
        
        $settings = [
            'maintenance_mode' => ConfigHelper::getMaintenanceMode(),
            'maintenance_message' => ConfigHelper::getMaintenanceMessage(),
        ];

        $this->viewParams['settings'] = $settings;

        return view('admin.settings.base.maintenance', $this->viewParams);
    }

    /**
     * メンテナンス設定の更新
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'maintenance_mode' => 'nullable|boolean',
            'maintenance_message' => 'nullable|string|max:2000',
        ]);

        $maintenanceMode = (int) ($validated['maintenance_mode'] ?? 0);

        // .envに保存
        $envData = [
            'maintenance_mode' => $maintenanceMode ? 'true' : 'false',
        ];

        EnvHelper::update($envData);

        // DBに保存
        $dbSettings = [
            'maintenance_mode' => $maintenanceMode ? '1' : '0',
            'maintenance_message' => $validated['maintenance_message'] ?? '',
        ];

        $this->baseSettingRepository->setMultiple($dbSettings);

        return redirect()->route('admin.settings.base.maintenance')
            ->with('success', __('admin/settings/base/maintenance.settings_updated'));
    }
}
