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

use App\Http\Requests\Install\InstallSecurityRequest;

/**
 * インストール - ステップ5: セキュリティ設定
 */
class InstallSecurityController extends BaseInstallController
{
    /**
     * セキュリティ設定画面を表示
     */
    public function create()
    {
        return view('install.security', $this->getViewData(5));
    }

    /**
     * セキュリティ設定を保存
     */
    public function store(InstallSecurityRequest $request)
    {
        $validated = $request->validated();

        // boolean値を文字列に変換（セッション保存用）
        $validated['enable_allowed_admin_ips'] = $validated['enable_allowed_admin_ips'] ? '1' : '0';
        $validated['enable_blocked_admin_ips'] = $validated['enable_blocked_admin_ips'] ? '1' : '0';
        $validated['enable_allowed_front_ips'] = $validated['enable_allowed_front_ips'] ? '1' : '0';
        $validated['enable_blocked_front_ips'] = $validated['enable_blocked_front_ips'] ? '1' : '0';

        session(['install_data' => array_merge(session('install_data', []), $validated)]);

        return redirect()->route('install.confirm');
    }
}
