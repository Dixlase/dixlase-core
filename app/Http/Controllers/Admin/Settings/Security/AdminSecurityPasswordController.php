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
use App\Http\Requests\Admin\Settings\Security\AdminSecurityPasswordUpdateRequest;

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
            // 共通設定
            'pwned_password_check_enabled' => filter_var($this->securitySettingRepository->get('pwned_password_check_enabled', false), FILTER_VALIDATE_BOOLEAN),
            
            // デフォルトパスワードポリシー
            'password_min_length_default' => (int) $this->securitySettingRepository->get('password_min_length_default', 8),
            'password_require_uppercase_default' => filter_var($this->securitySettingRepository->get('password_require_uppercase_default', true), FILTER_VALIDATE_BOOLEAN),
            'password_require_number_default' => filter_var($this->securitySettingRepository->get('password_require_number_default', true), FILTER_VALIDATE_BOOLEAN),
            'password_require_symbol_default' => filter_var($this->securitySettingRepository->get('password_require_symbol_default', false), FILTER_VALIDATE_BOOLEAN),
            'password_reset_enabled_default' => filter_var($this->securitySettingRepository->get('password_reset_enabled_default', true), FILTER_VALIDATE_BOOLEAN),
        ];

        $this->viewParams['settings'] = $settings;

        return view('admin.settings.security.password', $this->viewParams);
    }

    /**
     * パスワードセキュリティ設定の更新
     */
    public function update(AdminSecurityPasswordUpdateRequest $request)
    {
        $validated = $request->validated();

        // 共通設定
        if (array_key_exists('pwned_password_check_enabled', $validated)) {
            $this->securitySettingRepository->set('pwned_password_check_enabled', $validated['pwned_password_check_enabled'] ?? false);
        }

        // デフォルトパスワードポリシー
        if (array_key_exists('password_min_length_default', $validated)) {
            $this->securitySettingRepository->set('password_min_length_default', (int) $validated['password_min_length_default']);
        }
        if (array_key_exists('password_require_uppercase_default', $validated)) {
            $this->securitySettingRepository->set('password_require_uppercase_default', $validated['password_require_uppercase_default'] ?? false);
        }
        if (array_key_exists('password_require_number_default', $validated)) {
            $this->securitySettingRepository->set('password_require_number_default', $validated['password_require_number_default'] ?? false);
        }
        if (array_key_exists('password_require_symbol_default', $validated)) {
            $this->securitySettingRepository->set('password_require_symbol_default', $validated['password_require_symbol_default'] ?? false);
        }
        if (array_key_exists('password_reset_enabled_default', $validated)) {
            $this->securitySettingRepository->set('password_reset_enabled_default', $validated['password_reset_enabled_default'] ?? false);
        }

        return redirect()->route('admin.settings.security.password')
            ->with('success', __('admin/settings/security/password.settings_updated'));
    }
}
