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

use App\Helpers\AdminModeHelper;
use App\Helpers\ConfigHelper;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Http\Requests\Admin\Settings\Security\AdminSecuritySessionUpdateRequest;

class AdminSecuritySessionController extends AdminLoggedInController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Session settings page
     */
    public function index()
    {
        $settings = [
            'session_encrypt' => ConfigHelper::getSessionEncrypt(),
            'session_lifetime' => ConfigHelper::getSessionLifetime(),
        ];

        $this->viewParams['settings'] = $settings;
        $this->viewParams['modeData'] = AdminModeHelper::getViewModeData('settings.security.session');

        return view('admin.settings.security.session', $this->viewParams);
    }

    /**
     * Update session settings
     */
    public function update(AdminSecuritySessionUpdateRequest $request)
    {
        $validated = $request->validated();

        $before = ['session_encrypt' => ConfigHelper::getSessionEncrypt(), 'session_lifetime' => ConfigHelper::getSessionLifetime()];

        // Update session settings
        ConfigHelper::setSessionEncrypt($validated['session_encrypt'] ?? false);
        ConfigHelper::setSessionLifetime($validated['session_lifetime']);

        $after = ['session_encrypt' => ConfigHelper::getSessionEncrypt(), 'session_lifetime' => ConfigHelper::getSessionLifetime()];
        \App\Facades\Audit::logBulkSettingsChange('security.session', $before, $after, auth()->user());

        return redirect()->route('admin.settings.security.session')
            ->with('success', __('admin/settings/security/session.settings_updated'));
    }
}
