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
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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

namespace App\Http\Controllers\Admin\Settings\Base;

use App\Contracts\Repositories\SiteSettingRepositoryInterface;
use App\Helpers\AdminModeHelper;
use App\Helpers\ConfigHelper;
use App\Helpers\EnvHelper;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Http\Requests\Admin\Settings\Base\AdminBaseMaintenanceUpdateRequest;

class AdminBaseMaintenanceController extends AdminLoggedInController
{
    protected const SETTING_KEYS = ['maintenance_mode', 'maintenance_message', 'maintenance_auto_release', 'maintenance_start_at', 'maintenance_release_at'];

    protected SiteSettingRepositoryInterface $baseSettingRepository;

    public function __construct(SiteSettingRepositoryInterface $baseSettingRepository)
    {
        parent::__construct();
        $this->baseSettingRepository = $baseSettingRepository;
    }

    /**
     * Maintenance settings page
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
     * Update maintenance settings
     */
    public function update(AdminBaseMaintenanceUpdateRequest $request)
    {
        $actor = new \App\Actors\MemberActor(\App\Helpers\AdminHelper::getMember());

        \App\Actions\Settings\UpdateSettingsAction::make(
            repository: $this->baseSettingRepository,
            settingsPage: 'base.maintenance',
            settingKeys: static::SETTING_KEYS,
            writeCallback: function ($repo, $data) {
                $maintenanceMode = (int) ($data['maintenance_mode'] ?? 0);
                $autoRelease = (int) ($data['maintenance_auto_release'] ?? 0);

                EnvHelper::update([
                    'maintenance_mode' => $maintenanceMode ? 'true' : 'false',
                ]);

                $repo->setMultiple([
                    'maintenance_mode' => $maintenanceMode ? '1' : '0',
                    'maintenance_message' => $data['maintenance_message'] ?? '',
                    'maintenance_auto_release' => $autoRelease ? '1' : '0',
                    'maintenance_start_at' => $data['maintenance_start_at'] ?? null,
                    'maintenance_release_at' => $autoRelease ? ($data['maintenance_release_at'] ?? null) : null,
                ]);
            },
        )->execute($actor, $request->validated());

        return redirect()->route('admin.settings.base.maintenance')
            ->with('success', __('admin/settings/base/maintenance.settings_updated'));
    }

    /**
     * Preview maintenance screen
     */
    public function preview()
    {
        $message = request()->input('message', __('http/controllers/admin/settings/base/admin_base_maintenance_controller.currently_under_maintenance_please_wait'));
        $releaseAt = request()->input('release_at');

        return view('maintenance', [
            'message' => $message,
            'releaseAt' => $releaseAt,
            'isPreview' => true,
        ]);
    }
}
