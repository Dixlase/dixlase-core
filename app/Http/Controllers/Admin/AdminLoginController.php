<?php

/**
 * This file is part of MySoftware.
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

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\AdminLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class AdminLoginController extends AdminController
{

    //初期設定を行う
    public function __construct()
    {
        parent::__construct();
    }
    /**
     * Display the login view.
     */
    public function create()
    {
        $member = Auth::guard('member')->user();
        if ($member) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin::login', $this->viewParams);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(AdminLoginRequest $request)
    {
        $request->authenticate('member');
        //セッションの再生成（旧セッション破棄 & 新ID発行）
        $request->session()->regenerate(true);
        return redirect()->intended(route('admin.dashboard'));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('member')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return to_route('admin.login');
    }
}
