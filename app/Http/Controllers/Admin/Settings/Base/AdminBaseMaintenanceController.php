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

namespace App\Http\Controllers\Admin\Settings\Base;

use App\Contracts\Repositories\BaseSettingRepositoryInterface;
use App\Helpers\AdminModeHelper;
use App\Helpers\ConfigHelper;
use App\Helpers\EnvHelper;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Http\Requests\Admin\Settings\Base\AdminBaseMaintenanceUpdateRequest;

class AdminBaseMaintenanceController extends AdminLoggedInController
{
    protected const SETTING_KEYS = ['maintenance_mode', 'maintenance_message', 'maintenance_auto_release', 'maintenance_start_at', 'maintenance_release_at'];

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
        $settings = [
            'maintenance_mode' => ConfigHelper::getMaintenanceMode(),
            'maintenance_message' => ConfigHelper::getMaintenanceMessage(),
            'maintenance_auto_release' => $this->baseSettingRepository->get('maintenance_auto_release', '0'),
            'maintenance_start_at' => $this->baseSettingRepository->get('maintenance_start_at'),
            'maintenance_release_at' => $this->baseSettingRepository->get('maintenance_release_at'),
        ];

        $this->viewParams['settings'] = $settings;
        $this->viewParams['modeData'] = AdminModeHelper::getViewModeData('settings.base.maintenance');

        return view('admin.settings.base.maintenance', $this->viewParams);
    }

    /**
     * メンテナンス設定の更新
     */
    public function update(AdminBaseMaintenanceUpdateRequest $request)
    {
        $before = $this->baseSettingRepository->getMultiple(static::SETTING_KEYS);

        $validated = $request->validated();

        $maintenanceMode = (int) ($validated['maintenance_mode'] ?? 0);
        $autoRelease = (int) ($validated['maintenance_auto_release'] ?? 0);

        // .envに保存
        $envData = [
            'maintenance_mode' => $maintenanceMode ? 'true' : 'false',
        ];

        EnvHelper::update($envData);

        // DBに保存
        $dbSettings = [
            'maintenance_mode' => $maintenanceMode ? '1' : '0',
            'maintenance_message' => $validated['maintenance_message'] ?? '',
            'maintenance_auto_release' => $autoRelease ? '1' : '0',
            'maintenance_start_at' => $validated['maintenance_start_at'] ?? null,
            'maintenance_release_at' => $autoRelease ? ($validated['maintenance_release_at'] ?? null) : null,
        ];

        $this->baseSettingRepository->setMultiple($dbSettings);

        $after = $this->baseSettingRepository->getMultiple(static::SETTING_KEYS);
        \App\Facades\Audit::logBulkSettingsChange('base.maintenance', $before, $after, auth()->user());

        return redirect()->route('admin.settings.base.maintenance')
            ->with('success', __('admin/settings/base/maintenance.settings_updated'));
    }

    /**
     * メンテナンス画面のプレビュー
     */
    public function preview()
    {
        $message = request()->input('message', '現在メンテナンス中です。しばらくお待ちください。');
        $releaseAt = request()->input('release_at');

        return view('maintenance', [
            'message' => $message,
            'releaseAt' => $releaseAt,
            'isPreview' => true,
        ]);
    }
}
