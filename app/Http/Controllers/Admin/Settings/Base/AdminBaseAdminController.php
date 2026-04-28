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
use App\Helpers\AdminHelper;
use App\Helpers\AdminModeHelper;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Http\Requests\Admin\Settings\Base\AdminBaseAdminUpdateRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class AdminBaseAdminController extends AdminLoggedInController
{
    protected const SETTING_KEYS = ['admin_url', 'force_ssl'];

    protected BaseSettingRepositoryInterface $baseSettingRepository;

    public function __construct(BaseSettingRepositoryInterface $baseSettingRepository)
    {
        parent::__construct();
        $this->baseSettingRepository = $baseSettingRepository;
    }

    /**
     * 管理画面設定ページ
     */
    public function index()
    {
        $adminUrl = $this->baseSettingRepository->get('admin_url', config('admin.admin_url'));
        $prefixes = config('admin.url.admin_url_prefixes', ['admin']);

        // 現在のadmin_urlをプレフィックスとサフィックスに分割
        $parts = explode('-', $adminUrl, 2);
        $currentPrefix = in_array($parts[0], $prefixes) ? $parts[0] : $prefixes[0];
        $currentSuffix = $parts[1] ?? '';

        $settings = [
            'admin_url' => $adminUrl,
            'admin_url_prefix' => $currentPrefix,
            'admin_url_suffix' => $currentSuffix,
            'force_ssl' => (bool) $this->baseSettingRepository->get('force_ssl', false),
        ];

        $this->viewParams['settings'] = $settings;
        $this->viewParams['prefixes'] = array_combine($prefixes, $prefixes);
        $this->viewParams['modeData'] = AdminModeHelper::getViewModeData('settings.base.admin');

        return view('admin.settings.base.admin', $this->viewParams);
    }

    /**
     * 管理画面設定の更新
     */
    public function update(AdminBaseAdminUpdateRequest $request)
    {
        $validated = $request->validated();
        $currentAdminUrl = AdminHelper::getAdminUrl();
        $newAdminUrl = $validated['admin_url'];
        $forceSsl = $request->has('force_ssl') ? 1 : 0;

        $actor = new \App\Actors\MemberActor(AdminHelper::getMember());

        \App\Actions\Settings\UpdateSettingsAction::make(
            repository: $this->baseSettingRepository,
            settingsPage: 'base.admin',
            settingKeys: static::SETTING_KEYS,
            writeCallback: function ($repo, $data) use ($forceSsl) {
                $repo->set('admin_url', $data['admin_url']);
                $repo->set('force_ssl', $forceSsl);
            },
        )->execute($actor, $validated);

        // 管理画面URLが変更された場合の特別な処理
        if ($newAdminUrl !== $currentAdminUrl) {
            Auth::guard('member')->logout();
            Session::flush();

            $newAdminLoginUrl = url($newAdminUrl.'/login');
            if ($forceSsl) {
                $newAdminLoginUrl = str_replace('http://', 'https://', $newAdminLoginUrl);
            }

            return redirect($newAdminLoginUrl)
                ->with('success', __('admin/settings/base/admin.admin_url_changed'));
        }

        $baseUrl = url($newAdminUrl.'/settings/base/admin');
        if ($forceSsl) {
            $baseUrl = str_replace('http://', 'https://', $baseUrl);
        }

        return redirect($baseUrl)->with('success', __('admin/settings/base/admin.settings_updated'));
    }
}
