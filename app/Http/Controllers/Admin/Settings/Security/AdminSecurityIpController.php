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

class AdminSecurityIpController extends AdminLoggedInController
{
    protected SecuritySettingRepositoryInterface $securitySettingRepository;

    public function __construct(SecuritySettingRepositoryInterface $securitySettingRepository)
    {
        parent::__construct();
        $this->securitySettingRepository = $securitySettingRepository;
    }

    /**
     * IPアクセス制御設定ページ
     */
    public function index()
    {
        $this->addBreadcrumb(null, __('admin/nav.settings.text'));
        $this->addBreadcrumb('admin.settings.security.index', __('admin/nav.settings.security.text'));
        $this->addBreadcrumb(null, __('admin/nav.settings.security.ip'));
        $this->setBreadcrumbs();
        
        $settings = [
            'enable_allowed_admin_ips' => filter_var($this->securitySettingRepository->get('enable_allowed_admin_ips', false), FILTER_VALIDATE_BOOLEAN),
            'allowed_admin_ips' => $this->securitySettingRepository->get('allowed_admin_ips', ''),
            'enable_blocked_admin_ips' => filter_var($this->securitySettingRepository->get('enable_blocked_admin_ips', false), FILTER_VALIDATE_BOOLEAN),
            'blocked_admin_ips' => $this->securitySettingRepository->get('blocked_admin_ips', ''),
            'enable_allowed_front_ips' => filter_var($this->securitySettingRepository->get('enable_allowed_front_ips', false), FILTER_VALIDATE_BOOLEAN),
            'allowed_front_ips' => $this->securitySettingRepository->get('allowed_front_ips', ''),
            'enable_blocked_front_ips' => filter_var($this->securitySettingRepository->get('enable_blocked_front_ips', false), FILTER_VALIDATE_BOOLEAN),
            'blocked_front_ips' => $this->securitySettingRepository->get('blocked_front_ips', ''),
        ];

        $this->viewParams['settings'] = $settings;

        return view('admin.settings.security.ip', $this->viewParams);
    }

    /**
     * IPアクセス制御設定の更新
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'enable_allowed_admin_ips' => 'boolean',
            'allowed_admin_ips' => 'nullable|string',
            'enable_blocked_admin_ips' => 'boolean',
            'blocked_admin_ips' => 'nullable|string',
            'enable_allowed_front_ips' => 'boolean',
            'allowed_front_ips' => 'nullable|string',
            'enable_blocked_front_ips' => 'boolean',
            'blocked_front_ips' => 'nullable|string',
        ]);

        // IP設定を更新
        $this->securitySettingRepository->set('enable_allowed_admin_ips', $validated['enable_allowed_admin_ips'] ?? false);
        $this->securitySettingRepository->set('allowed_admin_ips', $validated['allowed_admin_ips'] ?? '');
        $this->securitySettingRepository->set('enable_blocked_admin_ips', $validated['enable_blocked_admin_ips'] ?? false);
        $this->securitySettingRepository->set('blocked_admin_ips', $validated['blocked_admin_ips'] ?? '');
        $this->securitySettingRepository->set('enable_allowed_front_ips', $validated['enable_allowed_front_ips'] ?? false);
        $this->securitySettingRepository->set('allowed_front_ips', $validated['allowed_front_ips'] ?? '');
        $this->securitySettingRepository->set('enable_blocked_front_ips', $validated['enable_blocked_front_ips'] ?? false);
        $this->securitySettingRepository->set('blocked_front_ips', $validated['blocked_front_ips'] ?? '');

        return redirect()->route('admin.settings.security.ip')
            ->with('success', __('admin/settings/security/ip_settings_updated'));
    }
}
