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

namespace App\Http\Controllers\Admin\Profile;

use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Http\Requests\Admin\Profile\ProfileNotificationsUpdateRequest;
use App\Enums\AuthenticationMode;
use App\Models\SecuritySetting;
use Illuminate\Support\Facades\Auth;

class AdminProfileNotificationsController extends AdminLoggedInController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Show the notifications edit page.
     */
    public function index()
    {
        $member = Auth::guard('member')->user();
        
        // ログイン通知設定の追加
        $loginNoticeGlobal = (int) MemberSetting::getValue(
            'login_notification_mode',
            AuthenticationMode::UseProfileSetting->value
        );
        $loginNotificationMode = $member->login_notification_mode;
        
        $this->viewParams['loginNoticeGlobal'] = $loginNoticeGlobal;
        $this->viewParams['loginNotificationMode'] = $loginNotificationMode;
        
        return view('admin.profile.notifications', $this->viewParams);
    }

    /**
     * Update notifications.
     */
    public function update(ProfileNotificationsUpdateRequest $request)
    {
        $member = Auth::guard('member')->user();
        $validated = $request->validated();
        
        // login_notification_mode は全体設定が UseProfileSetting のときだけ上書き（セキュリティ設定から）
        $globalLogin = (int) SecuritySetting::getValue('login_notification_mode', AuthenticationMode::UseProfileSetting->value);
        if ($globalLogin === AuthenticationMode::UseProfileSetting->value && array_key_exists('login_notification_mode', $validated)) {
            $member->login_notification_mode = (int) $validated['login_notification_mode'];
            $member->save();
        }
        
        return redirect()->route('admin.profile.notifications')->with('success', __('admin/profile.updated'));
    }
}
