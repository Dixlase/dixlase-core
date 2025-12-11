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

use App\Helpers\AdminHelper;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Contracts\Repositories\BaseSettingRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class AdminBaseAdminController extends AdminLoggedInController
{
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
        $settings = [
            'admin_url' => $this->baseSettingRepository->get('admin_url', config('admin.admin_url')),
            'force_ssl' => (bool) $this->baseSettingRepository->get('force_ssl', false),
        ];

        $this->viewParams['settings'] = $settings;

        return view('admin.settings.base.admin', $this->viewParams);
    }

    /**
     * 管理画面設定の更新
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'admin_url' => 'required|string|max:100|regex:/^[a-zA-Z0-9_-]+$/',
        ]);

        $forceSsl = $request->has('force_ssl') ? 1 : 0;

        // DBに保存
        $this->baseSettingRepository->set('admin_url', $validated['admin_url']);
        $this->baseSettingRepository->set('force_ssl', $forceSsl);

        // 管理画面URLが変更された場合の特別な処理
        $currentAdminUrl = AdminHelper::getAdminUrl();
        $newAdminUrl = $validated['admin_url'];

        if ($newAdminUrl !== $currentAdminUrl) {
            // ユーザーをログアウト
            Auth::guard('admin')->logout();
            Session::flush();

            $newAdminLoginUrl = url($newAdminUrl . '/login');

            // SSL強制の場合、HTTPSにリダイレクト
            if ($forceSsl) {
                $newAdminLoginUrl = str_replace('http://', 'https://', $newAdminLoginUrl);
            }

            return redirect($newAdminLoginUrl)
                ->with('success', __('admin.settings.base.admin.admin_url_changed'));
        }

        // 通常のリダイレクト
        $baseUrl = url($newAdminUrl . '/settings/base/admin');

        if ($forceSsl) {
            $baseUrl = str_replace('http://', 'https://', $baseUrl);
        }

        return redirect($baseUrl)->with('success', __('admin.settings.base.admin.settings_updated'));
    }
}
