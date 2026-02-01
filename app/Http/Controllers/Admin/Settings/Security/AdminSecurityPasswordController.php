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
            'password_min_length' => (int) $this->securitySettingRepository->get('password_min_length', 8),
            'password_require_uppercase' => filter_var($this->securitySettingRepository->get('password_require_uppercase', true), FILTER_VALIDATE_BOOLEAN),
            'password_require_number' => filter_var($this->securitySettingRepository->get('password_require_number', true), FILTER_VALIDATE_BOOLEAN),
            'password_require_symbol' => filter_var($this->securitySettingRepository->get('password_require_symbol', false), FILTER_VALIDATE_BOOLEAN),
            'password_reset_enabled' => filter_var($this->securitySettingRepository->get('password_reset_enabled', true), FILTER_VALIDATE_BOOLEAN),
        ];

        $minLengthOptions = collect(__('passwords.requirements.password_min_length_options'))
            ->map(fn($label, $key) => ['value' => (string) $key, 'label' => $label])
            ->values()
            ->toArray();

        $this->viewParams['settings'] = $settings;
        $this->viewParams['minLengthOptions'] = $minLengthOptions;

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
        if (array_key_exists('password_min_length', $validated)) {
            $this->securitySettingRepository->set('password_min_length', (int) $validated['password_min_length']);
        }
        if (array_key_exists('password_require_uppercase', $validated)) {
            $this->securitySettingRepository->set('password_require_uppercase', $validated['password_require_uppercase'] ?? false);
        }
        if (array_key_exists('password_require_number', $validated)) {
            $this->securitySettingRepository->set('password_require_number', $validated['password_require_number'] ?? false);
        }
        if (array_key_exists('password_require_symbol', $validated)) {
            $this->securitySettingRepository->set('password_require_symbol', $validated['password_require_symbol'] ?? false);
        }
        if (array_key_exists('password_reset_enabled', $validated)) {
            $this->securitySettingRepository->set('password_reset_enabled', $validated['password_reset_enabled'] ?? false);
        }

        return redirect()->route('admin.settings.security.password')
            ->with('success', __('admin/settings/security/password.settings_updated'));
    }
}
