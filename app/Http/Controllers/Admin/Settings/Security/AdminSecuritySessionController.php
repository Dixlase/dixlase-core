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
use App\Helpers\ConfigHelper;
use Illuminate\Http\Request;

class AdminSecuritySessionController extends AdminLoggedInController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * セッション設定ページ
     */
    public function index()
    {
        $settings = [
            'session_encrypt' => ConfigHelper::getSessionEncrypt(),
            'session_lifetime' => ConfigHelper::getSessionLifetime(),
        ];

        $this->viewParams['settings'] = $settings;

        return view('admin.settings.security.session', $this->viewParams);
    }

    /**
     * セッション設定の更新
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'session_encrypt' => 'boolean',
            'session_lifetime' => 'required|integer|min:1|max:43200',
        ]);

        // セッション設定を更新
        ConfigHelper::setSessionEncrypt($validated['session_encrypt'] ?? false);
        ConfigHelper::setSessionLifetime($validated['session_lifetime']);

        return redirect()->route('admin.settings.security.session')
            ->with('success', __('admin/settings/security/session.settings_updated'));
    }
}
