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

class AdminMemberPasswordController extends AdminMemberSettingsController
{
    /**
     * パスワード設定画面
     */
    public function index()
    {
        $this->loadViewParams();
        return view('admin.members.settings.password', $this->viewParams);
    }

    /**
     * パスワード設定更新
     */
    public function update(AdminSettingsMemberSettingsRequest $request)
    {
        $validated = $request->validated();

        if (array_key_exists('password_min_length', $validated)) {
            $this->memberSettingRepository->set('password_min_length', (int) $validated['password_min_length']);
        }
        if (array_key_exists('password_require_uppercase', $validated)) {
            $this->memberSettingRepository->set('password_require_uppercase', (int) $validated['password_require_uppercase']);
        }
        if (array_key_exists('password_require_number', $validated)) {
            $this->memberSettingRepository->set('password_require_number', (int) $validated['password_require_number']);
        }
        if (array_key_exists('password_require_symbol', $validated)) {
            $this->memberSettingRepository->set('password_require_symbol', (int) $validated['password_require_symbol']);
        }
        if (array_key_exists('password_reset_enabled', $validated)) {
            $this->memberSettingRepository->set('password_reset_enabled', (bool) $validated['password_reset_enabled']);
        }
        if (array_key_exists('pwned_password_check_enabled', $validated)) {
            $this->memberSettingRepository->set('pwned_password_check_enabled', (bool) $validated['pwned_password_check_enabled']);
        }

        return redirect()->back()
            ->with('success', __('admin/members/settings.updated'));
    }
}
