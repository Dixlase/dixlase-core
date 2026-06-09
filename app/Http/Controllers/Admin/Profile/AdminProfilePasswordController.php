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

namespace App\Http\Controllers\Admin\Profile;

use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Http\Requests\Admin\Profile\ProfilePasswordUpdateRequest;
use App\Models\SecuritySetting;
use App\Services\PasswordService;
use Illuminate\Support\Facades\Auth;

class AdminProfilePasswordController extends AdminLoggedInController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Show the password edit page.
     */
    public function index()
    {
        // Get password requirements (from security settings)
        $this->viewParams['passwordMinLength'] = (int) SecuritySetting::getValue('password_min_length', 8);
        $this->viewParams['passwordRequireUppercase'] = (bool) SecuritySetting::getValue('password_require_uppercase', true);
        $this->viewParams['passwordRequireSymbol'] = (bool) SecuritySetting::getValue('password_require_symbol', false);

        return view('admin.profile.password', $this->viewParams);
    }

    /**
     * Update password.
     */
    public function update(ProfilePasswordUpdateRequest $request)
    {
        $member = Auth::guard('member')->user();
        $validated = $request->validated();

        if (! empty($validated['password'])) {
            $member->password = PasswordService::hash($validated['password']);
            $member->save();

            \App\Facades\Audit::log([
                'category' => 'account',
                'action' => 'profile.password_changed',
                'actor' => auth()->user(),
                'target' => auth()->user(),
                'severity' => 'notice',
            ]);
        }

        return redirect()->route('admin.profile.password')->with('success', __('admin/profile/common.updated'));
    }
}
