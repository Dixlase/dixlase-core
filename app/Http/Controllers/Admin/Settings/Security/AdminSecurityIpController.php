<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
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

namespace App\Http\Controllers\Admin\Settings\Security;

use App\Contracts\Repositories\SecuritySettingRepositoryInterface;
use App\Helpers\AdminModeHelper;
use App\Helpers\IpAccessControlHelper;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Http\Requests\Admin\Settings\Security\AdminSecurityIpUpdateRequest;
use Illuminate\Http\Request;

class AdminSecurityIpController extends AdminLoggedInController
{
    protected const SETTING_KEYS = [
        'enable_allowed_admin_ips',
        'allowed_admin_ips',
        'enable_blocked_admin_ips',
        'blocked_admin_ips',
        'enable_allowed_front_ips',
        'allowed_front_ips',
        'enable_blocked_front_ips',
        'blocked_front_ips',
    ];

    protected SecuritySettingRepositoryInterface $securitySettingRepository;

    public function __construct(SecuritySettingRepositoryInterface $securitySettingRepository)
    {
        parent::__construct();
        $this->securitySettingRepository = $securitySettingRepository;
    }

    /**
     * IP access control settings page
     */
    public function index(Request $request)
    {
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
        $this->viewParams['ipDiagnostics'] = IpAccessControlHelper::inspectConnection($request);
        $this->viewParams['modeData'] = AdminModeHelper::getViewModeData('settings.security.ip');

        return view('admin.settings.security.ip', $this->viewParams);
    }

    /**
     * Update IP access control settings
     */
    public function update(AdminSecurityIpUpdateRequest $request)
    {
        $actor = new \App\Actors\MemberActor(\App\Helpers\AdminHelper::getMember());

        \App\Actions\Settings\UpdateSettingsAction::make(
            repository: $this->securitySettingRepository,
            settingsPage: 'security.ip',
            settingKeys: static::SETTING_KEYS,
            writeCallback: function ($repo, $data) {
                foreach (static::SETTING_KEYS as $key) {
                    $repo->set($key, $data[$key] ?? (str_contains($key, 'enable_') ? false : ''));
                }
            },
            permission: \App\Enums\Permission::SETTINGS_SECURITY,
        )->execute($actor, $request->validated());

        return redirect()->route('admin.settings.security.ip')
            ->with('success', __('admin/settings/security/ip.settings_updated'));
    }
}
