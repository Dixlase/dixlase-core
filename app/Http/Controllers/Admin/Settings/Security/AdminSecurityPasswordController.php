<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Http\Requests\Admin\Settings\Security\AdminSecurityPasswordUpdateRequest;

class AdminSecurityPasswordController extends AdminLoggedInController
{
    protected const SETTING_KEYS = [
        'pwned_password_check_enabled',
        'password_min_length',
        'password_require_uppercase',
        'password_require_number',
        'password_require_symbol',
        'password_reset_enabled',
    ];

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
            ->map(fn ($label, $key) => ['value' => (string) $key, 'label' => $label])
            ->values()
            ->toArray();

        $this->viewParams['settings'] = $settings;
        $this->viewParams['minLengthOptions'] = $minLengthOptions;
        $this->viewParams['modeData'] = AdminModeHelper::getViewModeData('settings.security.password');

        return view('admin.settings.security.password', $this->viewParams);
    }

    /**
     * パスワードセキュリティ設定の更新
     */
    public function update(AdminSecurityPasswordUpdateRequest $request)
    {
        $actor = new \App\Actors\MemberActor(\App\Helpers\AdminHelper::getMember());

        \App\Actions\Settings\UpdateSettingsAction::make(
            repository: $this->securitySettingRepository,
            settingsPage: 'security.password',
            settingKeys: static::SETTING_KEYS,
            writeCallback: function ($repo, $data) {
                foreach (static::SETTING_KEYS as $key) {
                    if (array_key_exists($key, $data)) {
                        $repo->set($key, $key === 'password_min_length' ? (int) $data[$key] : ($data[$key] ?? false));
                    }
                }
            },
            permission: \App\Enums\Permission::SETTINGS_SECURITY,
        )->execute($actor, $request->validated());

        return redirect()->route('admin.settings.security.password')
            ->with('success', __('admin/settings/security/password.settings_updated'));
    }
}
