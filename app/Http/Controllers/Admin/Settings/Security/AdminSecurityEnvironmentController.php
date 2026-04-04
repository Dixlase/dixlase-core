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

namespace App\Http\Controllers\Admin\Settings\Security;

use App\Enums\AppEnvironment;
use App\Helpers\AdminModeHelper;
use App\Helpers\EnvHelper;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Http\Requests\Admin\Settings\Security\AdminSecurityEnvironmentUpdateRequest;
use Illuminate\Support\Facades\Log;

class AdminSecurityEnvironmentController extends AdminLoggedInController
{
    /**
     * 環境設定ページ
     */
    public function index()
    {
        $settings = [
            'app_env' => config('app.env', 'local'),
            'app_debug' => config('app.debug', false),
        ];

        $this->viewParams['settings'] = $settings;
        $this->viewParams['environmentOptions'] = AppEnvironment::getRadioCardOptions();
        $this->viewParams['envColors'] = [
            'local' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
            'staging' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            'production' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        ];
        $this->viewParams['modeData'] = AdminModeHelper::getViewModeData('settings.security.environment');

        return view('admin.settings.security.environment', $this->viewParams);
    }

    /**
     * 環境設定の更新
     */
    public function update(AdminSecurityEnvironmentUpdateRequest $request)
    {
        $validated = $request->validated();

        // 本番環境でデバッグモードが有効の場合は警告
        if ($validated['app_env'] === 'production' && $validated['app_debug']) {
            return redirect()->route('admin.settings.security.environment')
                ->withErrors(['app_debug' => __('admin/settings/security/environment.production_debug_warning')])
                ->withInput();
        }

        $before = ['app_env' => config('app.env'), 'app_debug' => config('app.debug')];

        try {
            // .envファイルを更新（EnvHelperを使用）
            EnvHelper::update([
                'app_env' => $validated['app_env'],
                'app_debug' => $validated['app_debug'] ? 'true' : 'false',
            ]);

            Log::info('Environment settings updated', [
                'app_env' => $validated['app_env'],
                'app_debug' => $validated['app_debug'],
                'updated_by' => auth()->id(),
            ]);

            $after = ['app_env' => $validated['app_env'], 'app_debug' => $validated['app_debug']];
            \App\Facades\Audit::logBulkSettingsChange('security.environment', $before, $after, auth()->user());

            return redirect()->route('admin.settings.security.environment')
                ->with('success', __('admin/settings/security/environment.settings_updated'));
        } catch (\Exception $e) {
            Log::error('Failed to update environment settings', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('admin.settings.security.environment')
                ->withErrors(['general' => __('admin/settings/security/environment.update_failed')])
                ->withInput();
        }
    }
}
