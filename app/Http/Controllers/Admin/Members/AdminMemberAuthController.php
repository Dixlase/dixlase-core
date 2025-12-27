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

namespace App\Http\Controllers\Admin\Members;

use App\Http\Requests\Admin\Settings\Members\AdminSettingsMemberSettingsRequest;

class AdminMemberAuthController extends AdminMemberSettingsController
{
    /**
     * 認証設定画面
     */
    public function index()
    {
        $this->addBreadcrumb('admin.members.index', __('admin/nav.settings.members.text'));
        $this->addBreadcrumb('admin.members.settings', __('admin/members/settings.heading'));
        $this->addBreadcrumb(null, __('admin/members/settings.auth.heading'));
        $this->setDescription(__('admin/members/settings.auth.description'));
        $this->loadViewParams();
        return view('admin.members.settings.auth', $this->viewParams);
    }

    /**
     * 認証設定更新
     */
    public function update(AdminSettingsMemberSettingsRequest $request)
    {
        $validated = $request->validated();

        if (array_key_exists('login_notification_mode', $validated)) {
            $this->memberSettingRepository->set('login_notification_mode', (int) $validated['login_notification_mode']);
        }
        if (array_key_exists('login_attempt_limit_enabled', $validated)) {
            $this->memberSettingRepository->set('login_attempt_limit_enabled', $validated['login_attempt_limit_enabled'] ? '1' : '0');
        }
        if (array_key_exists('login_attempt_max_attempts', $validated)) {
            $this->memberSettingRepository->set('login_attempt_max_attempts', (string) $validated['login_attempt_max_attempts']);
        }
        if (array_key_exists('login_attempt_time_window', $validated)) {
            $this->memberSettingRepository->set('login_attempt_time_window', (string) $validated['login_attempt_time_window']);
        }
        if (array_key_exists('login_attempt_lockout_duration', $validated)) {
            $this->memberSettingRepository->set('login_attempt_lockout_duration', (string) $validated['login_attempt_lockout_duration']);
        }
        if (array_key_exists('lockout_notification_enabled', $validated)) {
            $this->memberSettingRepository->set('lockout_notification_enabled', $validated['lockout_notification_enabled'] ? '1' : '0');
        }

        if (array_key_exists('force_2fa', $validated)) {
            $this->memberSettingRepository->set('force_2fa', (int) $validated['force_2fa']);
        }
        if (array_key_exists('two_factor_expire_minutes', $validated)) {
            $this->memberSettingRepository->set('two_factor_expire_minutes', (string) $validated['two_factor_expire_minutes']);
        }
        if (array_key_exists('two_factor_resend_interval_seconds', $validated)) {
            $this->memberSettingRepository->set('two_factor_resend_interval_seconds', (string) $validated['two_factor_resend_interval_seconds']);
        }
        if (array_key_exists('enabled_2fa_passkey', $validated)) {
            $passkeyValue = $validated['enabled_2fa_passkey'] ? '1' : '0';
            $this->memberSettingRepository->set('enabled_2fa_passkey', $passkeyValue);
        }
        if (array_key_exists('2fa_max_attempts', $validated)) {
            $this->memberSettingRepository->set('2fa_max_attempts', (string) $validated['2fa_max_attempts']);
        }
        if (array_key_exists('2fa_attempt_window', $validated)) {
            $this->memberSettingRepository->set('2fa_attempt_window', (string) $validated['2fa_attempt_window']);
        }
        if (array_key_exists('2fa_lockout_duration', $validated)) {
            $this->memberSettingRepository->set('2fa_lockout_duration', (string) $validated['2fa_lockout_duration']);
        }
        if (array_key_exists('2fa_lockout_notification_enabled', $validated)) {
            $this->memberSettingRepository->set('2fa_lockout_notification_enabled', $validated['2fa_lockout_notification_enabled'] ? '1' : '0');
        }
        if (array_key_exists('recovery_codes_count', $validated)) {
            $this->memberSettingRepository->set('recovery_codes_count', (string) $validated['recovery_codes_count']);
        }
        if (array_key_exists('recovery_code_regenerate_interval', $validated)) {
            $this->memberSettingRepository->set('recovery_code_regenerate_interval', (string) $validated['recovery_code_regenerate_interval']);
        }

        if (array_key_exists('captcha_admin_login_enabled', $validated)) {
            $this->memberSettingRepository->set('captcha_admin_login_enabled', $request->boolean('captcha_admin_login_enabled') ? '1' : '0');
        }

        return redirect()->back()
            ->with('success', __('admin/members/settings.updated'));
    }
}
