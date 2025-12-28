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

namespace App\Http\Controllers\Admin\Settings\Security;

use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Contracts\Repositories\SecuritySettingRepositoryInterface;
use App\Helpers\ConfigHelper;
use Illuminate\Http\Request;

class AdminSecurityAuthController extends AdminLoggedInController
{
    protected SecuritySettingRepositoryInterface $securitySettingRepository;

    public function __construct(SecuritySettingRepositoryInterface $securitySettingRepository)
    {
        parent::__construct();
        $this->securitySettingRepository = $securitySettingRepository;
    }

    /**
     * 認証・セッション設定ページ
     */
    public function index()
    {
        
        $settings = [
            // Session settings
            'session_driver' => ConfigHelper::getSessionDriver(),
            'session_encrypt' => ConfigHelper::getSessionEncrypt(),
            'session_lifetime' => ConfigHelper::getSessionLifetime(),
            // Password security settings
            'pwned_password_check_enabled' => filter_var($this->securitySettingRepository->get('pwned_password_check_enabled', false), FILTER_VALIDATE_BOOLEAN),
        ];

        $this->viewParams['settings'] = $settings;

        return view('admin.settings.security.auth', $this->viewParams);
    }

    /**
     * 認証・セッション設定の更新
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'session_driver' => 'required|in:file,database,redis,memcached,cookie,array',
            'session_encrypt' => 'boolean',
            'session_lifetime' => 'required|integer|min:1|max:43200',
            'pwned_password_check_enabled' => 'boolean',
        ]);

        // セッション設定を更新
        ConfigHelper::setSessionDriver($validated['session_driver']);
        ConfigHelper::setSessionEncrypt($validated['session_encrypt'] ?? false);
        ConfigHelper::setSessionLifetime($validated['session_lifetime']);

        // パスワードセキュリティ設定を更新
        $this->securitySettingRepository->set('pwned_password_check_enabled', $validated['pwned_password_check_enabled'] ?? false);

        return redirect()->route('admin.settings.security.auth')
            ->with('success', __('admin/settings/security/auth_settings_updated'));
    }
}
