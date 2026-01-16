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
use Illuminate\Http\Request;

class AdminSecurityPasswordController extends AdminLoggedInController
{
    protected SecuritySettingRepositoryInterface $securitySettingRepository;

    public function __construct(SecuritySettingRepositoryInterface $securitySettingRepository)
    {
        parent::__construct();
        $this->securitySettingRepository = $securitySettingRepository;
    }

    /**
     * パスワードセキュリティ設定ページ
     */
    public function index()
    {
        $settings = [
            'pwned_password_check_enabled' => filter_var($this->securitySettingRepository->get('pwned_password_check_enabled', false), FILTER_VALIDATE_BOOLEAN),
        ];

        $this->viewParams['settings'] = $settings;

        return view('admin.settings.security.password', $this->viewParams);
    }

    /**
     * パスワードセキュリティ設定の更新
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'pwned_password_check_enabled' => 'boolean',
        ]);

        // パスワードセキュリティ設定を更新
        $this->securitySettingRepository->set('pwned_password_check_enabled', $validated['pwned_password_check_enabled'] ?? false);

        return redirect()->route('admin.settings.security.password')
            ->with('success', __('admin/settings/security/password.settings_updated'));
    }
}
