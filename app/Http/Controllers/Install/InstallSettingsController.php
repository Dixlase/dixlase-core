<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Http\Controllers\Install;

use App\Http\Requests\Install\InstallSettingsRequest;
use Illuminate\Support\Facades\Crypt;

/**
 * インストール - ステップ1: 基本設定
 */
class InstallSettingsController extends BaseInstallController
{
    /**
     * 基本設定画面を表示
     */
    public function create()
    {
        return view('install.settings', array_merge(
            $this->getViewData(1),
            ['errors' => session('errors') ?? new \Illuminate\Support\MessageBag()]
        ));
    }

    /**
     * 基本設定を保存
     */
    public function store(InstallSettingsRequest $request)
    {
        session([
            'install_data.site_name' => $request->site_name,
            'install_data.admin_account_name' => $request->admin_account_name,
            'install_data.admin_display_name' => $request->admin_display_name,
            'install_data.admin_email' => $request->admin_email,
            'install_data.admin_password' => Crypt::encryptString($request->admin_password),
        ]);

        return redirect()->route('install.environment');
    }
}
