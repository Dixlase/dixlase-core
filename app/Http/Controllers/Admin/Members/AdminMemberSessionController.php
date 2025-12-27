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

class AdminMemberSessionController extends AdminMemberSettingsController
{
    /**
     * セッション設定画面
     */
    public function index()
    {
        $this->addBreadcrumb('admin.members.index', __('admin/nav.settings.members.text'));
        $this->addBreadcrumb('admin.members.settings', __('admin/members/settings.heading'));
        $this->addBreadcrumb(null, __('admin/members/settings.session.heading'));
        $this->loadViewParams();
        return view('admin.members.settings.session', $this->viewParams);
    }

    /**
     * セッション設定更新
     */
    public function update(AdminSettingsMemberSettingsRequest $request)
    {
        $validated = $request->validated();

        if (array_key_exists('members_session_lifetime_enabled', $validated)) {
            $this->memberSettingRepository->set('members_session_lifetime_enabled', $validated['members_session_lifetime_enabled'] ? '1' : '0');
        }
        if (array_key_exists('members_session_lifetime', $validated)) {
            $this->memberSettingRepository->set('members_session_lifetime', (string) $validated['members_session_lifetime']);
        }

        return redirect()->back()
            ->with('success', __('admin/members/settings.updated'));
    }
}
