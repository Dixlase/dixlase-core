<?php

/**
 * This file is part of MySoftware.
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

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Models\SecuritySetting;
use App\Http\Requests\Admin\Settings\Security\AdminSettngsSecurityUpdateRequest;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;



class AdminSecuritySettingsController extends AdminLoggedInController
{
    public function index()
    {

        // 権限を確認
        $this->checkPermission('super_admin');

        $settings = [
            'admin_url' => SecuritySetting::get('admin_url', 'member'),
            'enable_allowed_admin_ips' => SecuritySetting::get('enable_allowed_admin_ips', false),
            'allowed_admin_ips' => SecuritySetting::get('allowed_admin_ips', ''),
            'enable_blocked_admin_ips' => SecuritySetting::get('enable_blocked_admin_ips', false),
            'blocked_admin_ips' => SecuritySetting::get('blocked_admin_ips', ''),
            'force_ssl' => SecuritySetting::get('force_ssl', false),
        ];

        $this->viewParams['settings'] = $settings;

        return view('admin.settings.security.index', $this->viewParams);
    }

    public function update(AdminSettngsSecurityUpdateRequest $request)
    {


        // 権限を確認
        $this->checkPermission('super_admin');

        // 現在の管理画面URLを取得
        $currentAdminUrl = SecuritySetting::get('admin_url', config('security.admin_url'));

        SecuritySetting::set('admin_url', $request->input('admin_url'));
        SecuritySetting::set('allowed_admin_ips', $request->input('allowed_admin_ips'));
        SecuritySetting::set('blocked_admin_ips', $request->input('blocked_admin_ips'));
        SecuritySetting::set('force_ssl', $request->boolean('force_ssl'));

        // 新しい管理画面URLを取得
        $newAdminUrl = $request->input('admin_url', config('security.admin_url'));

        // SSLを強制しているかどうかを確認
        $forceSsl = $request->boolean('force_ssl');

        // 管理画面のURLが変更された場合
        if ($newAdminUrl !== $currentAdminUrl) {
            // ユーザーをログアウト
            Auth::guard('admin')->logout();
            Session::flush();

            $newAdminLoginUrl = url($newAdminUrl . '/login');

            //SSLを強制している場合、HTTPSにリダイレクト
            if ($forceSsl) {
                $newAdminLoginUrl = str_replace('http://', 'https://', $newAdminLoginUrl);
            }

            // 新しいURLのログイン画面にリダイレクト
            return redirect($newAdminLoginUrl)
                ->with('success', '管理画面URLが変更されました。新しいURLでログインしてください。');
        }

        //通常のリダイレクト。セキュリティ設定のURLを取得
        $securityUrl = url($newAdminUrl . '/settings/security');

        //SSLを強制している場合、HTTPSに変換
        if ($forceSsl) {
            $securityUrl = str_replace('http://', 'https://', $securityUrl);
        }

        //リダイレクト
        return redirect($securityUrl)
            ->with('success', 'セキュリティ設定が更新されました。');
    }
}
