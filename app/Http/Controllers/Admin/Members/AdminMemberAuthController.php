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
        if (array_key_exists('login_attempt_max_attempts_ip', $validated)) {
            $this->memberSettingRepository->set('login_attempt_max_attempts_ip', (string) $validated['login_attempt_max_attempts_ip']);
        }
        if (array_key_exists('login_attempt_time_window', $validated)) {
            $this->memberSettingRepository->set('login_attempt_time_window', (string) $validated['login_attempt_time_window']);
        }
        if (array_key_exists('login_attempt_lockout_duration', $validated)) {
            $this->memberSettingRepository->set('login_attempt_lockout_duration', (string) $validated['login_attempt_lockout_duration']);
        }
        if (array_key_exists('login_attempt_lockout_notification_enabled', $validated)) {
            $this->memberSettingRepository->set('login_attempt_lockout_notification_enabled', $validated['login_attempt_lockout_notification_enabled'] ? '1' : '0');
        }

        if (array_key_exists('two_fa_force_mode', $validated)) {
            $this->memberSettingRepository->set('two_fa_mode', (int) $validated['two_fa_force_mode']);
        }
        if (array_key_exists('two_fa_passkey_mode', $validated)) {
            $this->memberSettingRepository->set('two_fa_passkey_mode', (string) $validated['two_fa_passkey_mode']);
        }
        if (array_key_exists('two_fa_default_method', $validated)) {
            $this->memberSettingRepository->set('two_fa_default_method', (string) $validated['two_fa_default_method']);
        }
        if (array_key_exists('two_fa_expire_minutes', $validated)) {
            $this->memberSettingRepository->set('two_fa_expire_minutes', (string) $validated['two_fa_expire_minutes']);
        }
        if (array_key_exists('two_fa_resend_interval_seconds', $validated)) {
            $this->memberSettingRepository->set('two_fa_resend_interval_seconds', (string) $validated['two_fa_resend_interval_seconds']);
        }
        if (array_key_exists('two_fa_max_attempts', $validated)) {
            $this->memberSettingRepository->set('two_fa_max_attempts', (string) $validated['two_fa_max_attempts']);
        }
        if (array_key_exists('two_fa_attempt_window', $validated)) {
            $this->memberSettingRepository->set('two_fa_attempt_window', (string) $validated['two_fa_attempt_window']);
        }
        if (array_key_exists('two_fa_lockout_duration', $validated)) {
            $this->memberSettingRepository->set('two_fa_lockout_duration', (string) $validated['two_fa_lockout_duration']);
        }
        if (array_key_exists('two_fa_lockout_notification_enabled', $validated)) {
            $this->memberSettingRepository->set('two_fa_lockout_notification_enabled', $validated['two_fa_lockout_notification_enabled'] ? '1' : '0');
        }
        if (array_key_exists('two_fa_recovery_codes_count', $validated)) {
            $this->memberSettingRepository->set('two_fa_recovery_codes_count', (string) $validated['two_fa_recovery_codes_count']);
        }
        if (array_key_exists('two_fa_recovery_code_regenerate_interval', $validated)) {
            $this->memberSettingRepository->set('two_fa_recovery_code_regenerate_interval', (string) $validated['two_fa_recovery_code_regenerate_interval']);
        }

        if (array_key_exists('captcha_admin_login_enabled', $validated)) {
            $this->memberSettingRepository->set('captcha_admin_login_enabled', $request->boolean('captcha_admin_login_enabled') ? '1' : '0');
        }

        if (array_key_exists('captcha_password_reset_enabled', $validated)) {
            $this->memberSettingRepository->set('captcha_password_reset_enabled', $request->boolean('captcha_password_reset_enabled') ? '1' : '0');
        }

        return redirect()->back()
            ->with('success', __('admin/members/settings/index.updated'));
    }
}
