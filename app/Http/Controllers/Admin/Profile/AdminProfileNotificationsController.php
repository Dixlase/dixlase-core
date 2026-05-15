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
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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

namespace App\Http\Controllers\Admin\Profile;

use App\Enums\AuthenticationMode;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Http\Requests\Admin\Profile\ProfileNotificationsUpdateRequest;
use App\Models\SecuritySetting;
use App\Services\MailServerValidatorService;
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

        // Pass mail server settings status
        $this->viewParams['isMailServerTested'] = MailServerValidatorService::isMailServerTested();

        // Add login notification settings
        $loginNoticeGlobal = (int) SecuritySetting::getValue(
            'login_notification_mode',
            AuthenticationMode::UseProfileSetting->value
        );
        $loginNotificationMode = $member->login_notification_mode;

        $this->viewParams['loginNoticeGlobal'] = $loginNoticeGlobal;
        $this->viewParams['loginNotificationMode'] = $loginNotificationMode;

        $loginNotificationModeValue = $loginNotificationMode instanceof AuthenticationMode
            ? $loginNotificationMode->value
            : ($loginNotificationMode ?? 1);
        $this->viewParams['loginNotificationModeValue'] = $loginNotificationModeValue;

        return view('admin.profile.notifications', $this->viewParams);
    }

    /**
     * Update notifications.
     */
    public function update(ProfileNotificationsUpdateRequest $request)
    {
        $member = Auth::guard('member')->user();
        $validated = $request->validated();

        // login_notification_mode is overridden only when global settings is UseProfileSetting (from security settings)
        $globalLogin = (int) SecuritySetting::getValue('login_notification_mode', AuthenticationMode::UseProfileSetting->value);
        if ($globalLogin === AuthenticationMode::UseProfileSetting->value && array_key_exists('login_notification_mode', $validated)) {
            $member->login_notification_mode = (int) $validated['login_notification_mode'];
            $member->save();
        }

        return redirect()->route('admin.profile.notifications')->with('success', __('admin/profile/common.updated'));
    }
}
