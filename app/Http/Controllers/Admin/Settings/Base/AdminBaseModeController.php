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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
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

use App\Contracts\Repositories\BaseSettingRepositoryInterface;
use App\Enums\AdminMode;
use App\Helpers\AdminModeHelper;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Services\AdminModeAutoConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminBaseModeController extends AdminLoggedInController
{
    protected const SETTING_KEYS = ['admin_mode'];

    protected BaseSettingRepositoryInterface $baseSettingRepository;

    public function __construct(BaseSettingRepositoryInterface $baseSettingRepository)
    {
        parent::__construct();
        $this->baseSettingRepository = $baseSettingRepository;
    }

    /**
     * モード設定ページ
     */
    public function index()
    {
        $currentMode = AdminMode::fromInt(
            (int) $this->baseSettingRepository->get('admin_mode', AdminMode::Simple->value)
        );

        $this->viewParams['currentMode'] = $currentMode;
        $this->viewParams['modeData'] = AdminModeHelper::getViewModeData('settings.base.mode');

        return view('admin.settings.base.mode', $this->viewParams);
    }

    /**
     * モード設定の更新
     */
    public function update(Request $request, AdminModeAutoConfigService $autoConfigService)
    {
        $validated = $request->validate([
            'admin_mode' => 'required|integer|in:0,1',
        ]);

        $actor = new \App\Actors\MemberActor(\App\Helpers\AdminHelper::getMember());

        \App\Actions\Settings\UpdateSettingsAction::make(
            repository: $this->baseSettingRepository,
            settingsPage: 'base.mode',
            settingKeys: static::SETTING_KEYS,
            writeCallback: function ($repo, $data) use ($autoConfigService) {
                $newMode = AdminMode::fromInt((int) $data['admin_mode']);

                $repo->set('admin_mode', (string) $newMode->value);
                AdminModeHelper::clearCache();

                if ($newMode->isSimple()) {
                    $results = $autoConfigService->applyAll();
                    Log::channel('admin_activity')->info('かんたんモード自動設定を適用', [
                        'results' => $results,
                        'member_id' => auth()->id(),
                    ]);
                }

                Log::channel('admin_activity')->info('管理画面モード設定を更新', [
                    'mode' => $newMode->name,
                    'member_id' => auth()->id(),
                ]);
            },
        )->execute($actor, $validated);

        return redirect()->route('admin.settings.base.mode')
            ->with('success', __('admin/settings/base/mode.settings_updated'));
    }
}
